<?php

namespace App\Service\Segment;

use App\Entity\PersonneLien;
use App\Entity\PersonnePhysique;
use App\Entity\Segment;
use App\Entity\SegmentIntervenant;
use App\Entity\User;
use App\Service\HelperService;
use DateTime;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class SegmentService
{
    public const INTER_REFERENT = 1;

    public const INTER_INTERVENANT = 2;

    private Connection $connexion;

    private EntityManagerInterface $em;

    private TokenStorageInterface $tokenStorage;

    private HelperService $helperService;

    private RequetteurService $requetteurService;

    private FiltreService $filtreService;

    public function __construct(
        EntityManagerInterface $em,
        TokenStorageInterface $tokenStorage,
        HelperService $helperService,
        RequetteurService $requetteurService,
        FiltreService $filtreService
    ) {
        $this->em = $em;
        $this->connexion = $this->em->getConnection();
        $this->tokenStorage = $tokenStorage;
        $this->helperService = $helperService;
        $this->requetteurService = $requetteurService;
        $this->filtreService = $filtreService;
    }

    public function getSegmentUser($user)
    {
        $data = [];
        /** @var PersonnePhysique[] $personne */
        $personne = $this->em->getRepository(PersonnePhysique::class)
            ->findBy(['user' => $user]);
        if ($personne[0]) {
            $data['prenom'] = $personne[0]->getPrenom();
            $data['nom'] = $personne[0]->getNom();
        }

        return $data;
    }

    public function getResultats($uuid)
    {
        /** @var PersonneLien[] $liens */
        $uuids = $this->calculate($uuid);

        return $this->helperService->printResultats($uuids);
    }

    /**
     * Recalcule les résultats d'un segment.
     *
     * @param $uuid
     *
     * @return array|\mixed[][]|null
     *
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws Exception
     */
    public function calculate($uuid)
    {
        /** @var Segment[] $segment */
        $segment = $this->em->getRepository(Segment::class)->findBy(['uuid' => $uuid]);
        if ($segment[0]) {
            return $this->getContacts($segment[0], true);
        }

        return null;
    }

    public function getContacts(Segment $segment, $persist = true)
    {
        $sql = $this->requetteurService->buildSegmentRequete($segment->getFiltres());
        $resultat = $this->connexion->prepare($sql);
        $resultat->execute();
        $resultat = $resultat->fetchAllAssociative();
        $count = [
            'public' => 0,
            'prive' => 0,
        ];
        foreach ($resultat as $res) {
            if ($res['visibilite'] > 2) {
                ++$count['prive'];
            } else {
                ++$count['public'];
            }
        }
        if ($persist) {
            $segment->setNbContactsPrives($count['prive']);
            $segment->setNbContactsPublics($count['public']);
            $segment->setDerniereDateExecution(new DateTime());
            $this->em->persist($segment);
            $this->em->flush();
        }

        return $resultat;
    }

    /**
     * Recalcule le nombre de contacts d'une requête et selon un nombre de filtres déterminé.
     *
     * @param $uuid
     * @param $index
     *
     * @return int|mixed|null
     *
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws Exception
     */
    public function nbContacts($uuid, $index)
    {
        /** @var Segment[] $segment */
        $segment = $this->em->getRepository(Segment::class)->findBy(['uuid' => $uuid]);
        if ($segment[0]) {
            $this->requetteurService->countContacts($segment[0], $index);
        }

        return null;
    }

    /**
     * Recalcule la liste des  nombres de contacts d'une requête selon l'ensemble des filtres.
     *
     * @param $uuid
     * @param $index
     *
     * @return array|array[]|null
     *
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws Exception
     */
    public function listeNbContacts($uuid)
    {
        if (!is_string($uuid)
            ||
            (1 !== preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $uuid))
        ) {
            return null;
        }
        /** @var Segment[] $segment */
        $segment = $this->em->getRepository(Segment::class)->findBy(['uuid' => $uuid]);
        if ($segment[0]) {
            return $this->filtreService->caculerTousResultats($segment[0]);
        } else {
            return null;
        }
    }

    /**
     * Met à jour une Personne.
     *
     * @param $data
     *
     * @throws \Exception
     */
    public function update($data, $persist = true)
    {
        if ($persist) {
            $segmentListe = null;
            if (is_string($data['segment']['uuid'])
                &&
                (1 === preg_match(
                    '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
                    $data['segment']['uuid']
                ))
            ) {
                /** @var Segment[] $segmentListe */
                $segmentListe = $this->em->getRepository(Segment::class)->findBy(
                    ['uuid' => $data['segment']['uuid']]
                );
            }

            if (isset($segmentListe[0])) {
                $segment = $segmentListe[0];
            } else {
                $segment = new Segment();
                $segment->setDateCreation(new DateTime());
                $segment->setUserCreation($this->tokenStorage->getToken()->getUser());
            }
        } else {
            $segment = new Segment();
            $segment->setDateCreation(new DateTime());
            $segment->setUserCreation($this->tokenStorage->getToken()->getUser());
        }
        $segment->setTitre($data['segment']['titre']);
        $segment->setVisibilite($data['segment']['visibilite']);
        if (isset($data['segment']['actif'])) {
            $segment->setActive($data['segment']['actif']);
        } else {
            $segment->setActive(0);
        }
        $this->updateIntervenants($segment, $data, $persist);
        $this->filtreService->updateFiltres($segment, $data, $persist);
        $segment->setDateModification(new DateTime());
        $segment->setUserModification($this->tokenStorage->getToken()->getUser());
        $this->em->persist($segment);

        if ($persist) {
            $this->em->flush();

            return $segment->getUuid()->toString();
        } else {
            return $segment;
        }
    }

    /**
     * Met à jour les intervenants pour un segment.
     *
     * @param $data
     *
     * @throws \Exception
     */
    public function updateIntervenants($segment, $data, $persist = true)
    {
        $user = null;
        //Ajout du lien pour le référent
        if (isset($data['segment']['referent'])) {
            $exists = false;
            $found = $this->em->getRepository(SegmentIntervenant::class)
                ->findBy(['segment' => $segment, 'type' => self::INTER_REFERENT]);
            if (!empty($data['segment']['referent'])) {
                $referentLien = $this->em->getRepository(PersonneLien::class)
                    ->findBy(['uuid' => $data['segment']['referent']]);
                if (isset($referentLien[0])) {
                    $user = $referentLien[0]->getPersonnePhysique()->getUser();
                } else {
                    $referentLien = $this->em->getRepository(PersonnePhysique::class)
                        ->findBy(['uuid' => $data['segment']['referent']]);
                    if (isset($referentLien[0])) {
                        $user = $referentLien[0]->getUser();
                    }
                }
            }
            /** @var SegmentIntervenant $item */
            foreach ($found as $item) {
                if (!isset($user) || $item->getUser() !== $user) {
                    if ($persist) {
                        $this->em->remove($item);
                    }
                } else {
                    $exists = true;
                }
            }
            if (!$exists && $user) {
                $this->helperService->addIntervenantSegment($segment, $user, self::INTER_REFERENT);
            }
        }

        //Ajout du lien pour les intervenants
        if (isset($data['segment']['intervenants'])) {
            $found = $this->em->getRepository(SegmentIntervenant::class)
                ->findBy(['segment' => $segment, 'type' => self::INTER_INTERVENANT]);
            if (empty($data['segment']['intervenants'])) {
                foreach ($found as $item) {
                    if ($persist) {
                        $this->em->remove($item);
                    }
                }
            } else {
                /** @var User[] $users */
                $users = [];
                $found = $this->em->getRepository(SegmentIntervenant::class)
                    ->findBy(['segment' => $segment, 'type' => self::INTER_INTERVENANT]);
                foreach ($data['segment']['intervenants'] as $intervenant) {
                    $referentLien = $this->em->getRepository(PersonneLien::class)
                        ->findBy(['uuid' => $intervenant]);
                    $userItem = $referentLien[0]->getPersonnePhysique()->getUser();
                    $users[$userItem->getId()] = $userItem;
                }
                foreach ($found as $item) {
                    if (empty($users)
                        || !array_key_exists($item->getUser()->getId(), $users)) {
                        if ($persist) {
                            $this->em->remove($item);
                        }
                    } else {
                        unset($users[$item->getUser()->getId()]);
                    }
                }
                foreach ($users as $user) {
                    $this->helperService->addIntervenantSegment(
                        $segment,
                        $user,
                        self::INTER_INTERVENANT,
                        $persist
                    );
                }
            }
        }
    }

    /**
     * Recalcule les nombres de résultats intermédiaires d'un segment en cours de modification.
     *
     * @param $data
     *
     * @throws \Exception
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    public function calculer($data)
    {
        $segment = $this->update($data, false);

        return $this->filtreService->caculerTousResultats($segment);
    }

    /**
     * Récupère les  résultats d'un segment en cours de modification.
     *
     * @param $data
     *
     * @throws \Exception
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    public function liveResultats($data)
    {
        $segment = $this->update($data, false);
        $uuids = $this->getContacts($segment, false);

        return $this->helperService->printResultats($uuids);
    }
}
