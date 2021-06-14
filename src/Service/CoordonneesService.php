<?php

namespace App\Service;

use App\Entity\PersonneLien;
use Doctrine\ORM\EntityManagerInterface;

class CoordonneesService
{
    private EntityManagerInterface $em;

    public TelephoneService $telephoneService;

    public EmailService $emailService;

    public SiteService $siteService;

    public function __construct(
        EntityManagerInterface $em,
        TelephoneService $telephoneService,
        EmailService $emailService,
        SiteService $siteService
    ) {
        $this->em = $em;
        $this->telephoneService = $telephoneService;
        $this->emailService = $emailService;
        $this->siteService = $siteService;
    }

    /**
     * Récupère la liste des coordonnées d'une PersonneLien.
     *
     * @param $uuid
     *
     * @return array
     */
    public function getCoordonnees($uuid)
    {
        $data = [];
        /** @var PersonneLien[] $lien */
        $lien = $this->em->getRepository(PersonneLien::class)->findBy(['uuid' => $uuid]);
        if ($lien[0]) {
            $data = $this->telephoneService->getPersonneTelephones($lien[0]);
            if (count($this->emailService->getPersonneEmails($lien[0])) > 0) {
                $data['emails'] = $this->emailService->getPersonneEmails($lien[0]);
            }
            if (count($this->siteService->getPersonneSites($lien[0])) > 0) {
                $data['sites'] = $this->siteService->getPersonneSites($lien[0]);
            }
        }

        return $data;
    }

    /**
     * Met à jour la liste des coordonnées d'une PersonneLien.
     *
     * @param $lien
     * @param $data
     *
     * @throws \Exception
     */
    public function updatePersonneCoordonnees($lien, $data)
    {
        if (isset($data['emails'])) {
            $this->emailService->updatePersonneEmails($lien, $data['emails']);
        }
        if (isset($data['sites'])) {
            $this->siteService->updatePersonneSites($lien, $data['sites']);
        }
        if (isset($data['telephones'])) {
            $this->telephoneService->updatePersonneTelephones($lien, $data);
        }
    }

    /**
     * Récupère la liste des types disponibles pour les différentes coordonnées.
     *
     * @return array
     */
    public function getCoordonneesTypes()
    {
        $data = [];
        foreach ($this->telephoneService->typeMapping as $id => $item) {
            $data['telephones'][] = ['id' => $id, 'text' => $item];
        }
        foreach ($this->emailService->typeMapping as $id => $item) {
            $data['emails'][] = ['id' => $id, 'text' => $item];
        }
        foreach ($this->siteService->typeMapping as $id => $item) {
            $data['sites'][] = ['id' => $id, 'text' => $item];
        }

        return $data;
    }
}
