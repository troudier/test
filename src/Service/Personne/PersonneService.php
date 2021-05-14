<?php

namespace App\Service\Personne;

use App\Entity\Champ;
use App\Entity\LienChamp;
use App\Entity\LienIntervenant;
use App\Entity\PersonneLien;
use App\Entity\PersonneLienFonction;
use App\Entity\PersonnePhysique;
use App\Entity\PersonneStatut;
use App\Entity\Tag;
use App\Entity\User;
use App\Model\Personne;
use App\Service\AdresseService;
use App\Service\CoordonneesService;
use App\Service\EmailService;
use App\Service\MemoService;
use App\Service\TelephoneService;
use DateTime;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class PersonneService
{
    /**
     * Liste des champs pour la recherche texte
     *
     * @var string[]
     */
    private $champsRecherche = [
        'pp.pp_prenom',
        'pp.pp_nom',
        'pm.pm_raison_sociale'
    ];

    private $champStatut = 'ps.ps_libelle';

    private $champType = 'pl.pl_type';

    private $champOrder = [
        'libelle' => 'pl_libelle'
    ];

    const INTER_CREATEUR = 0;
    const INTER_REFERENT = 1;
    const INTER_INTERVENANT = 2;

    private Connection $connexion;

    private EntityManagerInterface $em;

    private TokenStorageInterface $tokenStorage;

    private PersonnePhysiqueService $personnePhysiqueService;

    private PersonneLienService $personneLienService;

    private TelephoneService $telephoneService;

    private EmailService $emailService;

    private PersonneMoraleService $personneMoralService;


    private MemoService $memoService;

    private CoordonneesService $coordonneesService;

    private AdresseService $adresseService;

    public function __construct(
        EntityManagerInterface $em,
        TokenStorageInterface $tokenStorage,
        PersonneLienService $personneLienService,
        PersonnePhysiqueService $personnePhysiqueService,
        PersonneMoraleService $personneMoraleService,
        MemoService $memoService,
        TelephoneService $telephoneService,
        EmailService $emailService,
        CoordonneesService $coordonneesService,
        AdresseService $adresseService
    )
    {
        $this->em = $em;
        $this->connexion = $this->em->getConnection();
        $this->tokenStorage = $tokenStorage;
        $this->personnePhysiqueService = $personnePhysiqueService;
        $this->personneMoralService = $personneMoraleService;
        $this->memoService = $memoService;
        $this->personneLienService = $personneLienService;
        $this->telephoneService = $telephoneService;
        $this->emailService = $emailService;
        $this->coordonneesService = $coordonneesService;
        $this->adresseService = $adresseService;
    }

    /**
     * Prépare le SQL pour filter la liste des personnes (type, statut + recherche texte )
     *
     * @param array $filtes
     * @return string
     */
    public function setFiltres(array $filtes)
    {
        $sqlPart = '';
        foreach ($filtes as $filtre => $valeur) {
            $sqlPart = empty($sqlPart) ? "WHERE (" : $sqlPart . "AND (";
            switch ($filtre) {
                case 'recherche':
                    foreach ($this->champsRecherche as $i => $champs) {
                        foreach ($valeur as $j => $mot) {
                            $sqlPart .= ($i == 0) && ($j == 0) ? "" : "OR ";
                            $sqlPart .= " " . $champs . " LIKE '%" . $mot . "%' ";
                        }
                    }
                    $sqlPart .= ") ";
                    break;
                case 'type':
                    foreach ($valeur as $i => $type) {
                        $sqlPart .= $i == 0 ? "" : "OR ";
                        $sqlPart .= $this->champType . ' = "' . $type . '" ';
                    }
                    $sqlPart .= ") ";
                    break;
                case 'statut':
                    foreach ($valeur as $i => $statut) {
                        $sqlPart .= $i == 0 ? "" : "OR ";
                        $sqlPart .= $this->champStatut . ' = "' . $statut . '" ';
                    }
                    $sqlPart .= ") ";
                    break;
            }
        }
        //Gestion de la visibilité
        $userId = $this->tokenStorage->getToken()->getUser()->getId();
        $sqlPart = empty($sqlPart) ? "WHERE (" : $sqlPart . "AND (";
        $sqlPart .= 'pl_active = 1) AND (';
        $sqlPart .= '(pl_visibilite < 2) OR (user_creation.id = ' . $userId . ')';


        $sqlPart .= ") ";
        return $sqlPart;
    }

    /**
     * Récupère la liste des personnes, filtrées et paginée
     *
     * @param $query
     * @return \Doctrine\DBAL\Statement
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
        $slimitQuery = "";
        $limit = $query->has('limit') ? (int)$query->get('limit') : null;
        $offset = $query->has('offset') ? (int)$query->get('offset') : null;
        if ($limit !== null && $offset !== null) {
            $slimitQuery = " LIMIT " . $offset . "," . $limit;
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
                  ' . $swhereQuery . ' 
                 ORDER BY ' . $this->champOrder['libelle'] . ' ' . $slimitQuery;
        return $this->connexion->prepare($sql);
    }

    /**
     * Récupère les informations nécessaires à afficher la fiche d'une personne
     *
     * @param $uuid
     * @return \Doctrine\DBAL\Statement
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
                 WHERE pl.pl_uuid = "' . $uuid . '"';
        return $this->connexion->prepare($sql);
    }

    /**
     * Récupère les mémos associés à une personne
     *
     * @param $uuid
     * @return \Doctrine\DBAL\Statement
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
                  JOIN (select * from personne_lien as pl WHERE pl.pl_uuid = "' . $uuid . '") as pl
                        on pl.id = mem.lien_id
                  JOIN user as user_creation on user_creation.id = pl.user_creation_id
                  JOIN personne_physique as p_create on p_create.user_id =  user_creation.id
                  ORDER BY mem.mem_crea_date DESC';
        return $this->connexion->prepare($sql);
    }

    /**
     * Récupère les tags associés à une personne
     *
     * @param $uuid
     * @return \Doctrine\DBAL\Statement
     * @throws \Doctrine\DBAL\Exception
     */
    public function getPersonneTags($uuid)
    {

        $sql = 'SELECT  
                    tag_libelle as libelle,
                    tag_uuid as uuid
                 FROM tag 
                  JOIN personne_lien_tag as lg on lg.tag_id = tag.id
                  JOIN (select * from personne_lien as pl WHERE pl.pl_uuid = "' . $uuid . '") as pl on lg.personne_lien_id = pl.id
                  ';
        return $this->connexion->prepare($sql);
    }

    /**
     * Rattache une liste de tags à une personneLien
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
     * Récupère les liens associés à une personne
     *
     * @param $uuid
     * @return \Doctrine\DBAL\Statement
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
                  JOIN personne_physique as pp on pp.pp_uuid = "' . $uuid . '"
                  LEFT JOIN personne_lien_fonction as plf on plf.id = pl.fonction_id
                  LEFT JOIN personne_morale as pm on pm.id = pl.personne_morale_id
                  where pl.personne_physique_id = pp.id
                  order by type desc;';
        return $this->connexion->prepare($sql);
    }

    /**
     * Récupère les titres disponibles
     *
     * @param $query
     * @return \Doctrine\DBAL\Statement
     * @throws \Doctrine\DBAL\Exception
     */
    public function prepareListeStatuts($query)
    {
        $sql = 'SELECT  
                    ps_uuid as uuid,
                    ps_libelle as libelle,
                    ps_texte as texte
                 FROM personne_statut ';
        return $this->connexion->prepare($sql);
    }

    /**
     * Met à jour une Personne
     *
     * @param $data
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
            $lien[0]->setQualite((int)$data['personne']['qualite']);
            if (isset($data['personne']['actif'])) {
                $lien[0]->setActive($data['personne']['actif']);
            } else {
                $lien[0]->setActive(0);
            }
            $fonction = $this->em->getRepository(PersonneLienFonction::class)
                ->findBy(['uuid' => $data["personne"]["fonction"]]);
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
            $this->updateIntervenants($lien[0], $data);
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
                'tags' => $data['personne']['tags']
            ]);
            $this->em->persist($lien[0]);
            $this->em->flush();
            return $data['personne']['uuid'];
        }
        return null;
    }

    /**
     * Met à jour les intervenants pour une personne
     *
     * @param $data
     * @throws \Exception
     *
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
                    self::INTER_REFERENT);
            }
        }

        //Ajout du lien pour les intervenants
        if (isset($data['personne']['intervenants'])) {
            $found = $this->em->getRepository(LienIntervenant::class)
                ->findBy(['lien' => $lien, 'type' => self::INTER_INTERVENANT]);
            if(empty($data['personne']['intervenants'])){
                foreach ($found as $item) {
                        $this->em->remove($item);
                }
            }else{
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
                    if (
                        empty($users)
                        || !array_key_exists($item->getUser()->getId(), $users)) {
                        $this->em->remove($item);
                    } else {
                        unset($users[$item->getUser()->getId()]);
                    }
                }
                foreach ($users as $user) {
                    $this->addIntervenant(
                        $lien,
                        $user,
                        self::INTER_INTERVENANT);
                }
            }

        }
    }

    /**
     * Création d'un lien intervenant entre une entité personne et un user
     *
     * @param $lien
     * @param $user
     * @param $type
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
     * Création d'une personne
     *
     * @param $data
     * @return array|string[]
     * @throws \Doctrine\DBAL\Exception
     */
    public function create($data)
    {
        //Vérification des champs manquants (ceux requis par CHAMPS_REQUIS)
        $manquants = '';
        if ($data['personne']['type'] == 'physique') {
            $manquants = $this->personnePhysiqueService->getChampsManquants($data['personne']);
        }
        if ($data['personne']['type'] == 'morale') {
            $manquants = $this->personneMoralService->getChampsManquants($data['personne']);
        }
        if ($data['personne']['type'] == 'lien') {
            $manquants = $this->personneLienService->getChampsManquants($data['personne']);
        }
        if (count($manquants) > 0) {
            $message = 'Champs requis :';
            $i = 0;
            foreach ($manquants as $champs) {
                if ($i !== 0) {
                    $message .= ', ';
                }
                $message .= $champs;
                $i++;
            }
            return ['erreur' => $message];
        }

        //Création des entités personne physique, personne morale, personne lien et du lien intervenant (créateur) entre eux
        $personnePhysique = null;
        $personneMorale = null;
        if ($data['personne']['type'] == 'physique') {
            $personnePhysique = $this->personnePhysiqueService->add($data);
        }
        if ($data['personne']['type'] == 'morale') {
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
            $this->addIntervenant(
                $lien,
                $user,
                self::INTER_REFERENT);
        }

        //Ajout du lien pour les intervetants
        if (isset($data['personne']['intervenants']) && !empty($data['personne']['intervenants'])) {
            foreach ($data['personne']['intervenants'] as $intervenant) {
                $referentLien = $this->em->getRepository(PersonneLien::class)
                    ->findBy(['uuid' => $intervenant]);
                $user = $referentLien[0]->getPersonnePhysique()->getUser();
                $this->addIntervenant(
                    $lien,
                    $user,
                    self::INTER_INTERVENANT);
            }
        }

        //Ajout de l email et du lien
        if (isset($data['personne']['email']) && !empty($data['personne']['email'])) {
            $this->emailService->add(
                $lien->getUuid()->toString(),
                $data['personne']['email'],
                '1',
                true
            );
        }

        //Ajout du téléphone du lien
        if (isset($data['personne']['telephone']) && !empty($data['personne']['telephone'])) {
            $this->telephoneService->add(
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
                'persist' => true
            ]);
        }
        return ['uuid' => $lien->getUuid()->toString()];
    }


    /**
     * Création d'une personne Fonction
     *
     * @param $data
     * @return array|string[]
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
     * Mets à jours les champs personnalisés d'une personne
     *
     * @param $lien
     * @param $data
     */
    private function updateChamps($lien, $data)
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


    public function preparePersonnesSelect($type)
    {

        if ($type === 'morale') {
            $sql = 'SELECT  
                       distinct(pl.pl_uuid) as uuid,
                       pm.pm_raison_sociale as libelle
                 FROM personne_lien as pl
                 LEFT JOIN personne_morale as pm on pm.id = pl.personne_morale_id
                 WHERE pl.pl_type = "' . $type . '"';
        } elseif ($type === 'physique') {
            $sql = 'SELECT  
                       distinct(pl.pl_uuid) as uuid,
                       CONCAT(pp.prenom, " ", pp.nom) as libelle
                 FROM personne_lien as pl
                 LEFT JOIN personne_physique as pp on pp.id = pl.personne_physique_id
                 WHERE pl.pl_type = "' . $type . '"';
        }

        return $this->connexion->prepare($sql);
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
}
