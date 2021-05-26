<?php

namespace App\Controller;

use App\Entity\PersonneLien;
use App\Entity\PersonnePhysique;
use App\Repository\PersonneLienRepository;
use App\Service\HelperService;
use App\Service\Personne\PersonneService;
use Doctrine\DBAL\Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Throwable;

class PersonneController extends AbstractController
{
    private $personneService;

    private $personneLienRepository;

    private $helperService;

    public function __construct(
        PersonneService $personneService,
        PersonneLienRepository $personneLienRepository,
        HelperService $helperService
    ) {
        $this->personneService = $personneService;
        $this->personneLienRepository = $personneLienRepository;
        $this->helperService = $helperService;
    }

    /**
     * Endpoint pour la vue "cartes" du répertoire, renvoie une liste de personnes.
     *
     * @return JsonResponse
     *
     * @throws Exception
     */
    public function liste(Request $request)
    {
        $resultat = $this->personneLienRepository->prepareCartesRequete($request->query);
        try {
            $resultat->execute();

            return new JsonResponse($resultat->fetchAllAssociative());
        } catch (\Doctrine\DBAL\Driver\Exception $e) {
            return new JsonResponse(['error' => 'Internal Error'], 500);
        }
    }

    /**
     * Endpoint pour la vue "fiche" du répertoire, renvoie les informations d'une personne.
     *
     * @param $uuid
     *
     * @return JsonResponse
     *
     * @throws Exception
     */
    public function fiche($uuid)
    {
        $resultat = $this->personneLienRepository->prepareFicheRequete($uuid);

        $resultat->execute();
        $result = $resultat->fetchAssociative();
        if ($result) {
            $realUuid = $result['uuid'];
            if ('lien' === $result['type']) {
                $personneLien = $this->getDoctrine()->getRepository(PersonneLien::class)
                        ->findBy(['personneMorale' => $result['pmId'], 'type' => 'morale']);
                $realUuid = $personneLien[0]->getUuid();
            }
            $resultatMemos = $this->personneLienRepository->getPersonneMemos($realUuid);
            $resultatMemos->execute();
            $memos = $resultatMemos->fetchAllAssociative();
            $result['memos'] = $memos;
            $resultatTags = $this->personneLienRepository->getPersonneTags($realUuid);
            $resultatTags->execute();
            $tags = $resultatTags->fetchAllAssociative();
            $result['tags'] = $tags;

            return new JsonResponse($result);
        } else {
            return new JsonResponse(['error' => 'Not Found'], 404);
        }
    }

    /**
     * Rattache une liste de tags avec  une personneLien.
     *
     * @return JsonResponse
     */
    public function insertTags(Request $request)
    {
        try {
            $this->personneService->insertTags(json_decode($request->getContent(), true));

            return new JsonResponse(['content' => 'ok'], 201);
        } catch (Throwable $e) {
            return new JsonResponse(['error' => 'Internal Error'], 500);
        }
    }

    /**
     * Endpoint récupérer la liste des liens d'une personne physique.
     *
     * @param $uuid
     *
     * @return JsonResponse
     *
     * @throws Exception
     */
    public function getLiens($uuid)
    {
        $resultat = $this->personneLienRepository->prepareLiensRequete($uuid);
        try {
            $resultat->execute();

            return new JsonResponse($resultat->fetchAllAssociative());
        } catch (\Doctrine\DBAL\Driver\Exception $e) {
            return new JsonResponse(['error' => 'Internal Error'], 500);
        }
    }

    /**
     * Endpoint récupérer la liste des champs custom d'une personne et de leurs valeurs.
     *
     * @param $uuid
     *
     * @return JsonResponse
     *
     * @throws Throwable
     */
    public function getChamps($uuid)
    {
        try {
            $resultat = $this->personneService->getChamps($uuid);

            return new JsonResponse($resultat, 200);
        } catch (Throwable $e) {
            return new JsonResponse(['error' => 'Internal Error'], 500);
        }
    }

