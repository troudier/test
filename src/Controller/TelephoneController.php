<?php

namespace App\Controller;

use App\Service\TelephoneService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

class TelephoneController extends AbstractController
{
    private $telephoneService;

    public function __construct(TelephoneService $telephoneService)
    {
        $this->telephoneService = $telephoneService;
    }

    /**
     * Endpoint pour récupérer la liste des tags disponibles, filtrés ou non.
     *
     * @return JsonResponse
     */
    public function getIndicatifs()
    {
        try {
            return new JsonResponse($this->telephoneService->getIndicatifs());
        } catch (\Doctrine\DBAL\Driver\Exception $e) {
            return new JsonResponse(['error' => 'Internal Error'], 500);
        }
    }
}
