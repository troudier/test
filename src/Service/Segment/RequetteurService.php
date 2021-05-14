<?php

namespace App\Service\Segment;

use App\Entity\ChampRequetable;
use App\Entity\LienMail;
use App\Entity\Operateur;
use App\Entity\PersonneLien;
use App\Entity\PersonnePhysique;
use App\Entity\Segment;
use App\Entity\SegmentFiltre;
use App\Entity\SegmentFiltreValeur;
use App\Entity\SegmentIntervenant;
use App\Entity\User;
use App\Service\HelperService;
use DateTime;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class RequetteurService
{

    private $operateurParametres = [
        0 => '@X',
        1 => '@Y',
        2 => '@Z'
    ];

    private Connection $connexion;

    private EntityManagerInterface $em;

    private TokenStorageInterface $tokenStorage;

    private HelperService $helperService;

    public function __construct(
        EntityManagerInterface $em,
        TokenStorageInterface $tokenStorage,
        HelperService $helperService
    )
    {
        $this->em = $em;
        $this->connexion = $this->em->getConnection();
        $this->tokenStorage = $tokenStorage;
        $this->helperService = $helperService;
    }

    /**
     * @param SegmentFiltre[] $filtres
     */
    public function buildSegmentRequete($filtres, $index = null, $count = false, $prive = false)
    {
        $sql =
            $count
                ?
                "SELECT count(distinct(pl_uuid)) as count FROM personne_lien as pl "
                :
                "SELECT distinct(pl_uuid) as uuid, pl.pl_libelle, pl.pl_visibilite as visibilite FROM personne_lien as pl ";
        $where = '';
        $joins = [];
        $filtresOrdonnes = [];
        foreach ($filtres as $i => $filtre) {
            $filtresOrdonnes[(int)$filtre->getOrdre()] = $filtre;
        }
        ksort($filtresOrdonnes);
        foreach ($filtresOrdonnes as $i => $filtre) {
            if (!$index || $i <= (int)$index) {
                foreach (json_decode($filtre->getChamp()->getJoin(), TRUE) as $table => $join) {
                    if (!isset($joins[$table])) {
                        $joins[$table] = $join;
                    }
                }
                $operation = [];
                foreach ($filtre->getValeurs() as $j => $valeurs) {
                    foreach (json_decode($valeurs->getValeur(), TRUE) as $valeur) {

                        if (is_array($valeur)) {
                            $operateurString = $filtre->getOperateur()->getOperateur();
                            foreach ($valeur as $j => $val) {
                                if ($j < (int)$filtre->getOperateur()->getnbValeurs()) {
                                    $operateurString = str_replace(
                                        $this->operateurParametres[$j],
                                        $val,
                                        $operateurString
                                    );
                                }
                            }
                        } else {

                            $valeur = str_replace('"', '', $valeur);
                            $operateurString = str_replace(
                                $this->operateurParametres[0],
                                $valeur,
                                $filtre->getOperateur()->getOperateur()
                            );
                        }
                        $operation[] =
                            $filtre->getChamp()->getTableBD() .
                            '.' .
                            $filtre->getChamp()->getChampBD() .
                            ' ' .
                            $operateurString;
                    }

                }
                $or = '';
                if ($i == 1) {
                    $where .= " WHERE ( ";
                } else {
                    $where .= "AND ( ";
                }
                foreach ($operation as $k => $item) {
                    if ($k == 0) {
                        $or .= "( " . $item . ') ';
                    } else {
                        if ($filtre->getOperateur()->getNegation()) {
                            $or .= " AND (  " . $item . ') ';

                        } else {
                            $or .= " OR ( " . $item . ') ';

                        }
                    }
                }
                $where .= $or . " ) ";
            }
        }
        //GESTION VISIBILITE
        $userId = $this->tokenStorage->getToken()->getUser()->getId();
        $where = empty($where) ? "WHERE ((" : $where . "AND ((";
        $where .= 'pl_active = 1) ';
        if ($count && !$prive) {
            $where .= 'AND ( (pl_visibilite <= 2)))';
        } elseif ($count && $prive) {
            $where .= 'AND ( (pl_visibilite > 2) AND (user_creation.id = ' . $userId . ')))';
        } else {
            $where .= 'AND ( (pl_visibilite <= 2) OR (user_creation.id = ' . $userId . ')))';
        }
        foreach ($joins as $join) {
            $sql .= ' ' . $join;
        }
        $sql .= ' LEFT JOIN user as user_creation on user_creation.id = pl.user_creation_id ';
        $sql .= $where . " ORDER BY pl_libelle";
        return $sql;
    }


    public function countContacts($segment, $index, $prive)
    {
        $sql = $this->buildSegmentRequete($segment->getFiltres(), $index, true, $prive);
        $resultat = $this->connexion->prepare($sql);
        $resultat->execute();
        $resultat = $resultat->fetchAllAssociative();
        if (isset($resultat[0]['count'])) {
            return $resultat[0]['count'];
        }
        return 0;
    }
}
