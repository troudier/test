<?php

namespace App\Controller;


use App\Entity\PersonnePhysique;
use App\Service\MemoService;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

class MemoController extends AbstractController
{

    private $memoService;

    public function __construct(MemoService $memoService)
    {
        $this->memoService = $memoService;
    }

    /**
     * Endpoint pour récupérer la liste des tags disponibles, filtrés ou non
     *
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function add(Request $request)
    {
        try {
            $memo = $this->memoService->add(json_decode($request->getContent(), TRUE));
            $createur = $this->getDoctrine()->getRepository(PersonnePhysique::class)->findBy(['user' => $memo->getUserCreation()]);

            return new JsonResponse([
                'uuid' => $memo->getUuid()->toString(),
                'texte' => $memo->getTexte(),
                'date' => $memo->getDateCreation()->format('Y-m-d H:i:s'),
                'creationPrenom' => $createur[0]->getPrenom(),
                'creationNom' => $createur[0]->getNom()
            ], 201);
        } catch (Throwable $e) {

            return new JsonResponse(['error' => 'Internal Error'], 500);
        }
    }

}
