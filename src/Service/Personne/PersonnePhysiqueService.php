<?php

namespace App\Service\Personne;

use App\Entity\LienIntervenant;
use App\Entity\Origine;
use App\Entity\PersonnePhysique;
use App\Entity\Titre;
use DateTime;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class PersonnePhysiqueService
{
    public const CHAMPS_REQUIS = [
        'type' => 'Type',
        'civilite' => 'Civilité',
        'prenom' => 'Prénom',
        'nom' => 'Nom',
        'visibilite' => 'Visibilité',
    ];

    public const CHAMPS_RECOMMANDE = [
        'email' => 'E-mail',
        'telephone' => 'Tél.',
    ];

    /**
     * @var Connection
     */
    private $connexion;

    /**
     * @param EntityManagerInterface $em
     */
    private $em;

    private $tokenStorage;

    private $civiliteMapping = [
        'M' => 'M',
        'Mme' => 'Mme',
    ];

    public function __construct(
        EntityManagerInterface $em,
        TokenStorageInterface $tokenStorage
    ) {
        $this->em = $em;
        $this->connexion = $this->em->getConnection();
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * Crée une personne physique.
     *
     * @param $data
     *
     * @return PersonnePhysique
     *
     * @throws \Exception
     */
    public function add($data)
    {
        $personne = new PersonnePhysique();
        //Uuid généré
        $personneUuid = Uuid::uuid4();
        $personne->setUuid($personneUuid);
        //Champs requis
        $personne->setPrenom($data['personne']['prenom']);
        $personne->setNom($data['personne']['nom']);
        $personne->setCivilite($this->civiliteMapping[$data['personne']['civilite']]);
        if (isset($data['personne']['infoCommerciale'])) {
            $personne->setInfoCommerciale($data['personne']['infoCommerciale']);
        } else {
            $personne->setInfoCommerciale(false);
        }
        //Autres champs
        $personne->setVisibilite($data['personne']['visibilite']);
        //Champs communs de logs
        $personne->setDateCreation(new DateTime());
        $personne->setUserCreation($this->tokenStorage->getToken()->getUser());
        $personne->setDateModification(new DateTime());
        $personne->setUserModification($this->tokenStorage->getToken()->getUser());
        //De base n'est pas lié à un user de l'application
        $personne->setisUser(false);
        $this->em->persist($personne);

        return $personne;
    }

    /**
     * Mise à jour d'une personne physique.
     *
     * @param $data
     */
    public function update($lien, $data)
    {
        $personne = $lien->getPersonnePhysique();
        $personne->setPrenom($data['personne']['prenom']);
        $personne->setNom($data['personne']['nom']);
        if (isset($data['personne']['infoCommerciale'])) {
            $personne->setInfoCommerciale($data['personne']['infoCommerciale']);
        }
        if (isset($this->civiliteMapping[$data['personne']['civilite']])) {
            $personne->setCivilite($this->civiliteMapping[$data['personne']['civilite']]);
        }
        $titre = $this->em->getRepository(Titre::class)
            ->findBy(['libelle' => $data['personne']['titre']]);
        if (isset($titre[0])) {
            $personne->setTitre($titre[0]->getLibelle());
        }
        if (isset($data['personne']['apporteur'])) {
            $apporteur = $this->em->getRepository(PersonnePhysique::class)
                ->findBy(['uuid' => $data['personne']['apporteur']]);
            $personne->setApporteur($apporteur[0]);
        } else {
            $personne->setApporteur(null);
        }
        if (isset($data['personne']['origine'])) {
            $origine = $this->em->getRepository(Origine::class)
                ->findBy(['uuid' => $data['personne']['origine']]);
            $personne->setOrigine($origine[0]);
        } else {
            $personne->setOrigine(null);
        }
        if (isset($data['personne']['visibilite'])) {
            $personne->setVisibilite($data['personne']['visibilite']);
        }
        $personne->setDateModification(new DateTime());
        $personne->setUserModification($this->tokenStorage->getToken()->getUser());
        $this->em->persist($personne);
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
        $add = true;
        $found = $this->em->getRepository(LienIntervenant::class)
            ->findBy(['lien' => $lien, 'type' => $type]);
        /** @var LienIntervenant $item */
        foreach ($found as $item) {
            //Si c'est le même on ne le supprime pas
            if ($item->getUser() !== $user) {
                $this->em->remove($item);
            }
        }
        if ($add) {
            $lienIntervenant = new LienIntervenant();
            $lienIntervenant->setUuid(Uuid::uuid4());
            $lienIntervenant->setLien($lien);
            $lienIntervenant->setUser($user);
            $lienIntervenant->setType($type);
            $this->em->persist($lienIntervenant);
        }
    }

    /**
     * Vérifie si les champs requis pour créer une personne physique sont présents.
     *
     * @param array $data
     *
     * @return string[]
     */
    public function getChampsManquants($data)
    {
        return array_diff_key(
            self::CHAMPS_REQUIS,
            array_intersect_key(
                self::CHAMPS_REQUIS,
                array_filter($data)
            )
        );
    }

    /**
     * Récupère les origines disponibles, filtrés  ou non (si systeme ou non).
     *
     * @return \Doctrine\DBAL\Statement
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function prepareListePersonnesPhysiques()
    {
        $sql = 'SELECT  
                    pp_civilite as civilite,
                    pp_uuid as uuid,
                    pp_nom as nom,
                    pp_prenom as prenom
                 FROM personne_physique ';

        return $this->connexion->prepare($sql);
    }

    /**
     * Récupère les titres disponibles.
     *
     * @return \Doctrine\DBAL\Statement
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function prepareListeTitres()
    {
        $sql = 'SELECT  
                    titre_civilite as civilite,
                    titre_uuid as uuid,
                    titre_libelle as libelle
                 FROM titre ';

        return $this->connexion->prepare($sql);
    }
}
