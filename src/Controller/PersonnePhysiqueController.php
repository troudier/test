<?php

namespace App\Controller;

use App\Service\Personne\PersonnePhysiqueService;
use App\Service\Personne\PersonneService;
use Doctrine\DBAL\Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class PersonnePhysiqueController extends AbstractController
{

    private $personnePhysiqueService;

    private $personneService;

    public function __construct(
        PersonnePhysiqueService $personnePhysiqueService,
        PersonneService $personneService
    )
    {
        $this->personnePhysiqueService = $personnePhysiqueService;
        $this->personneService = $personneService;
    }

    /**
     * Retourne les champs requis et recommandés pour la création d'une personne physique
     * @return JsonResponse
     */
    public function getChampsCreation()
    {
        return new JsonResponse(
            [
                'requis' => $this->personnePhysiqueService::CHAMPS_REQUIS,
                'recommandes' => $this->personnePhysiqueService::CHAMPS_RECOMMANDE
            ],
            200);
    }

    /**
     * Endpoint pour récupérer la liste  simple (pour un select2 par ex) des personnes physiques
     *
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function getListe(Request $request)
    {

        $resultat = $this->personnePhysiqueService->prepareListePersonnesPhysiques($request->query);
        try {
            $resultat->execute();
            return new JsonResponse($resultat->fetchAllAssociative());
        } catch (\Doctrine\DBAL\Driver\Exception $e) {
            return new JsonResponse(['error' => 'Internal Error'], 500);

        }
    }

    /**
     * Endpoint pour récupérer la liste  des titres disponibles pour une personne physique
     *
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function getTitres(Request $request)
    {

        $resultat = $this->personnePhysiqueService->prepareListeTitres($request->query);
        try {
            $resultat->execute();
            return new JsonResponse($resultat->fetchAllAssociative());
        } catch (\Doctrine\DBAL\Driver\Exception $e) {
            return new JsonResponse(['error' => 'Internal Error'], 500);

        }
    }


}
