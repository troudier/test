<?php

namespace App\Controller;

use App\Service\OrigineService;
use Doctrine\DBAL\Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class OrigineController extends AbstractController
{
    private $origineService;

    public function __construct(OrigineService $origineService)
    {
        $this->origineService = $origineService;
    }

    /**
     * Endpoint pour récupérer la liste des origines disponibles, filtrés ou non.
     *
     * @return JsonResponse
     *
     * @throws Exception
     */
    public function getOrigines(Request $request)
    {
        $resultat = $this->origineService->prepareListeOrigines($request->query);
        try {
            $resultat->execute();

            return new JsonResponse($resultat->fetchAllAssociative());
        } catch (\Doctrine\DBAL\Driver\Exception $e) {
            return new JsonResponse(['error' => 'Internal Error'], 500);
        }
    }
}
