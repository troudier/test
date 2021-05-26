<?php

namespace App\Service\Personne;

use App\Entity\Champ;
use App\Entity\LienChamp;
use App\Entity\LienIntervenant;
use App\Entity\PersonneLien;
use App\Entity\PersonneLienFonction;
use App\Entity\PersonneStatut;
use App\Entity\Tag;
use App\Repository\PersonneLienRepository;
use App\Service\AdresseService;
use App\Service\CoordonneesService;
use App\Service\MemoService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class PersonneService
{
    public const INTER_CREATEUR = 0;

    public const INTER_REFERENT = 1;

    public const INTER_INTERVENANT = 2;

    private EntityManagerInterface $em;

    private TokenStorageInterface $tokenStorage;

    private PersonnePhysiqueService $personnePhysiqueService;

    private PersonneLienService $personneLienService;

    private PersonneMoraleService $personneMoralService;

    private MemoService $memoService;

    private CoordonneesService $coordonneesService;

    private AdresseService $adresseService;

    private PersonneLienRepository $personneLienRepository;

    public function __construct(
        EntityManagerInterface $em,
        TokenStorageInterface $tokenStorage,
        PersonneLienService $personneLienService,
        PersonnePhysiqueService $personnePhysiqueService,
        PersonneMoraleService $personneMoraleService,
        MemoService $memoService,
        CoordonneesService $coordonneesService,
        AdresseService $adresseService,
        PersonneLienRepository $personneLienRepository
    ) {
        $this->em = $em;
        $this->tokenStorage = $tokenStorage;
        $this->personnePhysiqueService = $personnePhysiqueService;
        $this->personneMoralService = $personneMoraleService;
        $this->memoService = $memoService;
        $this->personneLienService = $personneLienService;
        $this->coordonneesService = $coordonneesService;
        $this->adresseService = $adresseService;
        $this->personneLienRepository = $personneLienRepository;
    }

    /**
     * Rattache une liste de tags à une personneLien.
     *
     * @param $data
     */
    public function insertTags($data)
    {
        $personneRepository = $this->em->getRepository(PersonneLien::class);
        /**
         * @var $lien PersonneLien[]
         */
        $lien = $personneRepository->findBy(['uuid' => $data['uuid']]);
        if (isset($lien[0])) {
            $newTags = [];
            foreach ($data['tags'] as $uuid) {
                $tagRepository = $this->em->getRepository(Tag::class);
                $newTags[] = $tagRepository->findBy(['uuid' => $uuid])[0];
            }
            $lien[0]->setTags($newTags);
            $this->em->persist($lien[0]);
            $this->em->flush();
        }
    }

    /**
     * Met à jour une Personne.
     *
     * @param $data
     *
     * @throws \Exception
     */
    public function update($data)
    {
        /**
         * @var PersonneLien[] $lien
         */
        $lien = $this->em->getRepository(PersonneLien::class)
            ->findBy(['uuid' => $data['personne']['uuid']]);
        if (isset($lien[0])) {
            $lien[0]->setQualite((int) $data['personne']['qualite']);
            if (isset($data['personne']['actif'])) {
                $lien[0]->setActive($data['personne']['actif']);
            } else {
                $lien[0]->setActive(0);
            }
            $fonction = $this->em->getRepository(PersonneLienFonction::class)
                ->findBy(['uuid' => $data['personne']['fonction']]);
            if (isset($fonction[0])) {
                $lien[0]->setFonction($fonction[0]);
            }
            $lien[0]->setFonctionPersonnalisee($data['personne']['fonctionPersonnalisee']);
            $lien[0]->setVisibilite($data['personne']['visibilite']);
            $lien[0]->setDateModification(new DateTime());
            $lien[0]->setUserModification($this->tokenStorage->getToken()->getUser());
            $statut = $this->em->getRepository(PersonneStatut::class)
                ->findBy(['libelle' => $data['personne']['statut']]);
            $lien[0]->setStatut($statut[0]);

            if ($lien[0]->getPersonnePhysique()) {
                $this->personnePhysiqueService->update($lien[0], $data);
            } else {
                $this->personneMoralService->update($lien[0], $data);
            }
            $this->personneLienRepository->updateIntervenants($lien[0], $data);
            $this->updateChamps($lien[0], $data);
            if (isset($data['personne']['coordonnees'])) {
                $this->coordonneesService->updatePersonneCoordonnees($lien[0], $data['personne']['coordonnees']);
            }
            if (isset($data['personne']['adresses'])) {
                $this->adresseService->updatePersonneAdresses($lien[0], $data['personne']['adresses']);
            }
            $this->memoService->update($lien[0], $data['personne']['memos']);
            $this->insertTags([
                'uuid' => $lien[0]->getUuid()->toString(),
                'tags' => $data['personne']['tags'],
            ]);
            $this->em->persist($lien[0]);
            $this->em->flush();

            return $data['personne']['uuid'];
        }

        return null;
    }

    /**
     * Création d'un lien intervenant entre une entité personne et un user.
     *
     * @param $lien
     * @param $user
     * @param $type
     *
     * @throws \Exception
     */
    public function addIntervenant($lien, $user, $type)
    {
        $lienIntervenant = new LienIntervenant();
        $lienIntervenant->setUuid(Uuid::uuid4());
        $lienIntervenant->setLien($lien);
        $lienIntervenant->setUser($user);
        $lienIntervenant->setType($type);
        $this->em->persist($lienIntervenant);
    }

    /**
     * Création d'une personne.
     *
     * @param $data
     *
     * @return array|string[]
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function create($data)
    {
        //Vérification des champs manquants (ceux requis par CHAMPS_REQUIS)
        $manquants = '';
        if ('physique' == $data['personne']['type']) {
            $manquants = $this->personnePhysiqueService->getChampsManquants($data['personne']);
        }
        if ('morale' == $data['personne']['type']) {
            $manquants = $this->personneMoralService->getChampsManquants($data['personne']);
        }
        if ('lien' == $data['personne']['type']) {
            $manquants = $this->personneLienService->getChampsManquants($data['personne']);
        }
        if (count($manquants) > 0) {
            $message = 'Champs requis :';
            $i = 0;
            foreach ($manquants as $champs) {
                if (0 !== $i) {
                    $message .= ', ';
                }
                $message .= $champs;
                ++$i;
            }

            return ['erreur' => $message];
        }

        //Création des entités personne physique, personne morale, personne lien
        // et du lien intervenant (créateur) entre eux
        $personnePhysique = null;
        $personneMorale = null;
        if ('physique' == $data['personne']['type']) {
            $personnePhysique = $this->personnePhysiqueService->add($data);
        }
        if ('morale' == $data['personne']['type']) {
            $personneMorale = $this->personneMoralService->add($data);
        }
        $lien = $this->personneLienService->add($data, $personnePhysique, $personneMorale);
        $this->addIntervenant($lien, $this->tokenStorage->getToken()->getUser(), self::INTER_CREATEUR);

        $this->em->flush();

        //Ajout du lien pour le référent
        if (isset($data['personne']['referent']) && !empty($data['personne']['referent'])) {
            $referentLien = $this->em->getRepository(PersonneLien::class)
                ->findBy(['uuid' => $data['personne']['referent']]);
            $user = $referentLien[0]->getPersonnePhysique()->getUser();
            $this->addIntervenant($lien, $user, self::INTER_REFERENT);
        }

        //Ajout du lien pour les intervenants
        if (isset($data['personne']['intervenants']) && !empty($data['personne']['intervenants'])) {
            foreach ($data['personne']['intervenants'] as $intervenant) {
                $referentLien = $this->em->getRepository(PersonneLien::class)
                    ->findBy(['uuid' => $intervenant]);
                $user = $referentLien[0]->getPersonnePhysique()->getUser();
                $this->addIntervenant($lien, $user, self::INTER_INTERVENANT);
            }
        }

        //Ajout de l email et du lien
        if (isset($data['personne']['email']) && !empty($data['personne']['email'])) {
            $this->coordonneesService->emailService->add(
                $lien->getUuid()->toString(),
                $data['personne']['email'],
                '1',
                true
            );
        }

        //Ajout du téléphone du lien
        if (isset($data['personne']['telephone']) && !empty($data['personne']['telephone'])) {
            $this->coordonneesService->telephoneService->add(
                $lien->getUuid()->toString(),
                $data['personne']['telephone'],
                $data['personne']['indicatifTel'],
                '1',
                true
            );
        }

        //Ajout du memo et du lien
        if (isset($data['personne']['memo']) && !empty($data['personne']['memo'])) {
            $this->memoService->add([
                'uuid' => $lien->getUuid()->toString(),
                'texte' => $data['personne']['memo'],
                'persist' => true,
            ]);
        }

        return ['uuid' => $lien->getUuid()->toString()];
    }

    /**
     * Création d'une personne Fonction.
     *
     * @param $data
     *
     * @return array|string[]
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function createPersonneFonction($data)
    {
        $morale = null;
        $physique = null;
        if (isset($data['personne']['physique'])) {
            /** @var PersonneLien[] $lienPhysique */
            $lienPhysique = $this->em->getRepository(PersonneLien::class)
                ->findBy(['uuid' => $data['personne']['physique']]);
            if (isset($lienPhysique[0])) {
                $data['personne']['statut'] = $lienPhysique[0]->getStatut()->getLibelle();
                $data['personne']['visibilite'] = $lienPhysique[0]->getVisibilite();
                $data['personne']['actif'] = $lienPhysique[0]->getActive();
                $physique = $lienPhysique[0]->getPersonnePhysique();
            }
        }
        if (isset($data['personne']['morale'])) {
            /** @var PersonneLien[] $lienMorale */
            $lienMorale = $this->em->getRepository(PersonneLien::class)
                ->findBy(['uuid' => $data['personne']['morale']]);
            if (isset($lienMorale[0])) {
                $morale = $lienMorale[0]->getPersonneMorale();
            }
        }
        $lien = $this->personneLienService->add($data, $physique, $morale);
        $this->em->flush();

        return ['uuid' => $lien->getUuid()->toString()];
    }

    public function getChamps($uuid)
    {
        /** @var PersonneLien[] $lien */
        $lien = $this->em->getRepository(PersonneLien::class)
            ->findBy(['uuid' => $uuid]);
        $data = null;
        if ($lien[0]) {
            /** @var LienChamp $champ */
            foreach ($lien[0]->getChamps() as $champ) {
                $item = [];
                $item['uuid'] = $champ->getChamp()->getUuid()->toString();
                $item['valeur'] = json_decode($champ->getValeur());
                $data[] = $item;
            }
        }

        return $data;
    }

    /**
     * Mets à jours les champs personnalisés d'une personne.
     *
     * @param $lien
     * @param $data
     */
    public function updateChamps($lien, $data)
    {
        $champsExistants = $this->em->getRepository(LienChamp::class)
            ->findBy(['lien' => $lien]);
        foreach ($data['personne']['personnalises'] as $item) {
            /** @var Champ[] $champ */
            $champ = $this->em->getRepository(Champ::class)->findBy(['uuid' => $item['id']]);
            if (isset($champ[0])) {
                /** @var LienChamp[] $lienChamp */
                $lienChamp = $this->em->getRepository(LienChamp::class)
                    ->findBy(['lien' => $lien, 'champ' => $champ[0]]);
                if (isset($lienChamp[0])) {
                    $lienChamp[0]->setValeur(json_encode($item['value']));
                    $this->em->persist($lienChamp[0]);
                    if (($key = array_search($lienChamp[0], $champsExistants)) !== false) {
                        unset($champsExistants[$key]);
                    }
                } else {
                    $nouveau = new LienChamp();
                    $nouveau->setUuid(Uuid::uuid4());
                    $nouveau->setLien($lien);
                    $nouveau->setChamp($champ[0]);
                    $nouveau->setValeur(json_encode($item['value']));
                    $this->em->persist($nouveau);
                }
            }
        }
        foreach ($champsExistants as $champ) {
            $this->em->remove($champ);
        }
    }
}
