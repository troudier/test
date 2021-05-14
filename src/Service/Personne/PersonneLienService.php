<?php

namespace App\Service\Personne;

use App\Entity\PersonneLien;
use App\Entity\PersonneLienFonction;
use App\Entity\PersonneStatut;
use DateTime;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class PersonneLienService
{
    public const CHAMPS_REQUIS = [
        'Fonction' => 'Fonction',
    ];

    public const CHAMPS_RECOMMANDE = [
    ];

    /**
     * @var Connection
     */
    private $connexion;

    /**
     *
     * @param EntityManagerInterface $em
     */
    private $em;

    private $tokenStorage;


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
     * Vérifie si les champs requis pour créer une personne fonction sont présents
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

    public function add($data, $personnePhysique = null, $personneMorale = null){
        $lien = new PersonneLien();
        $lienUuid =  Uuid::uuid4();
        $lien->setUuid($lienUuid);
        if($personnePhysique && $personneMorale){
            $lien->setPersonnePhysique($personnePhysique);
            $lien->setPersonneMorale($personneMorale);
            $lien->setLibelle($personnePhysique->getNom());
            $lien->setType('lien');

        }elseif($personnePhysique){
            $lien->setPersonnePhysique($personnePhysique);
            $lien->setLibelle($personnePhysique->getNom());
            $lien->setType('physique');
        }elseif($personneMorale){
            $lien->setPersonneMorale($personneMorale);
            $lien->setLibelle($personneMorale->getRaisonSociale());
            $lien->setType('morale');
        }
        if(isset($data['personne']['fonction'])){
            $fonction = $this->em->getRepository(PersonneLienFonction::class)
                ->findBy(['uuid' => $data['personne']['fonction']]);
            if(isset($fonction[0])){
                $lien->setFonction($fonction[0]);
            }
        }
        if(isset($data['personne']['statut'])){

            $status = $this->em->getRepository(PersonneStatut::class)
                ->findBy(['libelle' => $data['personne']['statut']]);
            $lien->setStatut($status[0]);
        }
        $lien->setReferent(false);
        if(isset($data['personne']['actif'])){
            $lien->setActive($data['personne']['actif']);
        }else{
            $lien->setActive(0);
        }
        $lien->setQualite(0);
        $lien->setVisibilite($data['personne']['visibilite']);
        $lien->setDateCreation(new DateTime());
        $lien->setUserCreation($this->tokenStorage->getToken()->getUser());
        $lien->setDateModification(new DateTime());
        $lien->setUserModification($this->tokenStorage->getToken()->getUser());
        $this->em->persist($lien);
        return $lien;
    }

}
