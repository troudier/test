<?php

namespace App\Controller;

use App\Service\TagService;
use Doctrine\DBAL\Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class TagController extends AbstractController
{
    private $tagService;

    public function __construct(TagService $tagService)
    {
        $this->tagService = $tagService;
    }

    /**
     * Endpoint pour récupérer la liste des tags disponibles, filtrés ou non.
     *
     * @return JsonResponse
     *
     * @throws Exception
     */
    public function getTags(Request $request)
    {
        $resultat = $this->tagService->prepareListeTags($request->query);
        try {
            $resultat->execute();

            return new JsonResponse($resultat->fetchAllAssociative());
        } catch (\Doctrine\DBAL\Driver\Exception $e) {
            return new JsonResponse(['error' => 'Internal Error'], 500);
        }
    }
}
