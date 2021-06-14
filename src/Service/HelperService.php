<?php

namespace App\Service;

use App\Entity\ChampRequetable;
use App\Entity\LienMail;
use App\Entity\Operateur;
use App\Entity\PersonneLien;
use App\Entity\PersonnePhysique;
use App\Entity\SegmentIntervenant;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;

class HelperService
{
    public $entityMapping = [
        'personne' => [
            'entity' => 'App\\Entity\\PersonneLien',
            'lien' => 'App\\Entity\\LienIntervenant',
        ],
        'segment' => [
            'entity' => 'App\\Entity\\Segment',
            'lien' => 'App\\Entity\\SegmentIntervenant',
        ],
        'Type' => [
            'entity' => 'App\\Entity\\PersonneStatut',
        ],
        'Titre' => [
            'entity' => 'App\\Entity\\Titre',
        ],
        'Tag' => [
            'entity' => 'App\\Entity\\Tag',
        ],
        'Fonction' => [
            'entity' => 'App\\Entity\\PersonneLienFonction',
        ],
        "Chiffre d'affaire" => [
            'entity' => 'App\\Entity\\ChiffreAffaire',
        ],
        'Effectif' => [
            'entity' => 'App\\Entity\\Effectif',
        ],
        'Forme Juridique' => [
            'entity' => 'App\\Entity\\FormeJuridique',
        ],
        'Origine' => [
            'entity' => 'App\\Entity\\Origine',
        ],
    ];

    private $type_input = [
        1 => 'string',
        2 => 'int',
        11 => 'select2',
    ];

    private EntityManagerInterface $em;

    private \Doctrine\DBAL\Connection $connexion;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
        $this->connexion = $this->em->getConnection();
    }

    /**
     * Récupère les intervenants d'une personne.
     *
     * @param $uuid
     * @param $type
     *
     * @return array
     */
    public function getListeIntervenants($uuid, $type)
    {
        $result = [];
        if (!is_string($uuid)
            ||
            (1 !== preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $uuid))
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
                $item['libelle'] = $personnePhysique[0]->getPrenom().' '.$personnePhysique[0]->getNom();
                $lien = $this->em->getRepository(PersonneLien::class)
                    ->findBy(['personnePhysique' => $personnePhysique, 'type' => 'physique']);
                $item['uuid'] = $lien[0]->getUuid()->toString();
                $result[] = $item;
            }
        }

        return $result;
    }

    /**
     * Renvoie la liste des valeurs ( sous la forme [uuid, libelle]) d'une entité.
     *
     * @param $type
     *
     * @return array
     */
    public function getDictionnaire($type)
    {
        $result = [];
        $data = $this->em->getRepository($this->entityMapping[$type]['entity'])
            ->findAll();
        foreach ($data as $object) {
            $result[] = [
                'uuid' => $object->getUuid()->toString(),
                'libelle' => $object->getTexte(),
            ];
        }

        return $result;
    }

    public function getDateFormatee($date)
    {
        return $date ? $date->format('Y-m-d H:i:s') : null;
    }

    public function preparePersonnesFonctions()
    {
        $sql = 'SELECT  
                 plf.plf_libelle as libelle,
                 plf.plf_uuid as uuid
                 FROM personne_lien_fonction as plf
                  order by libelle asc;';

        return $this->connexion->prepare($sql);
    }

    public function preparePersonnesSelect($type)
    {
        if ('morale' === $type) {
            $sql = 'SELECT  
                       distinct(pl.pl_uuid) as uuid,
                       pm.pm_raison_sociale as libelle
                 FROM personne_lien as pl
                 LEFT JOIN personne_morale as pm on pm.id = pl.personne_morale_id
                 WHERE pl.pl_type = "'.$type.'"';
        } elseif ('physique' === $type) {
            $sql = 'SELECT  
                       distinct(pl.pl_uuid) as uuid,
                       CONCAT(pp.prenom, " ", pp.nom) as libelle
                 FROM personne_lien as pl
                 LEFT JOIN personne_physique as pp on pp.id = pl.personne_physique_id
                 WHERE pl.pl_type = "'.$type.'"';
        }

        return $this->connexion->prepare($sql);
    }

    /**
     * Création d'un lien intervenant entre une entité segment et un user.
     *
     * @param $segment
     * @param $user
     * @param $type
     *
     * @throws \Exception
     */
    public function addIntervenantSegment($segment, $user, $type)
    {
        $segmentIntervenant = new SegmentIntervenant();
        $segmentIntervenant->setUuid(Uuid::uuid4());
        $segmentIntervenant->setSegment($segment);
        $segmentIntervenant->setUser($user);
        $segmentIntervenant->setType($type);
        $this->em->persist($segmentIntervenant);
    }

    public function printResultats($uuids)
    {
        $data = [
            'resultats' => [],
        ];
        $count = [
            'public' => 0,
            'prive' => 0,
        ];
        foreach ($uuids as $uuid) {
            $lien = $this->em->getRepository(PersonneLien::class)->findBy(['uuid' => $uuid]);
            if ((int) $lien[0]->getVisibilite() < 3) {
                ++$count['public'];
            } else {
                ++$count['prive'];
            }
            $item = [];
            $item['type'] = $lien[0]->getType();

            switch ($lien[0]->getType()) {
                case 'physique':
                    $item['libelle'] =
                        $lien[0]->getPersonnePhysique()->getPrenom().
                        ' '.
                        $lien[0]->getPersonnePhysique()->getNom();
                    break;
                case 'morale':
                    $item['libelle'] =
                        $lien[0]->getPersonneMorale()->getRaisonSociale().
                        ' ('.
                        $lien[0]->getPersonneMorale()->getFormeJuridique()->getLibelle().
                        ')';
                    break;
                case 'lien':
                    $item['libelle'] =
                        $lien[0]->getPersonnePhysique()->getPrenom().
                        ' '.
                        $lien[0]->getPersonnePhysique()->getNom().
                        ', '.
                        $lien[0]->getPersonneMorale()->getRaisonSociale().
                        ' ('.
                        $lien[0]->getPersonneMorale()->getFormeJuridique()->getLibelle().
                        ')';
                    break;
            }
            $item['email'] = '-';
            /** @var LienMail $lienMail */
            foreach ($lien[0]->getMails() as $lienMail) {
                if ($lienMail->getPrincipal()) {
                    $item['email'] = $lienMail->getMail()->getValeur();
                }
            }

            $data['resultats'][] = $item;
        }
        $data['count'] = $count;

        return $data;
    }

    public function getChamps()
    {
        $data = [];
        $listeChamps = $this->em->getRepository(ChampRequetable::class)->findAll();
        /** @var ChampRequetable $champs */
        foreach ($listeChamps as $champs) {
            $item = [];
            $item['uuid'] = $champs->getUuid()->toString();
            $item['text'] = $champs->getLibelle();
            $item['type'] = $this->type_input[(int) $champs->getTypeInput()];
            $data[] = $item;
        }

        return $data;
    }

    public function getOperateurs()
    {
        $data = [];
        $listeChamps = $this->em->getRepository(Operateur::class)->findAll();
        /** @var Operateur $champs */
        foreach ($listeChamps as $champs) {
            $item = [];
            $item['uuid'] = $champs->getUuid()->toString();
            $item['text'] = $champs->getLibelle();
            $item['nbValeurs'] = $champs->getNbValeurs();
            $data[] = $item;
        }

        return $data;
    }
}
