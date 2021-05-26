<?php

namespace App\Controller;

use App\Service\EvenementService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Throwable;

class EvenementController extends AbstractController
{
    private EvenementService $evenementService;

    public function __construct(EvenementService $evenementService)
    {
        $this->evenementService = $evenementService;
    }

    /**
     * Récupère la liste des évènements liés à une PersonneLien.
     *
     * @param $uuid
     *
     * @return JsonResponse
     *
     * @throws Throwable
     */
    public function getPersonneEvenements($uuid)
    {
        try {
            $resultat = $this->evenementService->getPersonneEvenements($uuid);

            return new JsonResponse($resultat, 200);
        } catch (Throwable $e) {
            dump($e->getMessage());

            return new JsonResponse(['error' => 'Internal Error'], 500);
        }
    }

    /**
     * Ajoute un nouvel événement lié à une PersonneLien.
     *
     * @return JsonResponse
     */
    public function add(Request $request)
    {
        try {
            $evenement = $this->evenementService->add(json_decode($request->getContent(), true));

            return new JsonResponse([
                'uuid' => $evenement->getUuid()->toString(),
            ], 201);
        } catch (Throwable $e) {
            return new JsonResponse(['error' => 'Internal Error'], 500);
        }
    }
}