    public function getListePersonnesUsers()
    {
        try {
            $personnes = $this->getDoctrine()->getRepository(PersonnePhysique::class)
                ->findBy(['isUser' => true]);
            $result = [];
            /**
             * @var PersonnePhysique $personne
             */
            foreach ($personnes as $personne) {
                $item = [];
                $lien = $this->getDoctrine()->getRepository(PersonneLien::class)
                    ->findBy(['personnePhysique' => $personne, 'type' => 'physique']);
                $item['uuid'] = $lien[0]->getUuid()->toString();
                $item['civilite'] = $personne->getCivilite();
                $item['prenom'] = $personne->getPrenom();
                $item['nom'] = $personne->getNom();
                $result[] = $item;
            }

            return new JsonResponse($result, 200);
        } catch (Throwable $e) {
            return new JsonResponse(['error' => 'Internal Error'], 500);
        }
    }

    /**
     * Permet de récupérer civilité / nom / prenom de l'utilisateur connecté.
     */
    public function getPersonneCourante(): JsonResponse
    {
        try {
            $user = $this->getUser();
            $personne = $this->getDoctrine()->getRepository(PersonnePhysique::class)
                ->findBy(['user' => $user]);
            $donneesPersonne = [];
            /**
             * @var PersonnePhysique $personne
             */
            foreach ($personne as $current) {
                $donneesPersonne['uuid'] = $current->getUuid();
                $donneesPersonne['civilite'] = $current->getCivilite();
                $donneesPersonne['nom'] = $current->getNom();
                $donneesPersonne['prenom'] = $current->getPrenom();
            }

            return new JsonResponse($donneesPersonne, 200);
        } catch (\Doctrine\DBAL\Driver\Exception $e) {
            return new JsonResponse(['error' => 'Internal Error'], 500);
        }
    }

    /**
     * Endpoint pour récupérer la liste  des statuts disponibles pour une personne (pp / pm / lien).
     *
     * @return JsonResponse
     *
     * @throws Exception
     */
    public function getStatuts()
    {
        $resultat = $this->personneLienRepository->prepareListeStatuts();
        try {
            $resultat->execute();

            return new JsonResponse($resultat->fetchAllAssociative());
        } catch (\Doctrine\DBAL\Driver\Exception $e) {
            return new JsonResponse(['error' => 'Internal Error'], 500);
        }
    }

    /**
     * Mets à jour une personne physique.
     *
     * @return JsonResponse
     */
    public function update($uuid, Request $request)
    {
        try {
            $update = $this->personneService->update(json_decode($request->getContent(), true));
            if (isset($update['erreur'])) {
                return new JsonResponse($update, 400);
            }

            return new JsonResponse(['content' => $update, 'uuid' => $uuid], 201);
        } catch (Throwable $e) {
            return new JsonResponse(['erreur' => 'Internal Error'], 500);
        }
    }

    /**
     * Ajoute une personne physique.
     *
     * @return JsonResponse
     */
    public function add(Request $request)
    {
        $create = $this->personneService->create(json_decode($request->getContent(), true));
        if (isset($create['erreur'])) {
            return new JsonResponse($create, 400);
        }

        return new JsonResponse(['content' => $create], 201);
    }

    /**
     * Ajoute une personne fonction.
     *
     * @return JsonResponse
     */
    public function addPersonneFonction(Request $request)
    {
        $create = $this->personneService->createPersonneFonction(json_decode($request->getContent(), true));
        if (isset($create['erreur'])) {
            return new JsonResponse($create, 400);
        }

        return new JsonResponse(['content' => $create], 201);
    }

    /**
     * Endpoint pour récupérer le couple uuid / libelle  des personnes (pp / pm / lien) pour remplir un select.
     *
     * @param  $type
     *
     * @return JsonResponse
     *
     * @throws Exception
     */
    public function getPersonnesSelect($type)
    {
        try {
            $resultat = $this->helperService->preparePersonnesSelect($type);
            $resultat->execute();

            return new JsonResponse($resultat->fetchAllAssociative());
        } catch (Throwable $e) {
            var_dump($e->getMessage());

            return new JsonResponse(['error' => 'Internal Error'], 500);
        }
    }

    /**
     * Endpoint pour récupérer le couple uuid / libelle  des fonctions pour les personnes "fonction",
     * pour remplir un select.
     *
     * @return JsonResponse
     *
     * @throws Exception
     */
    public function getPersonnesFonctions()
    {
        try {
            $resultat = $this->helperService->preparePersonnesFonctions();
            $resultat->execute();

            return new JsonResponse($resultat->fetchAllAssociative());
        } catch (Throwable $e) {
            var_dump($e->getMessage());

            return new JsonResponse(['error' => 'Internal Error'], 500);
        }
    }
}
