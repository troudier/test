<?php

namespace App\Controller;

use App\Repository\SegmentRepository;
use App\Service\HelperService;
use App\Service\Segment\SegmentService;
use Doctrine\DBAL\Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class SegmentController extends AbstractController
{
    private SegmentService  $segmentService;

    private SegmentRepository $segmentRepository;

    private HelperService $helperService;

    public function __construct(
        SegmentService $segmentService,
        SegmentRepository $segmentRepository,
        HelperService $helperService
    ) {
        $this->segmentService = $segmentService;
        $this->segmentRepository = $segmentRepository;
        $this->helperService = $helperService;
    }

    /**
     * Endpoint pour la vue "cartes" des segments, renvoie une liste de segments.
     *
     * @return JsonResponse
     *
     * @throws Exception
     */
    public function liste(Request $request)
    {
        $resultat = $this->segmentRepository->prepareCartesRequete($request->query);
        try {
            $resultat->execute();

            return new JsonResponse($resultat->fetchAllAssociative());
        } catch (\Doctrine\DBAL\Driver\Exception $e) {
            return new JsonResponse(['error' => 'Internal Error'], 500);
        }
    }

    /**
     * Endpoint pour la vue "fiche" d'un segment.
     *
     * @return JsonResponse
     *
     * @throws Exception
     */
    public function fiche($uuid)
    {
        try {
            $resultat = $this->segmentRepository->getFiche($uuid);

            return new JsonResponse($resultat);
        } catch (\Doctrine\DBAL\Driver\Exception $e) {
            return new JsonResponse(['error' => 'Internal Error'], 500);
        }
    }

    /**
     * Renvoie les résultats à un instant T d'un segment.
     *
     * @return JsonResponse
     *
     * @throws Exception
     */
    public function resultats($uuid)
    {
        try {
            $resultat = $this->segmentService->getResultats($uuid);

            return new JsonResponse($resultat);
        } catch (\Doctrine\DBAL\Driver\Exception $e) {
            return new JsonResponse(['error' => 'Internal Error'], 500);
        }
    }

    /**
     * Renvoie le nombre de contact par filtre (index : postion du filtre dans la liste).
     *
     * @return JsonResponse
     *
     * @throws Exception
     */
    public function nbContacts($uuid, $index)
    {
        try {
            $resultat = $this->segmentService->nbContacts($uuid, $index);

            return new JsonResponse($resultat);
        } catch (\Doctrine\DBAL\Driver\Exception $e) {
            return new JsonResponse(['error' => 'Internal Error'], 500);
        }
    }

    /**
     * Renvoie la liste des nombres de contact.
     *
     * @return JsonResponse
     *
     * @throws Exception
     */
    public function listeNbContacts($uuid)
    {
        try {
            $resultat = $this->segmentService->listeNbContacts($uuid);

            return new JsonResponse($resultat);
        } catch (\Doctrine\DBAL\Driver\Exception $e) {
            return new JsonResponse(['error' => 'Internal Error'], 500);
        }
    }

    /**
     * Renvoie les champs requêtables sur les segments.
     *
     * @return JsonResponse
     *
     * @throws Exception
     */
    public function getChamps()
    {
        try {
            $resultat = $this->helperService->getChamps();

            return new JsonResponse($resultat);
        } catch (\Doctrine\DBAL\Driver\Exception $e) {
            return new JsonResponse(['error' => 'Internal Error'], 500);
        }
    }

    /**
     * Renvoie les opérateurs disponibles pour les segments.
     *
     * @return JsonResponse
     *
     * @throws Exception
     */
    public function getOperateurs()
    {
        try {
            $resultat = $this->helperService->getOperateurs();

            return new JsonResponse($resultat);
        } catch (\Doctrine\DBAL\Driver\Exception $e) {
            return new JsonResponse(['error' => 'Internal Error'], 500);
        }
    }

    /**
     * Mets à jour un segment.
     *
     * @return JsonResponse
     */
    public function update($uuid, Request $request)
    {
        $update = $this->segmentService->update(json_decode($request->getContent(), true));
        if (isset($update['erreur'])) {
            return new JsonResponse($update, 400);
        }

        return new JsonResponse(['content' => $update, 'uuid' => $uuid], 201);
    }

    /**
     * Met à jour le nombre de résultats d'un segment en cours de modification.
     *
     * @return JsonResponse
     */
    public function calculer($uuid, Request $request)
    {
        $update = $this->segmentService->calculer(json_decode($request->getContent(), true));
        if (isset($update['erreur'])) {
            return new JsonResponse($update, 400);
        }

        return new JsonResponse(['content' => $update, 'uuid' => $uuid], 201);
    }

    /**
     * Retourne les résultats d'un segment en cours de modification.
     *
     * @return JsonResponse
     */
    public function liveResultats($uuid, Request $request)
    {
        $update = $this->segmentService->liveResultats(json_decode($request->getContent(), true));
        if (isset($update['erreur'])) {
            return new JsonResponse($update, 400);
        }

        return new JsonResponse(['content' => $update, 'uuid' => $uuid], 201);
    }
}
