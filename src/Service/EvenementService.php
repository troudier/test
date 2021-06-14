<?php

namespace App\Service;

use App\Entity\Evenement;
use App\Entity\PersonneLien;
use App\Entity\PersonnePhysique;
use Doctrine\ORM\EntityManagerInterface;

class EvenementService
{
    public array $typeMapping = [
        '10' => 'Appel',
        '20' => 'Mail',
        '30' => 'Rendez-vous',
        '40' => 'Devis',
        '50' => 'Changement de statut',
    ];

    private array $statutMapping = [
        '0' => 'A faire',
        '1' => 'Fait / Envoyé',
        '2' => 'Reçu',
    ];

    private EntityManagerInterface $em;

    private HelperService $helperService;

    public function __construct(
        EntityManagerInterface $em,
        HelperService $helperService
    ) {
        $this->em = $em;
        $this->helperService = $helperService;
    }

    /**
     * Renvoie la liste des emails liés à une PersonneLien.
     */
    public function getPersonneEvenements(string $uuid): array
    {
        $evenements = [];
        $personneLien = $this->em->getRepository(PersonneLien::class)
            ->findBy(['uuid' => $uuid]);
        if (isset($personneLien[0])) {
            /** @var Evenement $evenement */
            foreach ($personneLien[0]->getEvenements() as $evenement) {
                $data = [];
                $data['uuid'] = $evenement->getUuid()->toString();
                $data['libelle'] = $evenement->getTitre();
                $data['description'] = $evenement->getDescription();
                $data['type'] = $this->typeMapping[$evenement->getType()];
                $data['statut'] = $this->statutMapping[$evenement->getStatut()];
                $data['dateEcheance'] = $this->helperService->getDateFormatee($evenement->getDateEcheance());
                $data['dateFait'] = $this->helperService->getDateFormatee($evenement->getDateFait());
                $data['dateCreation'] = $this->helperService->getDateFormatee($evenement->getDateCreation());
                $data['dateModification'] = $this->helperService->getDateFormatee($evenement->getDateModification());
                $createur = $this->em->getRepository(PersonnePhysique::class)->findBy(
                    ['user' => $evenement->getUserCreation()]
                );
                if (isset($createur[0])) {
                    $data['userCreationPrenom'] = $createur[0]->getPrenom();
                    $data['userCreationNom'] = $createur[0]->getNom();
                }
                $modificateur = $this->em->getRepository(PersonnePhysique::class)->findBy(
                    ['user' => $evenement->getUserCreation()]
                );
                if (isset($modificateur[0])) {
                    $data['userModificationPrenom'] = $modificateur[0]->getPrenom();
                    $data['userModificationNom'] = $modificateur[0]->getNom();
                }
                $evenements[] = $data;
            }
        }

        return $evenements;
    }

    public function add($data)
    {
        $type = $this->getTypeId($data['type']);
        $statut = $this->getStatutId($data['statut']);

        return [$type, $statut];
    }

    /**
     * Retourne l'id d'un type d'évenement depuis son libellé.
     *
     * @param $type
     *
     * @return string|null
     */
    private function getTypeId($type)
    {
        foreach ($this->typeMapping as $id => $texte) {
            if ($texte === $type) {
                return $id;
            }
        }

        return null;
    }

    /**
     * Retourne l'id d'un status d'évenement depuis son libellé.
     *
     * @param $type
     *
     * @return string|null
     */
    private function getStatutId($type)
    {
        foreach ($this->statutMapping as $id => $texte) {
            if ($texte === $type) {
                return $id;
            }
        }

        return null;
    }
}
