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

class SegmentService
{
    private $champsRecherche = [
        'seg.seg_titre',
        'pp.pp_nom',
        'pp.pp_prenom'
    ];

    private $type_input = [
        1 => 'string',
        2 => 'int',
        11 => 'select2'
    ];

    const INTER_CREATEUR = 0;
    const INTER_REFERENT = 1;
    const INTER_INTERVENANT = 2;

    private $champOrder = [
        'titre' => 'titre'
    ];
    private Connection $connexion;

    private EntityManagerInterface $em;

    private TokenStorageInterface $tokenStorage;

    private HelperService $helperService;

    private RequetteurService $requetteurService;

    public function __construct(
        EntityManagerInterface $em,
        TokenStorageInterface $tokenStorage,
        HelperService $helperService,
        RequetteurService $requetteurService
    )
    {
        $this->em = $em;
        $this->connexion = $this->em->getConnection();
        $this->tokenStorage = $tokenStorage;
        $this->helperService = $helperService;
        $this->requetteurService = $requetteurService;
    }

    /**
     * Prépare le SQL pour filter la liste des segments
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
            }
        }
        //Gestion de la visibilité
        $userId = $this->tokenStorage->getToken()->getUser()->getId();
        $sqlPart = empty($sqlPart) ? "WHERE (" : $sqlPart . "AND (";
        $sqlPart .= 'seg_active = 1) AND (';
        $sqlPart .= '(seg_visibilite < 2) OR (user_creation.id = ' . $userId . ')';


        $sqlPart .= ") ";
        return $sqlPart;
    }

    /**
     * Récupère la liste des segments, filtrées et paginée
     *
     * @param $query
     * @return \Doctrine\DBAL\Statement
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
        $slimitQuery = "";
        $limit = $query->has('limit') ? (int)$query->get('limit') : null;
        $offset = $query->has('offset') ? (int)$query->get('offset') : null;
        if ($limit !== null && $offset !== null) {
            $slimitQuery = " LIMIT " . $offset . "," . $limit;
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
                  ' . $swhereQuery . '   ORDER BY ' . $this->champOrder['titre'] . ' ASC' . $slimitQuery;

        return $this->connexion->prepare($sql);
    }

    /**
     * Revnoie les infomrations pour afficher la fiche d'un segment
     *
     * @param $uuid
     * @return array
     *
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
            $data['derniereDate'] = $segment[0]->getDerniereDateExecution() ? $segment[0]->getDerniereDateExecution()->format('Y-m-d H:i:s') : null;
            $data['creationDate'] = $segment[0]->getDateCreation()->format('Y-m-d H:i:s');
            $data['modificationDate'] = $segment[0]->getDateModification()->format('Y-m-d H:i:s');
            $data['modificationUser'] = $this->getSegmentUser($segment[0]->getUserCreation());
            $data['creationUser'] = $this->getSegmentUser($segment[0]->getUserModification());
            $data['visibilite'] = (string)$segment[0]->getVisibilite();
            /** @var SegmentIntervenant $intervenant */

