<?php

namespace App\Controller;

use App\Service\CoordonneesService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Throwable;

class CoordonneesController extends AbstractController
{
    private CoordonneesService $coordonneesService;

    public function __construct(CoordonneesService $coordonneesService)
    {
        $this->coordonneesService = $coordonneesService;
    }

    /**
     * Endpoint récupérer la liste des champs custom disponbiles pour un type de personne.
     *
     * @param $uuid
     *
     * @return JsonResponse
     *
     * @throws Throwable
     */
    public function getCoordonnees($uuid)
    {
        try {
            $resultat = $this->coordonneesService->getCoordonnees($uuid);

            return new JsonResponse($resultat, 200);
        } catch (Throwable $e) {
            dump($e->getMessage());

            return new JsonResponse(['error' => 'Internal Error'], 500);
        }
    }

    /**
     * Endpoint récupérer la liste destypes disponbiles pour les différentes coordonnées.
     *
     * @return JsonResponse
     *
     * @throws Throwable
     */
    public function getCoordonneesTypes()
    {
        try {
            $resultat = $this->coordonneesService->getCoordonneesTypes();

            return new JsonResponse($resultat, 200);
        } catch (Throwable $e) {
            dump($e->getMessage());

            return new JsonResponse(['error' => 'Internal Error'], 500);
        }
    }
}
