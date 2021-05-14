<?php

namespace App\Service\Personne;

use App\Entity\ChiffreAffaire;
use App\Entity\Effectif;
use App\Entity\FormeJuridique;
use App\Entity\PersonneMorale;
use App\Entity\Titre;
use DateTime;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class PersonneMoraleService
{
    public const CHAMPS_REQUIS = [
        'type' => 'Type',
        'raisonSociale' => 'Raison sociale',
        'formeJuridique' => 'Forme juridique',
    ];

    public const CHAMPS_RECOMMANDE = [
        'email' => 'E-mail',
        'telephone' => 'Tél.',
    ];

    /**
     * @var Connection
     */
    private Connection $connexion;

    /**
     *
     * @param EntityManagerInterface $em
     */
    private EntityManagerInterface $em;

    /**
     * @var TokenStorageInterface
     */
    private TokenStorageInterface $tokenStorage;

    /**
     * @var string[]
     */
    private array $civiliteMapping = [
        'M' => 'M',
        'Mme' => 'Mme'
    ];

    public function __construct(
        EntityManagerInterface $em,
        TokenStorageInterface $tokenStorage
    )
    {
        $this->em = $em;
        $this->connexion = $this->em->getConnection();
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * Vérifie si les champs requis pour créer une personne physique sont présents
     *
     * @param array $data
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
     * Crée une personne morale
     *
     * @param $data
     * @return PersonneMorale
     * @throws \Exception
     */
    public function add($data)
    {
        $personne = new PersonneMorale();
        //Uuid généré
        $personneUuid = Uuid::uuid4();
        $personne->setUuid($personneUuid);
        //Champs requis
        $personne->setRaisonSociale($data['personne']['raisonSociale']);
        $fj = $this->em->getRepository(FormeJuridique::class)->findBy(['uuid' => $data['personne']['formeJuridique']]);
        if (isset($fj[0])) {
            $personne->setFormeJuridique($fj[0]);
        }
        //Autres champs
        $personne->setVisibilite($data['personne']['visibilite']);
        //Champs communs de logs
        $personne->setDateCreation(new DateTime());
        $personne->setUserCreation($this->tokenStorage->getToken()->getUser());
        $personne->setDateModification(new DateTime());
        $personne->setUserModification($this->tokenStorage->getToken()->getUser());
        //De base n'est pas lié à un user de l'application
        $this->em->persist($personne);
        return $personne;
    }

    /**
     * Mise à jour d'une personne morale
     *
     * @param $data
     */
    public function update($lien, $data)
    {
        $personne = $lien->getPersonneMorale();
        $personne->setRaisonSociale($data["personne"]["raisonSociale"]);
        $forme_juridique = $this->em->getRepository(FormeJuridique::class)
            ->findBy(['uuid' => $data["personne"]["formeJuridique"]]);
        if (isset($forme_juridique[0])) {
            $personne->setFormeJuridique($forme_juridique[0]);
        }
        $personne->setSiret($data["personne"]["siret"]);
        $personne->setCodeNaf($data["personne"]["codeNaf"]);
        $personne->setCapital($data["personne"]["capital"]);
        $effectif = $this->em->getRepository(Effectif::class)
            ->findBy(['uuid' => $data["personne"]["effectif"]]);
        if (isset($effectif[0])) {
            $personne->setEffectif($effectif[0]);
        }
        $chiffre_affaire = $this->em->getRepository(ChiffreAffaire::class)
            ->findBy(['uuid' => $data["personne"]["chiffreAffaire"]]);
        if (isset($chiffre_affaire[0])) {
            $personne->setChiffreAffaire($chiffre_affaire[0]);
        }
        $personne->setParent($data["personne"]["organisationParente"]);
        $organisation_parente = $this->em->getRepository(PersonneMorale::class)
            ->findBy(['uuid' => $data["personne"]["organisationParente"]]);
        if (isset($organisation_parente[0])) {
            $personne->setParent($organisation_parente[0]);
        }
        if (isset($this->civiliteMapping[$data["personne"]["civilite"]])) {
            $personne->setCivilite($this->civiliteMapping[$data["personne"]["civilite"]]);
        }
        $titre = $this->em->getRepository(Titre::class)
            ->findBy(['libelle' => $data["personne"]["titre"]]);
        if (isset($titre[0])) {
            $personne->setTitre($titre[0]->getLibelle());
        }
        if (isset($data['personne']['visibilite'])) {
            $personne->setVisibilite($data['personne']['visibilite']);
        }
        $personne->setDateModification(new DateTime());
        $personne->setUserModification($this->tokenStorage->getToken()->getUser());
        $this->em->persist($personne);
    }

    /**
     * Récupère les formes juridiques
     *
     * @param $query
     * @return \Doctrine\DBAL\Statement
     * @throws \Doctrine\DBAL\Exception
     */
    public function prepareListeFormeJuridique($query): \Doctrine\DBAL\Statement
    {
        $sql = 'SELECT fjud_libelle as fjlib, fjud_uuid as uuid FROM forme_juridique';
        return $this->connexion->prepare($sql);
    }

    /**
     * Récupère les effectifs
     *
     * @param $query
     * @return \Doctrine\DBAL\Statement
     * @throws \Doctrine\DBAL\Exception
     */
    public function prepareListeEffectif($query): \Doctrine\DBAL\Statement
    {
        $sql = 'SELECT eff_libelle as eff_lib, eff_uuid FROM effectif';
        return $this->connexion->prepare($sql);
    }

    /**
     * Récupère les chiffres d'affaires
     *
     * @param $query
     */
    public function prepareListeChiffreAffaire($query)
    {
        $sql = 'SELECT ca_libelle as ca_lib, ca_uuid FROM chiffre_affaire';
        return $this->connexion->prepare($sql);
    }

    /**
     * Récupère les personnes morales sans la PM courante
     *
     * @param $query
     */
    public function prepareListeOrganisationParente($query): \Doctrine\DBAL\Statement
    {
        $sql = 'SELECT pm_raison_sociale as organisation_lib, pm_uuid as organisation_uuid FROM personne_morale WHERE NOT pm_uuid="8e075fa1-fba2-4b57-8de1-78305f5d70b1"';
        return $this->connexion->prepare($sql);
    }

}
