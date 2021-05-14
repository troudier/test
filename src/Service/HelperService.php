<?php

namespace App\Service;

use App\Entity\LienIntervenant;
use App\Entity\PersonneLien;
use App\Entity\PersonnePhysique;
use App\Entity\Tag;
use App\Entity\User;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;

class HelperService
{

    public $entityMapping = [
        'personne' => [
            'entity' => 'App\\Entity\\PersonneLien',
            'lien' => 'App\\Entity\\LienIntervenant'
            ],
        'segment' => [
            'entity' => 'App\\Entity\\Segment',
            'lien' => 'App\\Entity\\SegmentIntervenant'
        ],
        'Type' => [
            'entity' => 'App\\Entity\\PersonneStatut'
        ],
        'Titre' => [
            'entity' =>  'App\\Entity\\Titre'
        ],
        'Tag' => [
            'entity' =>  'App\\Entity\\Tag'
        ],
        'Fonction' => [
            'entity' =>  'App\\Entity\\PersonneLienFonction'
        ],
        "Chiffre d'affaire" => [
            'entity' =>  'App\\Entity\\ChiffreAffaire'
        ],
        "Effectif" => [
            'entity' =>  'App\\Entity\\Effectif'
        ],
        "Forme Juridique" => [
            'entity' =>  'App\\Entity\\FormeJuridique'
        ],
        "Origine" => [
            'entity' =>  'App\\Entity\\Origine'
        ]
    ];


    /**
     *
     * @param EntityManagerInterface $em
     */

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * Récupère les intervenants d'une personne
     *
     * @param $uuid
     * @param $type
     * @return array
     */
    public function getListeIntervenants($uuid, $type)
    {

        $result = [];
        if (
            !is_string($uuid)
            ||
            (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $uuid) !== 1)
        ) {
            return $result;
        }
        /** @var PersonneLien[] $personne */
        $personne = $this->em->getRepository($this->entityMapping[$type]['entity'])
            ->findBy(['uuid' => $uuid]);
        if (isset($personne[0])) {
            $intervenants = $personne[0]->getIntervenants();
            foreach ($intervenants as $intervenant) {
                $item = [];
                $item['type'] = $intervenant->getType();
                /** @var User $user */
                $user = $intervenant->getUser();
                $personnePhysique = $this->em->getRepository(PersonnePhysique::class)
                    ->findBy(['user' => $user]);
                $item['libelle'] = $personnePhysique[0]->getPrenom() . " " . $personnePhysique[0]->getNom();
                $lien = $this->em->getRepository(PersonneLien::class)
                    ->findBy(['personnePhysique' => $personnePhysique, 'type' => 'physique']);
                $item['uuid'] = $lien[0]->getUuid()->toString();
                $result[] = $item;
            }
        }
        return $result;
    }

    /**
     * Renvoie la liste des valeurs ( sous la forme [uuid, libelle]) d'une entité
     *
     * @param $type
     * @return array
     */
    public function getDictionnaire($type){
        $result = [];
        $data = $this->em->getRepository($this->entityMapping[$type]['entity'])
            ->findAll();
        foreach($data as $object){
            $result[] = [
                'uuid' => $object->getUuid()->toString(),
                'libelle' => $object->getTexte()
            ];
        }
        return $result;
    }

}