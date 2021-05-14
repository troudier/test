<?php
namespace App\Controller;

use App\Service\ChampService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Throwable;


class ChampController extends AbstractController
{

    private ChampService $champService;

    public function __construct(ChampService $champService)
    {
        $this->champService = $champService;
    }
    /**
     * Endpoint récupérer la liste des champs custom disponbiles pour un type de personne
     *
     * @param $uuid
     * @return JsonResponse
     * @throws Throwable
     */
    public function getChampsDisponibles($target)
    {
        try {
            $resultat = $this->champService->getChampsDisponibles($target);
            return new JsonResponse($resultat, 200);
        } catch (Throwable $e) {
            return new JsonResponse(['error' => 'Internal Error'], 500);

        }
    }
}