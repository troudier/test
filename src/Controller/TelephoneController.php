<?php

namespace App\Controller;

use App\Service\TelephoneService;
use Doctrine\DBAL\Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class TelephoneController extends AbstractController
{

    private $telephoneService;

    public function __construct(TelephoneService $telephoneService)
    {
        $this->telephoneService = $telephoneService;
    }

    /**
     * Endpoint pour récupérer la liste des tags disponibles, filtrés ou non
     *
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function getIndicatifs(Request $request)
    {
        try {
            return new JsonResponse( $this->telephoneService->getIndicatifs());
        } catch (\Doctrine\DBAL\Driver\Exception $e) {
            return new JsonResponse(['error' => 'Internal Error'], 500);

        }
    }
}