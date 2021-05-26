<?php

namespace App\Controller;

use App\Service\AdresseService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Throwable;

class AdresseController extends AbstractController
{
    private AdresseService $adresseService;

    public function __construct(AdresseService $adresseService)
    {
        $this->adresseService = $adresseService;
    }

    /**
     * Endpoint récupérer la liste des adresses disponbiles pour une PersonneLien.
     *
     * @param $uuid
     *
     * @return JsonResponse
     *
     * @throws Throwable
     */
    public function getAdresses($uuid)
    {
        try {
            $resultat = $this->adresseService->getAdresses($uuid);

            return new JsonResponse($resultat, 200);
        } catch (Throwable $e) {
            dump($e->getMessage());

            return new JsonResponse(['error' => 'Internal Error'], 500);
        }
    }

    /**
     * Endpoint récupérer la liste destypes disponbiles pour les adresses.
     *
     * @return JsonResponse
     *
     * @throws Throwable
     */
    public function getAdressesTypes()
    {
        try {
            $resultat = $this->adresseService->getAdressesTypes();

            return new JsonResponse($resultat, 200);
        } catch (Throwable $e) {
            dump($e->getMessage());

            return new JsonResponse(['error' => 'Internal Error'], 500);
        }
    }
}
