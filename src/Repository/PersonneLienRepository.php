<?php

namespace App\Repository;

use App\Entity\LienIntervenant;
use App\Entity\PersonneLien;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class PersonneLienRepository extends ServiceEntityRepository
{
    private $champOrder = [
        'libelle' => 'pl_libelle',
    ];

    private $champsRecherche = [
        'pp.pp_prenom',
        'pp.pp_nom',
        'pm.pm_raison_sociale',
    ];

    public const INTER_REFERENT = 1;

    public const INTER_INTERVENANT = 2;

    private TokenStorageInterface $tokenStorage;

    private $champStatut = 'ps.ps_libelle';

    private $champType = 'pl.pl_type';

    private Connection $connexion;

    private EntityManagerInterface $em;

    public function __construct(
        ManagerRegistry $registry,
        EntityManagerInterface $em,
        TokenStorageInterface $tokenStorage
    ) {
        parent::__construct($registry, PersonneLien::class);
        $this->em = $em;
        $this->connexion = $this->em->getConnection();
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * Prépare le SQL pour filter la liste des personnes (type, statut + recherche texte ).
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
                case 'type':
                    foreach ($valeur as $i => $type) {
                        $sqlPart .= 0 == $i ? '' : 'OR ';
                        $sqlPart .= $this->champType.' = "'.$type.'" ';
                    }
                    $sqlPart .= ') ';
                    break;
                case 'statut':
                    foreach ($valeur as $i => $statut) {
                        $sqlPart .= 0 == $i ? '' : 'OR ';
                        $sqlPart .= $this->champStatut.' = "'.$statut.'" ';
                    }
                    $sqlPart .= ') ';
                    break;
            }
        }
        //Gestion de la visibilité
        $userId = $this->tokenStorage->getToken()->getUser()->getId();
        $sqlPart = empty($sqlPart) ? 'WHERE (' : $sqlPart.'AND (';
        $sqlPart .= 'pl_active = 1) AND (';
        $sqlPart .= '(pl_visibilite < 2) OR (user_creation.id = '.$userId.')';

        $sqlPart .= ') ';

        return $sqlPart;
    }

    /**
     * Récupère la liste des personnes, filtrées et paginée.
     *
     * @param $query
     *
     * @return \Doctrine\DBAL\Statement
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function prepareCartesRequete($query)
    {
        $filtres = [];

        $recherche = $query->has('recherche') ? addslashes($query->get('recherche')) : null;
        if (!empty($recherche)) {
            $filtres['recherche'] = explode(' ', $recherche);
        }

        $statut = $query->has('statut') ? addslashes($query->get('statut')) : null;
        if (!empty($statut)) {
            $filtres['statut'] = explode(',', $statut);
        }

        $type = $query->has('type') ? addslashes($query->get('type')) : null;
        if (!empty($type)) {
            $filtres['type'] = explode(',', $type);
        }

        $swhereQuery = $this->setFiltres($filtres);
        $slimitQuery = '';
        $limit = $query->has('limit') ? (int) $query->get('limit') : null;
        $offset = $query->has('offset') ? (int) $query->get('offset') : null;
        if (null !== $limit && null !== $offset) {
            $slimitQuery = ' LIMIT '.$offset.','.$limit;
        }
        $sql = 'SELECT  
                       distinct(pl.pl_uuid) as uuid,
                       pp_prenom as prenom,
                       pl_libelle,
                       pp_nom as nom, 
                       pp.pp_infos_com as infoCommerciale, 
                       pm_raison_sociale as raisonSociale, 
                       ps.ps_libelle as statut,
                       plf_libelle as fonction,
                       a.adr_cp as cp,
                       a.adr_ville as ville, 
                       t.tel_valeur as telephone,
                       m.mail_valeur  as mail,
                       pp.pp_uuid,
                       pm.pm_uuid,
                       pl.pl_type as type,
                       (a.adr_cp is not null 
                       && a.adr_ville is not null 
                       && t.tel_valeur is not null 
                       &&  m.mail_valeur  is not null ) as isComplete,
                       fj.fjud_libelle as formeJuridique,
                       pl_active as actif
                 FROM personne_lien as pl
                 LEFT JOIN personne_physique as pp on pp.id = pl.personne_physique_id
                 LEFT JOIN personne_morale as pm on pm.id = pl.personne_morale_id
                 LEFT JOIN personne_lien_fonction as plf on plf.id = pl.fonction_id
                 LEFT JOIN (select * from lien_mail where plm_principal = 1   ) as lm on lm.lien_id = pl.id
                 LEFT JOIN mail as m on m.id = lm.mail_id                 
                 LEFT JOIN (select * from lien_telephone where plt_principal = 1  ) as lt on lt.lien_id = pl.id
                 LEFT JOIN telephone as t on t.id = lt.telephone_id                
                 LEFT JOIN (select * from lien_adresse where pla_principal = 1 ) as la on la.lien_id = pl.id
                 LEFT JOIN adresse as a on a.id = la.adresse_id 
                 LEFT JOIN forme_juridique as fj on fj.id = pm.forme_juridique_id
                 LEFT JOIN personne_statut as ps on ps.id = pl.statut_id
                 LEFT JOIN user as user_creation on user_creation.id = pl.user_creation_id
                  '.$swhereQuery.' 
                 ORDER BY '.$this->champOrder['libelle'].' '.$slimitQuery;

        return $this->connexion->prepare($sql);
    }

    /**
     * Récupère les informations nécessaires à afficher la fiche d'une personne.
     *
     * @param $uuid
     *
     * @return \Doctrine\DBAL\Statement
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function prepareFicheRequete($uuid)
    {
        $sql = 'SELECT  
                       pl.pl_uuid as uuid,
                       pp.pp_prenom as prenom,
                       pl.pl_libelle,
                       pp.pp_nom as nom, 
                       pp.pp_infos_com as infoCommerciale, 
                       pm.pm_raison_sociale as raisonSociale, 
                       ps.ps_libelle as statut,
                       plf.plf_libelle as fonctionLibelle,
                       plf.plf_uuid as fonction,
                       pl.fonction_personnalisee as fonctionPersonnalisee,
                       a.adr_cp as cp,
                       a.adr_ville as ville, 
                       t.tel_valeur as telephone,
                       m.mail_valeur  as mail,
                       pp.pp_uuid,
                       pm.pm_uuid,
                       pm2.pm_uuid as organisationParente,
                       pl.pl_type as type,
                       pl.pl_qualite as qualite,
                       pl.pl_active as actif,
                       (a.adr_cp is not null 
                       && a.adr_ville is not null 
                       && t.tel_valeur is not null 
                       &&  m.mail_valeur  is not null ) as isComplete,
                       fj.fjud_libelle as formeJuridiqueLibelle,
                       fj.fjud_uuid as formeJuridique,
                       ca.ca_libelle as chiffreAffaireLibelle,
                       ca.ca_uuid as chiffreAffaire,
                       eff.eff_libelle as effectifLibelle,
                       eff.eff_uuid as effectif,
                       p_create.pp_prenom as creationPrenom,
                       p_create.pp_nom as creationNom,
                       p_modification.pp_prenom as modificationPrenom,
                       p_modification.pp_nom as modificationNom,
                       pl.pl_crea_date as creationDate,
                       pl.pl_modif_date as modification_date,
                       pp.pp_civilite as civilite,
                       pp.pp_titre as titre,
                       pm.id as pmId,
                       p_apporteur.pp_uuid as apporteur,
                       origine.ori_uuid as origine,
                       pl_visibilite as visibilite,
                       pm.pm_code_naf as codeNaf,
                       pm.pm_siret as siret,
                       pm.pm_capital as capital
                 FROM personne_lien as pl
                 LEFT JOIN personne_physique as pp on pp.id = pl.personne_physique_id
                 LEFT JOIN personne_morale as pm on pm.id = pl.personne_morale_id
                 LEFT JOIN personne_morale as pm2 on pm2.id = pm.parent_id
                 LEFT JOIN personne_lien_fonction as plf on plf.id = pl.fonction_id
                 LEFT JOIN (select * from lien_mail where plm_principal = 1 ) as lm on lm.lien_id = pl.id
                 LEFT JOIN mail as m on m.id = lm.mail_id                 
                 LEFT JOIN (select * from lien_telephone where plt_principal = 1) as lt on lt.lien_id = pl.id
                 LEFT JOIN telephone as t on t.id = lt.telephone_id                
                 LEFT JOIN (select * from lien_adresse where pla_principal = 1 ) as la on la.lien_id = pl.id
                 LEFT JOIN adresse as a on a.id = la.adresse_id 
                 LEFT JOIN forme_juridique as fj on fj.id = pm.forme_juridique_id
                 LEFT JOIN personne_statut as ps on ps.id = pl.statut_id
                 LEFT JOIN chiffre_affaire as ca on ca.id = pm.chiffre_affaire_id
                 LEFT JOIN effectif as eff on eff.id = pm.effectif_id
                 LEFT JOIN user as user_creation on user_creation.id = pl.user_creation_id
                 LEFT JOIN personne_physique as p_create on p_create.user_id =  user_creation.id
                 LEFT JOIN user as user_modification on user_modification.id = pl.user_modification_id
                 LEFT JOIN personne_physique as p_modification on p_modification.user_id =  user_modification.id
                 LEFT JOIN personne_physique as p_apporteur on p_apporteur.id =  pp.apporteur_id
                 LEFT JOIN origine as origine on origine.id =  pp.origine_id
                 WHERE pl.pl_uuid = "'.$uuid.'"';

        return $this->connexion->prepare($sql);
    }

    /**
     * Récupère les mémos associés à une personne.
     *
     * @param $uuid
     *
     * @return \Doctrine\DBAL\Statement
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function getPersonneMemos($uuid)
    {
        $sql = 'SELECT  
                 mem.mem_texte as texte,
                 mem.mem_uuid as uuid,
                 mem.mem_crea_date as date,
                 p_create.pp_prenom as creationPrenom,
                 p_create.pp_nom as creationNom
                 FROM memo as mem
                  JOIN (select * from personne_lien as pl WHERE pl.pl_uuid = "'.$uuid.'") as pl
                        on pl.id = mem.lien_id
                  JOIN user as user_creation on user_creation.id = pl.user_creation_id
                  JOIN personne_physique as p_create on p_create.user_id =  user_creation.id
                  ORDER BY mem.mem_crea_date DESC';

        return $this->connexion->prepare($sql);
    }

    /**
     * Récupère les tags associés à une personne.
     *
     * @param $uuid
     *
     * @return \Doctrine\DBAL\Statement
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function getPersonneTags($uuid)
    {
        $sql = 'SELECT  
                    tag_libelle as libelle,
                    tag_uuid as uuid
                 FROM tag 
                  JOIN personne_lien_tag as lg on lg.tag_id = tag.id
                  JOIN (select * 
                        from personne_lien as pl 
                        WHERE pl.pl_uuid = "'.$uuid.'") as pl on lg.personne_lien_id = pl.id
                  ';

        return $this->connexion->prepare($sql);
    }

    /**
     * Récupère les liens associés à une personne.
     *
     * @param $uuid
     *
     * @return \Doctrine\DBAL\Statement
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function prepareLiensRequete($uuid)
    {
        $sql = 'SELECT  
                 pl.pl_libelle as libelle,
                 pl.pl_uuid as uuid,
                 pl.pl_type as type,
                 pp.pp_nom as nom,
                 pp.pp_prenom as prenom,
                 plf_libelle as fonction,
                 pm.pm_raison_sociale as raisonSociale
                 FROM personne_lien as pl
                  JOIN personne_physique as pp on pp.pp_uuid = "'.$uuid.'"
                  LEFT JOIN personne_lien_fonction as plf on plf.id = pl.fonction_id
                  LEFT JOIN personne_morale as pm on pm.id = pl.personne_morale_id
                  where pl.personne_physique_id = pp.id
                  order by type desc;';

        return $this->connexion->prepare($sql);
    }

    /**
     * Récupère les titres disponibles.
     *
     * @return \Doctrine\DBAL\Statement
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function prepareListeStatuts()
    {
        $sql = 'SELECT  
                    ps_uuid as uuid,
                    ps_libelle as libelle,
                    ps_texte as texte
                 FROM personne_statut ';

        return $this->connexion->prepare($sql);
    }

    /**
     * Met à jour les intervenants pour une personne.
     *
     * @param $data
     *
     * @throws \Exception
     */
    public function updateIntervenants($lien, $data)
    {
        //Ajout du lien pour le référent
        if (isset($data['personne']['referent'])) {
            $exists = false;
            $found = $this->em->getRepository(LienIntervenant::class)
                ->findBy(['lien' => $lien, 'type' => self::INTER_REFERENT]);
            if (!empty($data['personne']['referent'])) {
                $referentLien = $this->em->getRepository(PersonneLien::class)
                    ->findBy(['uuid' => $data['personne']['referent']]);
                $user = $referentLien[0]->getPersonnePhysique()->getUser();
            }
            /** @var LienIntervenant $item */
            foreach ($found as $item) {
                if (!isset($user) || $item->getUser() !== $user) {
                    $this->em->remove($item);
                } else {
                    $exists = true;
                }
            }
            if (!$exists) {
                $this->addIntervenant(
                    $lien,
                    $user,
                    self::INTER_REFERENT
                );
            }
        }

        //Ajout du lien pour les intervenants
        if (isset($data['personne']['intervenants'])) {
            $found = $this->em->getRepository(LienIntervenant::class)
                ->findBy(['lien' => $lien, 'type' => self::INTER_INTERVENANT]);
            if (empty($data['personne']['intervenants'])) {
                foreach ($found as $item) {
                    $this->em->remove($item);
                }
            } else {
                /** @var User[] $users */
                $users = [];
                $found = $this->em->getRepository(LienIntervenant::class)
                    ->findBy(['lien' => $lien, 'type' => self::INTER_INTERVENANT]);
                foreach ($data['personne']['intervenants'] as $intervenant) {
                    $referentLien = $this->em->getRepository(PersonneLien::class)
                        ->findBy(['uuid' => $intervenant]);
                    $userItem = $referentLien[0]->getPersonnePhysique()->getUser();
                    $users[$userItem->getId()] = $userItem;
                }
                foreach ($found as $item) {
                    if (empty($users)
                        || !array_key_exists($item->getUser()->getId(), $users)) {
                        $this->em->remove($item);
                    } else {
                        unset($users[$item->getUser()->getId()]);
                    }
                }
                foreach ($users as $user) {
                    $this->addIntervenant($lien, $user, self::INTER_INTERVENANT);
                }
            }
        }
    }
}
