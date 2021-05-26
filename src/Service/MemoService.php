<?php

namespace App\Service;

use App\Entity\Memo;
use App\Entity\PersonneLien;
use DateTime;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class MemoService
{
    /**
     * @var Connection
     */
    private $connexion;

    /**
     * @param EntityManagerInterface $em
     */
    private $em;
    /**
     * @param TokenStorageInterface $tokenStorage
     */
    private $tokenStorage;

    public function __construct(
        EntityManagerInterface $em,
        TokenStorageInterface $tokenStorage
    ) {
        $this->em = $em;
        $this->connexion = $this->em->getConnection();
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * Ajoute un mémo en relation avec une personne lien.
     *
     * @param $query
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function add($data): Memo
    {
        $memo = new Memo();
        $memo->setTexte(addslashes($data['texte']));
        $memo->setDateCreation(new DateTime());
        $memo->setUserCreation($this->tokenStorage->getToken()->getUser());
        $memo->setDateModification(new DateTime());
        $memo->setUserModification($this->tokenStorage->getToken()->getUser());
        $personneRepository = $this->em->getRepository(PersonneLien::class);
        $lien = $personneRepository->findBy(['uuid' => $data['uuid']]);
        if (isset($lien[0])) {
            $memo->setLien($lien[0]);
        }
        if ($data['persist']) {
            $this->em->persist($memo);
            $this->em->flush();
        }

        return $memo;
    }

    /**
     * @param PersonneLien $lien
     * @param array        $memosJson
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function update($lien, $memosJson)
    {
        /** @var Memo[] $memos */
        $memos = $this->em->getRepository(Memo::class)
            ->findBy(['lien' => $lien]);
        $nouveaux = [];
        foreach ($memosJson as $item) {
            $found = false;
            foreach ($memos as $memo) {
                if ($item['uuid'] === $memo->getUuid()->toString()) {
                    $found = true;
                }
            }
            if (!$found) {
                $nouveaux[] = $item['texte'];
            }
        }
        foreach ($nouveaux as $texte) {
            $this->add([
                'uuid' => $lien->getUuid()->toString(),
                'texte' => $texte,
                'persist' => true,
                ]);
        }
    }
}
