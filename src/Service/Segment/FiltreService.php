<?php

namespace App\Service\Segment;

use App\Entity\ChampRequetable;
use App\Entity\Operateur;
use App\Entity\Segment;
use App\Entity\SegmentFiltre;
use App\Entity\SegmentFiltreValeur;
use DateTime;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class FiltreService
{
    private Connection $connexion;

    private EntityManagerInterface $em;

    private TokenStorageInterface $tokenStorage;

    private RequetteurService $requetteurService;

    public function __construct(
        EntityManagerInterface $em,
        TokenStorageInterface $tokenStorage,
        RequetteurService $requetteurService
    ) {
        $this->em = $em;
        $this->connexion = $this->em->getConnection();
        $this->tokenStorage = $tokenStorage;
        $this->requetteurService = $requetteurService;
    }

    /**
     * Créé un objet SegmentFiltre lié à un Segment.
     *
     * @param $segment
     * @param $filtre
     *
     * @throws \Exception
     */
    public function createFiltre($segment, $filtre, $persist = true, $index = 0)
    {
        $newFiltre = new SegmentFiltre();
        $newFiltre->setUuid(Uuid::uuid4());
        $newFiltre->setSegment($segment);
        if ($segment->getFiltres()) {
            $newFiltre->setOrdre(count($segment->getFiltres()) + 1);
        } else {
            $newFiltre->setOrdre($index);
        }
        $champ = $this->em->getRepository(ChampRequetable::class)->findBy(['uuid' => $filtre['champ']['uuid']]);
        if (isset($champ[0])) {
            $newFiltre->setChamp($champ[0]);
        }
        $operateur = $this->em->getRepository(Operateur::class)->findBy(['uuid' => $filtre['operateur']['uuid']]);
        if (isset($operateur[0])) {
            $newFiltre->setOperateur($operateur[0]);
        }
        $valeurs = [];
        foreach ($filtre['valeurs'] as $valeur) {
            $valeurs[] = $this->createValeur($newFiltre, json_encode($valeur));
        }
        if (!$persist) {
            $newFiltre->setValeurs($valeurs);
        }
        $newFiltre->setDateCreation(new DateTime());
        $newFiltre->setUserCreation($this->tokenStorage->getToken()->getUser());
        $newFiltre->setDateModification(new DateTime());
        $newFiltre->setUserModification($this->tokenStorage->getToken()->getUser());
        $this->em->persist($newFiltre);

        return $newFiltre;
    }

    /**
     * Créé un objet SegmentFiltreValeur lié à un SegmentFiltre.
     *
     * @param $filtre
     * @param $valeur
     *
     * @return SegmentFiltreValeur
     *
     * @throws \Exception
     */
    public function createValeur($filtre, $valeur)
    {
        $newValeur = new SegmentFiltreValeur();
        $newValeur->setUuid(Uuid::uuid4());
        $newValeur->setSegmentFiltre($filtre);
        if (!is_array($valeur)) {
            $valeur = json_decode($valeur);
        }
        if (is_array($valeur)) {
            $newValeur->setValeur((string) json_encode($valeur));
        } else {
            $newValeur->setValeur((string) json_encode([$valeur], JSON_OBJECT_AS_ARRAY));
        }
        $newValeur->setDateCreation(new DateTime());
        $newValeur->setUserCreation($this->tokenStorage->getToken()->getUser());
        $newValeur->setDateModification(new DateTime());
        $newValeur->setUserModification($this->tokenStorage->getToken()->getUser());
        $this->em->persist($newValeur);

        return $newValeur;
    }

    /**
     * Met à jour les filtres pour un segment.
     *
     * @param Segment $segment
     * @param $data
     *
     * @throws \Exception
     */
    public function updateFiltres($segment, $data, $persist = true)
    {
        $filtres = [];
        if ($segment->getFiltres()) {
            /** @var SegmentFiltre $dbFiltre */
            foreach ($segment->getFiltres() as $dbFiltre) {
                $filtreExiste = false;
                foreach ($data['segment']['filtres'] as $key => $item) {
                    if ($item['uuid'] === $dbFiltre->getUuid()->toString()) {
                        $filtreExiste = true;
                        $champ = $dbFiltre->getChamp();
                        if ($champ->getUuid()->toString() !== $item['champ']['uuid']) {
                            $newChamp = $this->em->getRepository(ChampRequetable::class)
                                ->findBy(['uuid' => $item['champ']['uuid']]);
                            if (isset($newChamp[0])) {
                                $dbFiltre->setChamp($newChamp[0]);
                            }
                        }
                        $operateur = $dbFiltre->getOperateur();
                        if ($operateur->getUuid()->toString() !== $item['operateur']['uuid']) {
                            $newOperateur = $this->em->getRepository(Operateur::class)
                                ->findBy(['uuid' => $item['operateur']['uuid']]);
                            if (isset($newOperateur[0])) {
                                $dbFiltre->setOperateur($newOperateur[0]);
                            }
                        }
                        /** @var SegmentFiltreValeur $dbValeur */
                        foreach ($dbFiltre->getValeurs() as $dbValeur) {
                            $valeurExiste = false;
                            foreach ($item['valeurs'] as $valeurKey => $valeur) {
                                if (json_decode($dbValeur->getValeur(), true) === $valeur) {
                                    $valeurExiste = true;
                                    unset($item['valeurs'][$valeurKey]);
                                }
                            }
                            if (!$valeurExiste) {
                                if ($persist) {
                                    $this->em->remove($dbValeur);
                                }
                            }
                        }
                        foreach ($item['valeurs'] as $valeur) {
                            $this->createValeur($dbFiltre, json_encode($valeur));
                        }
                        $this->em->persist($dbFiltre);
                        unset($data['segment']['filtres'][$key]);
                        $filtres[] = $dbFiltre;
                    }
                }
                if (!$filtreExiste) {
                    foreach ($dbFiltre->getValeurs() as $valeur) {
                        if ($persist) {
                            $this->em->remove($valeur);
                        }
                    }
                    if ($persist) {
                        $this->em->remove($dbFiltre);
                    }
                }
            }
        }
        if ($data['segment']['filtres']) {
            foreach ($data['segment']['filtres'] as $i => $item) {
                if ($item) {
                    $filtres[] = $this->createFiltre($segment, $item, $persist, $i);
                }
            }
        }

        if (!$persist) {
            $segment->setFiltres($filtres);
        }
    }

    public function caculerTousResultats($segment)
    {
        $data = [];
        $filtres = [];
        if (!is_array($segment->getFiltres())) {
            foreach ($segment->getFiltres() as $filtre) {
                $filtres[] = $filtre;
            }
        } else {
            $filtres = $segment->getFiltres();
        }
        foreach (array_keys($filtres) as $i) {
            $data[$i]['public'] = $this->requetteurService->countContacts($segment, $i + 1);
            $data[$i]['prive'] = $this->requetteurService->countContacts($segment, $i + 1, true);
        }

        return $data;
    }
}
