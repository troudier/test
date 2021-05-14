<?php


namespace App\Controller;

use App\Service\Personne\PersonneMoraleService;
use Doctrine\DBAL\Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;


class PersonneMoraleController extends AbstractController
{
    private PersonneMoraleService $personneMoraleService;
    private PersonneService $personneService;


    public function __construct(
        PersonneMoraleService $personneMoraleService
    )
    {
        $this->personneMoraleService = $personneMoraleService;
    }

    /**
     * Retourne les champs requis et recommandés pour la création d'une personne morale
     * @return JsonResponse
     */
    public function getChampsCreation()
    {
        return new JsonResponse(
            [
                'requis' => $this->personneMoraleService::CHAMPS_REQUIS,
                'recommandes' => $this->personneMoraleService::CHAMPS_RECOMMANDE
            ],
            200);
    }

    /**
     * Endpoint pour récupérer la liste des formes juridiques d'une personne morale
     *
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function getFormeJuridique(Request $request)
    {
        $resultat = $this->personneMoraleService->prepareListeFormeJuridique($request->query);
        try {
            $resultat->execute();
            return new JsonResponse($resultat->fetchAllAssociative());
        } catch (\Doctrine\DBAL\Driver\Exception $e) {
            return new JsonResponse(['error' => 'Internal Error'], 500);
        }
    }

    /**
     * Endpoint pour récupérer la liste des effectif
     *
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function getEffectif(Request $request)
    {
        $resultat = $this->personneMoraleService->prepareListeEffectif($request->query);
        try {
            $resultat->execute();
            return new JsonResponse($resultat->fetchAllAssociative());
        } catch (\Doctrine\DBAL\Driver\Exception $e) {
            return new JsonResponse(['error' => 'Internal Error'], 500);
        }
    }

    /**
     * Endpoint pour récupérer la liste des chiffres d'affaires
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getChiffreAffaire(Request $request)
    {
        $resultat = $this->personneMoraleService->prepareListeChiffreAffaire($request->query);
        try {
        $resultat->execute();
        return new JsonResponse($resultat->fetchAllAssociative());
        } catch (\Doctrine\DBAL\Driver\Exception $e) {
            return new JsonResponse(['error' => 'Internal Error'], 500);
        }
    }

    /**
     * Endpoint pour récupérer la liste des organisations parentes
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getOrganisationParente(Request $request)
    {
        $resultat = $this->personneMoraleService->prepareListeOrganisationParente($request->query);
        try {
            $resultat->execute();
            return new JsonResponse($resultat->fetchAllAssociative());
        } catch (\Doctrine\DBAL\Driver\Exception $e) {
            return new JsonResponse(['error' => 'Internal Error'], 500);
        }
    }

}
