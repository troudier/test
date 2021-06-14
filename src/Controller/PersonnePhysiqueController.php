<?php

namespace App\Controller;

use App\Service\Personne\PersonnePhysiqueService;
use Doctrine\DBAL\Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

class PersonnePhysiqueController extends AbstractController
{
    private $personnePhysiqueService;

    public function __construct(
        PersonnePhysiqueService $personnePhysiqueService
    ) {
        $this->personnePhysiqueService = $personnePhysiqueService;
    }

    /**
     * Retourne les champs requis et recommandés pour la création d'une personne physique.
     *
     * @return JsonResponse
     */
    public function getChampsCreation()
    {
        return new JsonResponse(
            [
                'requis' => $this->personnePhysiqueService::CHAMPS_REQUIS,
                'recommandes' => $this->personnePhysiqueService::CHAMPS_RECOMMANDE,
            ],
            200
        );
    }

    /**
     * Endpoint pour récupérer la liste  simple (pour un select2 par ex) des personnes physiques.
     *
     * @return JsonResponse
     *
     * @throws Exception
     */
    public function getListe()
    {
        $resultat = $this->personnePhysiqueService->prepareListePersonnesPhysiques();
        try {
            $resultat->execute();

            return new JsonResponse($resultat->fetchAllAssociative());
        } catch (\Doctrine\DBAL\Driver\Exception $e) {
            return new JsonResponse(['error' => 'Internal Error'], 500);
        }
    }

    /**
     * Endpoint pour récupérer la liste  des titres disponibles pour une personne physique.
     *
     * @return JsonResponse
     *
     * @throws Exception
     */
    public function getTitres()
    {
        $resultat = $this->personnePhysiqueService->prepareListeTitres();
        try {
            $resultat->execute();

            return new JsonResponse($resultat->fetchAllAssociative());
        } catch (\Doctrine\DBAL\Driver\Exception $e) {
            return new JsonResponse(['error' => 'Internal Error'], 500);
        }
    }
}
