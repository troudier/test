<?php

namespace App\Repository;

use App\Entity\PersonneLien;
use App\Entity\PersonnePhysique;
use App\Entity\Segment;
use App\Entity\SegmentFiltre;
use App\Entity\SegmentFiltreValeur;
use App\Entity\SegmentIntervenant;
use App\Entity\User;
use App\Service\HelperService;
use App\Service\Segment\SegmentService;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class SegmentRepository extends ServiceEntityRepository
{
    private TokenStorageInterface $tokenStorage;

    private Connection $connexion;

    private EntityManagerInterface $em;

    private HelperService $helperService;

    private SegmentService $segmentService;

    private $champOrder = [
        'titre' => 'titre',
    ];

    private $champsRecherche = [
        'seg.seg_titre',
        'pp.pp_nom',
        'pp.pp_prenom',
    ];

    private $type_input = [
        1 => 'string',
        2 => 'int',
        11 => 'select2',
    ];

    public function __construct(
        ManagerRegistry $registry,
        EntityManagerInterface $em,
        TokenStorageInterface $tokenStorage,
        HelperService $helperService,
        SegmentService $segmentService
    ) {
        parent::__construct($registry, PersonneLien::class);
        $this->em = $em;
        $this->connexion = $this->em->getConnection();
        $this->tokenStorage = $tokenStorage;
        $this->helperService = $helperService;
        $this->segmentService = $segmentService;
    }

    /**
     * Prépare le SQL pour filter la liste des segments.
     *
     * @return string
     */
    public function setFiltres(array $filtes)
    {
        $sqlPart = '';
        foreach ($filtes as $filtre => $valeur) {
            $sqlPart = empty($sqlPart) ? 'WHERE (' : $sqlPart.'AND (';
            switch ($filtre) {
                case 'recherche':
                    foreach ($this->champsRecherche as $i => $champs) {
                        foreach ($valeur as $j => $mot) {
                            $sqlPart .= (0 == $i) && (0 == $j) ? '' : 'OR ';
                            $sqlPart .= ' '.$champs." LIKE '%".$mot."%' ";
                        }
                    }
                    $sqlPart .= ') ';
                    break;
            }
        }
        //Gestion de la visibilité
        $userId = $this->tokenStorage->getToken()->getUser()->getId();
        $sqlPart = empty($sqlPart) ? 'WHERE (' : $sqlPart.'AND (';
        $sqlPart .= 'seg_active = 1) AND (';
        $sqlPart .= '(seg_visibilite < 2) OR (user_creation.id = '.$userId.')';

        $sqlPart .= ') ';

        return $sqlPart;
    }

    /**
     * Récupère la liste des segments, filtrées et paginée.
     *
     * @param $query
     *
     * @return \Doctrine\DBAL\Statement
     *
     * @throws Exception
     */
    public function prepareCartesRequete($query)
    {
        $filtres = [];

        $recherche = $query->has('recherche') ? addslashes($query->get('recherche')) : null;
        if (!empty($recherche)) {
            $filtres['recherche'] = explode(' ', $recherche);
        }
        $swhereQuery = $this->setFiltres($filtres);
        $slimitQuery = '';
        $limit = $query->has('limit') ? (int) $query->get('limit') : null;
        $offset = $query->has('offset') ? (int) $query->get('offset') : null;
        if (null !== $limit && null !== $offset) {
            $slimitQuery = ' LIMIT '.$offset.','.$limit;
        }
        $sql = 'SELECT  
                       distinct(seg.seg_uuid) as uuid,
                       seg.seg_titre as titre,
                       seg.seg_nb_contacts_publics as nbContactsPublics, 
                       seg.seg_nb_contacts_prives as nbContactsPrives,                                          
                       seg.seg_derniere_date_execution as derniereDate,
                       pp.pp_prenom as prenom,
                       pp.pp_nom as nom
                 FROM segment as seg
                 LEFT JOIN segment_intervenant as seg_int on seg_int.segment_id = seg.id  and seg_int.si_type = 1 
                 LEFT JOIN personne_physique as pp on pp.user_id = seg_int.user_id
                 LEFT JOIN user as user_creation on user_creation.id = seg.user_creation_id
                  '.$swhereQuery.'   ORDER BY '.$this->champOrder['titre'].' ASC'.$slimitQuery;

        return $this->connexion->prepare($sql);
    }

    /**
     * Revnoie les infomrations pour afficher la fiche d'un segment.
     *
     * @param $uuid
     *
     * @return array
     */
    public function getFiche($uuid)
    {
        /** @var Segment[] $segment */
        $segment = $this->em->getRepository(Segment::class)->findBy(['uuid' => $uuid]);
        $data = [];
        if (isset($segment[0])) {
            $data['uuid'] = $segment[0]->getUuid()->toString();
            $data['titre'] = $segment[0]->getTitre();
            $data['nbContactsPublics'] = $segment[0]->getNbContactsPublics();
            $data['nbContactsPrives'] = $segment[0]->getNbContactsPrives();
            $data['actif'] = $segment[0]->getActive();
            $data['derniereDate'] =
                $segment[0]->getDerniereDateExecution()
                    ? $segment[0]->getDerniereDateExecution()->format('Y-m-d H:i:s')
                    : null;
            $data['creationDate'] = $segment[0]->getDateCreation()->format('Y-m-d H:i:s');
            $data['modificationDate'] = $segment[0]->getDateModification()->format('Y-m-d H:i:s');
            $data['modificationUser'] = $this->segmentService->getSegmentUser($segment[0]->getUserCreation());
            $data['creationUser'] = $this->segmentService->getSegmentUser($segment[0]->getUserModification());
            $data['visibilite'] = (string) $segment[0]->getVisibilite();
            /** @var SegmentIntervenant $intervenant */
            foreach ($segment[0]->getIntervenants() as $intervenant) {
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
                if (isset($data['intervenants'])) {
                    $data['intervenants'][] = $item;
                } else {
                    $data['intervenants'] = [$item];
                }
            }
            $filtres = [];
            /** @var SegmentFiltre $item */
            foreach ($segment[0]->getFiltres() as $item) {
                $filtres[$item->getOrdre()]['uuid'] = $item->getUuid()->toString();
                $filtres[$item->getOrdre()]['ordre'] = (int) $item->getOrdre();
                $filtres[$item->getOrdre()]['champ']['uuid'] = $item->getChamp()->getUuid()->toString();
                $filtres[$item->getOrdre()]['champ']['type'] =
                    $this->type_input[(int) $item->getChamp()->getTypeInput()];
                $filtres[$item->getOrdre()]['champ']['libelle'] = $item->getChamp()->getLibelle();
                $filtres[$item->getOrdre()]['operateur']['uuid'] = $item->getOperateur()->getUuid()->toString();
                $filtres[$item->getOrdre()]['operateur']['libelle'] = $item->getOperateur()->getLibelle();
                $filtres[$item->getOrdre()]['operateur']['nbValeurs'] = $item->getOperateur()->getNbValeurs();
                $filtres[$item->getOrdre()]['valeurs'] = [];
                /** @var SegmentFiltreValeur $valeur */
                foreach ($item->getValeurs() as $valeur) {
                    if ('select2' == $this->type_input[(int) $item->getChamp()->getTypeInput()]) {
                        $valeurArray = [];
                        foreach (json_decode($valeur->getValeur()) as $val) {
                            $object = $this->em->getRepository(
                                $this->helperService->entityMapping[$item->getChamp()->getLibelle()]['entity']
                            )->findBy(['uuid' => $val]);
                            if (isset($object[0])) {
                                $valeurArray[] = $object[0]->getTexte();
                            }
                        }
                        $filtres[$item->getOrdre()]['valeurs'][] = $valeurArray;
                    } else {
                        $filtres[$item->getOrdre()]['valeurs'][] = json_decode($valeur->getValeur());
                    }
                }
                $data['filtres'] = $filtres;
            }
        }

        return $data;
    }
}
