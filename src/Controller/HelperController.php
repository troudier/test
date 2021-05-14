<?php

namespace App\Controller;


use App\Service\HelperService;
use App\Service\Segment\SegmentService;
use Doctrine\DBAL\Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

class HelperController extends AbstractController
{

    private HelperService  $helperService;

    public function __construct(HelperService $helperService)
    {
        $this->helperService = $helperService;
    }


    /**
     * Renvoie la liste des valeurs de l'entité $type, sous la forme [uuuid, libelle]
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function getDictionnaire($type)
    {
        try {
            $resultat = $this->helperService->getDictionnaire($type);
            return new JsonResponse($resultat);
        } catch (\Doctrine\DBAL\Driver\Exception $e) {
            return new JsonResponse(['error' => 'Internal Error'], 500);

        }
    }

    /**
     * Renvoie les intervenants liés à une entité
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function getIntervenants($uuid, $type)
    {
        try {
            $resultat = $this->helperService->getListeIntervenants($uuid, $type);
            return new JsonResponse($resultat);
        } catch (\Doctrine\DBAL\Driver\Exception $e) {
            return new JsonResponse(['error' => 'Internal Error'], 500);

        }
    }
}