            foreach ($segment[0]->getIntervenants() as $intervenant) {
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
            $filtres = [];
            /** @var SegmentFiltre $item */
            foreach ($segment[0]->getFiltres() as $item) {
                $filtres[$item->getOrdre()]['uuid'] = $item->getUuid()->toString();
                $filtres[$item->getOrdre()]['ordre'] = (int)$item->getOrdre();
                $filtres[$item->getOrdre()]['champ']['uuid'] = $item->getChamp()->getUuid()->toString();
                $filtres[$item->getOrdre()]['champ']['type'] = $this->type_input[(int)$item->getChamp()->getTypeInput()];
                $filtres[$item->getOrdre()]['champ']['libelle'] = $item->getChamp()->getLibelle();
                $filtres[$item->getOrdre()]['operateur']['uuid'] = $item->getOperateur()->getUuid()->toString();
                $filtres[$item->getOrdre()]['operateur']['libelle'] = $item->getOperateur()->getLibelle();
                $filtres[$item->getOrdre()]['operateur']['nbValeurs'] = $item->getOperateur()->getNbValeurs();
                $filtres[$item->getOrdre()]['valeurs'] = [];
                /** @var SegmentFiltreValeur $valeur */
                foreach ($item->getValeurs() as $valeur) {
                    if ($this->type_input[(int)$item->getChamp()->getTypeInput()] == "select2") {
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

    public function getSegmentUser($user)
    {
        $data = [];
        /** @var PersonnePhysique[] $personne */
        $personne = $this->em->getRepository(PersonnePhysique::class)
            ->findBy(['user' => $user]);
        if ($personne[0]) {
            $data['prenom'] = $personne[0]->getPrenom();
            $data['nom'] = $personne[0]->getNom();
        }
        return $data;
    }

    public function printResultats($uuids)
    {
        $data = [
            'resultats' => []
        ];
        $count = [
            'public' => 0,
            'prive' => 0
        ];
        foreach ($uuids as $uuid) {
            $lien = $this->em->getRepository(PersonneLien::class)->findBy(['uuid' => $uuid]);
            if ((int)$lien[0]->getVisibilite() < 3) {
                $count['public']++;
            } else {
                $count['prive']++;
            }
            $item = [];
            $item['type'] = $lien[0]->getType();

            switch ($lien[0]->getType()) {
                case 'physique':
                    $item['libelle'] =
                        $lien[0]->getPersonnePhysique()->getPrenom() .
                        ' ' .
                        $lien[0]->getPersonnePhysique()->getNom();
                    break;
                case 'morale':
                    $item['libelle'] =
                        $lien[0]->getPersonneMorale()->getRaisonSociale() .
                        ' (' .
                        $lien[0]->getPersonneMorale()->getFormeJuridique()->getLibelle() .
                        ')';
                    break;
                case 'lien':
                    $item['libelle'] =
                        $lien[0]->getPersonnePhysique()->getPrenom() .
                        ' ' .
                        $lien[0]->getPersonnePhysique()->getNom() .
                        ', ' .
                        $lien[0]->getPersonneMorale()->getRaisonSociale() .
                        ' (' .
                        $lien[0]->getPersonneMorale()->getFormeJuridique()->getLibelle() .
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

    public function getResultats($uuid)
    {
        /** @var PersonneLien[] $liens */
        $uuids = $this->calculate($uuid);
        return $this->printResultats($uuids);
    }

    /**
     * Recalcule les résultats d'un segment
     *
     * @param $uuid
     * @return array|\mixed[][]|null
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws Exception
     */
    public function calculate($uuid)
    {
        /** @var Segment[] $segment */
        $segment = $this->em->getRepository(Segment::class)->findBy(['uuid' => $uuid]);
        if ($segment[0]) {
            return $this->getContacts($segment[0], true);
        }
        return null;
    }

    public function getContacts(Segment $segment, $persist = true)
    {
        $sql = $this->requetteurService->buildSegmentRequete($segment->getFiltres());
        $resultat = $this->connexion->prepare($sql);
        $resultat->execute();
        $resultat = $resultat->fetchAllAssociative();
        $count = [
            'public' => 0,
            'prive' => 0
        ];
        foreach ($resultat as $res) {
            if ($res['visibilite'] > 2) {
                $count['prive']++;
            } else {
                $count['public']++;
            }
        }
        if ($persist) {
            $segment->setNbContactsPrives($count['prive']);
            $segment->setNbContactsPublics($count['public']);
            $segment->setDerniereDateExecution(new DateTime());
            $this->em->persist($segment);
            $this->em->flush();
        }
        return $resultat;
    }

    /**
     * Recalcule le nombre de contacts d'une requête et selon un nombre de filtres déterminé
     *
     * @param $uuid
     * @param $index
     * @return int|mixed|null
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws Exception
     */
    public function nbContacts($uuid, $index)
    {
        /** @var Segment[] $segment */
        $segment = $this->em->getRepository(Segment::class)->findBy(['uuid' => $uuid]);
        if ($segment[0]) {
            $this->requetteurService->countContacts($segment[0], $index);
        }
        return null;

    }


    /**
     * Recalcule la liste des  nombres de contacts d'une requête selon l'ensemble des filtres
     *
     * @param $uuid
     * @param $index
     * @return array|array[]|null
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws Exception
     */
    public function listeNbContacts($uuid)
    {
        if (
            !is_string($uuid)
            ||
            (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $uuid) !== 1)
        ) {
            return null;
        }
        /** @var Segment[] $segment */
        $segment = $this->em->getRepository(Segment::class)->findBy(['uuid' => $uuid]);
        if ($segment[0]) {
            return $this->caculerTousResultats($segment[0]);
        } else {
            return null;
        }
    }

    public function caculerTousResultats($segment)
    {
        $data = [];
        foreach ($segment->getFiltres() as $i => $filte) {
            $data[$i]['public'] = $this->requetteurService->countContacts($segment, $i + 1, false);
            $data[$i]['prive'] = $this->requetteurService->countContacts($segment, $i + 1, true);
        }
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
            $item['type'] = $this->type_input[(int)$champs->getTypeInput()];
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

    /**
     * Met à jour une Personne
     *
     * @param $data
     * @throws \Exception
     */
    public function update($data, $persist = true)
    {
        if ($persist) {
            $segmentListe = null;
            if (
                is_string($data['segment']['uuid'])
                &&
                (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $data['segment']['uuid']) === 1)
            ) {
                /** @var Segment[] $segmentListe */
                $segmentListe = $this->em->getRepository(Segment::class)->findBy(['uuid' => $data['segment']['uuid']]);
            }

            if (isset($segmentListe[0])) {
                $segment = $segmentListe[0];
            } else {
                $segment = new Segment();
                $segment->setDateCreation(new DateTime());
                $segment->setUserCreation($this->tokenStorage->getToken()->getUser());
            }
        } else {
            $segment = new Segment();
            $segment->setDateCreation(new DateTime());
            $segment->setUserCreation($this->tokenStorage->getToken()->getUser());
        }
        $segment->setTitre($data['segment']['titre']);
        $segment->setVisibilite($data['segment']['visibilite']);
        if (isset($data['segment']['actif'])) {
            $segment->setActive($data['segment']['actif']);
        } else {
            $segment->setActive(0);
        }
        $this->updateIntervenants($segment, $data, $persist);
        $this->updateFiltres($segment, $data, $persist);

        $segment->setDateModification(new DateTime());
        $segment->setUserModification($this->tokenStorage->getToken()->getUser());
        $this->em->persist($segment);

        if ($persist) {
            $this->em->flush();
            return $segment->getUuid()->toString();
        } else {
            return $segment;
        }
    }


    /**
     * Met à jour les intervenants pour un segment
     *
     * @param $data
     * @throws \Exception
     *
     */
    public function updateIntervenants($segment, $data, $persist = true)
    {
        $user = null;
        //Ajout du lien pour le référent
        if (isset($data['segment']['referent'])) {
            $exists = false;
            $found = $this->em->getRepository(SegmentIntervenant::class)
                ->findBy(['segment' => $segment, 'type' => self::INTER_REFERENT]);
            if (!empty($data['segment']['referent'])) {
                $referentLien = $this->em->getRepository(PersonneLien::class)
                    ->findBy(['uuid' => $data['segment']['referent']]);
                if (isset($referentLien[0])) {
                    $user = $referentLien[0]->getPersonnePhysique()->getUser();
                }else{
                    $referentLien = $this->em->getRepository(PersonnePhysique::class)
                        ->findBy(['uuid' => $data['segment']['referent']]);
                    if (isset($referentLien[0])) {
                        $user = $referentLien[0]->getUser();
                    }
                }

            }
            /** @var SegmentIntervenant $item */
            foreach ($found as $item) {
                if (!isset($user) || $item->getUser() !== $user) {
                    if ($persist) {
                        $this->em->remove($item);
                    }
                } else {
                    $exists = true;
                }
            }
            if (!$exists && $user) {
                $this->addIntervenant(
                    $segment,
                    $user,
                    self::INTER_REFERENT);
            }
        }

        //Ajout du lien pour les intervenants
        if (isset($data['segment']['intervenants'])) {
            $found = $this->em->getRepository(SegmentIntervenant::class)
                ->findBy(['segment' => $segment, 'type' => self::INTER_INTERVENANT]);
            if (empty($data['segment']['intervenants'])) {
                foreach ($found as $item) {
                    if ($persist) {
                        $this->em->remove($item);
                    }
                }
            } else {
                /** @var User[] $users */
                $users = [];
                $found = $this->em->getRepository(SegmentIntervenant::class)
                    ->findBy(['segment' => $segment, 'type' => self::INTER_INTERVENANT]);
                foreach ($data['segment']['intervenants'] as $intervenant) {
                    $referentLien = $this->em->getRepository(PersonneLien::class)
                        ->findBy(['uuid' => $intervenant]);
                    $userItem = $referentLien[0]->getPersonnePhysique()->getUser();
                    $users[$userItem->getId()] = $userItem;
                }
                foreach ($found as $item) {
                    if (
                        empty($users)
                        || !array_key_exists($item->getUser()->getId(), $users)) {
                        if ($persist) {
                            $this->em->remove($item);
                        }
                    } else {
                        unset($users[$item->getUser()->getId()]);
                    }
                }
                foreach ($users as $user) {

                    $this->addIntervenant(
                        $segment,
                        $user,
                        self::INTER_INTERVENANT,
                        $persist
                    );
                }
            }

        }
    }

    /**
     * Création d'un lien intervenant entre une entité segment et un user
     *
     * @param $segment
     * @param $user
     * @param $type
     * @throws \Exception
     */
    public function addIntervenant($segment, $user, $type, $persist = true)
    {
        $segmentIntervenant = new SegmentIntervenant();
        $segmentIntervenant->setUuid(Uuid::uuid4());
        $segmentIntervenant->setSegment($segment);
        $segmentIntervenant->setUser($user);
        $segmentIntervenant->setType($type);
        $this->em->persist($segmentIntervenant);
    }


    /**
     * Met à jour les filtres pour un segment
     *
     * @param Segment $segment
     * @param $data
     * @throws \Exception
     */
    public function updateFiltres($segment, $data, $persist = true)
    {
        $filtres = [];
        if ($segment->getFiltres()) {
            /** @var SegmentFiltre $dbFiltre */
            foreach ($segment->getFiltres() as $dbFiltre) {
                $filtreExiste = false;
                foreach ($data['segment']['filtres'] as $key => $item) {
                    if ($item['uuid'] === $dbFiltre->getUuid()->toString()) {
                        $filtreExiste = true;
                        $champ = $dbFiltre->getChamp();
                        if ($champ->getUuid()->toString() !== $item['champ']['uuid']) {
                            $newChamp = $this->em->getRepository(ChampRequetable::class)
                                ->findBy(['uuid' => $item['champ']['uuid']]);
                            if (isset($newChamp[0])) {
                                $dbFiltre->setChamp($newChamp[0]);
                            }
                        }
                        $operateur = $dbFiltre->getOperateur();
                        if ($operateur->getUuid()->toString() !== $item['operateur']['uuid']) {
                            $newOperateur = $this->em->getRepository(Operateur::class)
                                ->findBy(['uuid' => $item['operateur']['uuid']]);
                            if (isset($newOperateur[0])) {
                                $dbFiltre->setOperateur($newOperateur[0]);
                            }
                        }
                        /** @var SegmentFiltreValeur $dbValeur */
                        foreach ($dbFiltre->getValeurs() as $dbValeur) {
                            $valeurExiste = false;
                            foreach ($item['valeurs'] as $valeurKey => $valeur) {
                                if (json_decode($dbValeur->getValeur(), TRUE) === $valeur) {
                                    $valeurExiste = true;
                                    unset($item['valeurs'][$valeurKey]);
                                }
                            }
                            if (!$valeurExiste) {
                                if ($persist) {
                                    $this->em->remove($dbValeur);
                                }
                            }
                        }
                        foreach ($item['valeurs'] as $valeur) {
                            $this->createValeur($dbFiltre, json_encode($valeur), $persist);
                        }
                        $this->em->persist($dbFiltre);
                        unset($data['segment']['filtres'][$key]);
                        $filtres[] = $dbFiltre;
                    }
                }
                if (!$filtreExiste) {
                    foreach ($dbFiltre->getValeurs() as $valeur) {
                        if ($persist) {
                            $this->em->remove($valeur);
                        }
                    }
                    if ($persist) {
                        $this->em->remove($dbFiltre);
                    }
                }
            }
        }
        if ($data['segment']['filtres']) {
            foreach ($data['segment']['filtres'] as $i => $item) {
                if ($item) {
                    $filtres[] = $this->createFiltre($segment, $item, $persist, $i);
                }
            }
        }

        if (!$persist) {
            $segment->setFiltres($filtres);
        }
    }

    /**
     * Créé un objet SegmentFiltreValeur lié à un SegmentFiltre
     *
     * @param $filtre
     * @param $valeur
     * @return SegmentFiltreValeur
     * @throws \Exception
     */
    public function createValeur($filtre, $valeur, $persist = true)
    {
        $newValeur = new SegmentFiltreValeur();
        $newValeur->setUuid(Uuid::uuid4());
        $newValeur->setSegmentFiltre($filtre);
        if (!is_array($valeur)) {
            $valeur = json_decode($valeur);
        }
        if (is_array($valeur)) {
            $newValeur->setValeur((string)json_encode($valeur));

        } else {
            $newValeur->setValeur((string)json_encode([$valeur], JSON_OBJECT_AS_ARRAY));
        }
        $newValeur->setDateCreation(new DateTime());
        $newValeur->setUserCreation($this->tokenStorage->getToken()->getUser());
        $newValeur->setDateModification(new DateTime());
        $newValeur->setUserModification($this->tokenStorage->getToken()->getUser());
        $this->em->persist($newValeur);
        return $newValeur;
    }

    /**
     * Créé un objet SegmentFiltre lié à un Segment
     *
     * @param $segment
     * @param $filtre
     * @throws \Exception
     */
    public function createFiltre($segment, $filtre, $persist = true, $index = 0)
    {
        $newFiltre = new SegmentFiltre();
        $newFiltre->setUuid(Uuid::uuid4());
        $newFiltre->setSegment($segment);
        if ($segment->getFiltres()) {
            $newFiltre->setOrdre(count($segment->getFiltres()) + 1);
        } else {
            $newFiltre->setOrdre($index);
        }
        $champ = $this->em->getRepository(ChampRequetable::class)->findBy(['uuid' => $filtre['champ']['uuid']]);
        if (isset($champ[0])) {
            $newFiltre->setChamp($champ[0]);
        }
        $operateur = $this->em->getRepository(Operateur::class)->findBy(['uuid' => $filtre['operateur']['uuid']]);
        if (isset($operateur[0])) {
            $newFiltre->setOperateur($operateur[0]);
        }
        $valeurs = [];
        foreach ($filtre['valeurs'] as $valeur) {

            $valeurs[] = $this->createValeur($newFiltre, json_encode($valeur), $persist);
        }
        if (!$persist) {
            $newFiltre->setValeurs($valeurs);
        }
        $newFiltre->setDateCreation(new DateTime());
        $newFiltre->setUserCreation($this->tokenStorage->getToken()->getUser());
        $newFiltre->setDateModification(new DateTime());
        $newFiltre->setUserModification($this->tokenStorage->getToken()->getUser());
        $this->em->persist($newFiltre);
        return $newFiltre;
    }

    /**
     * Recalcule les nombres de résultats intermédiaires d'un segment en cours de modification
     *
     * @param $data
     * @throws \Exception
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    public function calculer($data)
    {
        $segment = $this->update($data, false);
        return $this->caculerTousResultats($segment);
    }

    /**
     * Récupère les  résultats d'un segment en cours de modification
     *
     * @param $data
     * @throws \Exception
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    public function liveResultats($data)
    {
        $segment = $this->update($data, false);
        $uuids = $this->getContacts($segment, false);
        return $this->printResultats($uuids);
    }

}
