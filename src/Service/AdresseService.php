<?php

namespace App\Service;

use App\Entity\Adresse;
use App\Entity\Champ;
use App\Entity\LienAdresse;
use App\Entity\LienMail;
use App\Entity\LienSite;
use App\Entity\LienTelephone;
use App\Entity\PersonneLien;
use App\Entity\Site;
use DateTime;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class AdresseService
{

    public array $typeMapping = [
        '-1' => 'OUTPUT',
        '0' => 'NPAI',
        '1' => 'Adresse',
        '2' => 'Pro',
        '3' => 'Domicile',
    ];
    private EntityManagerInterface $em;

    private TokenStorageInterface $tokenStorage;

    /**
     *
     * @param EntityManagerInterface $em
     */
    public function __construct(
        EntityManagerInterface $em,
        TokenStorageInterface $tokenStorage

    )
    {
        $this->em = $em;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * Créé une Adresse
     *
     * @param $email
     * @return Adresse
     * @throws \Exception
     */
    private function createAdresse($data): Adresse
    {
        $adresse = new Adresse();
        $adresse->setUuid(Uuid::uuid4());
        $adresse->setLigne1($data['ligne1']);
        $adresse->setLigne2($data['ligne2']);
        $adresse->setLigne3($data['ligne3']);
        $adresse->setCp($data['cp']);
        $adresse->setVille($data['ville']);
        $adresse->setPays($data['pays']);
        $adresse->setCedexLibelle($data['cedexLibelle']);
        $adresse->setCedexCode($data['cedexCode']);
        $adresse->setDateCreation(new DateTime());
        $adresse->setUserCreation($this->tokenStorage->getToken()->getUser());
        $adresse->setDateModification(new DateTime());
        $adresse->setUserModification($this->tokenStorage->getToken()->getUser());
        $this->em->persist($adresse);
        return $adresse;
    }

    /**
     * Ajoute une adresse en relation avec une personne lien
     *
     * @param $uuid
     * @param $email
     * @throws \Exception
     */
    public function add($uuid, $adresse, $type = "1", $principal = false)
    {
        $personneLien = $this->em->getRepository(PersonneLien::class)
            ->findBy(['uuid' => $uuid]);
        $lien = new LienAdresse();
        $lien->setUuid(Uuid::uuid4());
        $lien->setAdresse($this->createAdresse($adresse));
        $lien->setLien($personneLien[0]);
        $lien->setPrincipal($principal);
        $lien->setType((int)$type);
        $this->em->persist($lien);
        $this->em->flush();
    }

    /**
     * Récupère la liste des adresses d'une PersonneLien
     *
     * @param $uuid
     * @return array
     */
    public function getAdresses($uuid)
    {
        $adresses = [];
        /** @var PersonneLien[] $lien */
        $lien = $this->em->getRepository(PersonneLien::class)->findBy(['uuid' => $uuid]);
        if ($lien[0]) {
            /** @var LienAdresse $lienAdresse */
            foreach ($lien[0]->getAdresses() as $lienAdresse) {
                $data = [];
                $data['uuid'] = $lienAdresse->getUuid()->toString();
                $data['principal'] = $lienAdresse->getPrincipal();
                $data['type'] = $this->typeMapping[$lienAdresse->getType()];
                $data['type_id'] = $lienAdresse->getType();
                /** @var Adresse $adresse */
                $adresse = $lienAdresse->getAdresse();
                $data['ligne1'] = $adresse->getLigne1();
                $data['ligne2'] = $adresse->getLigne2();
                $data['ligne3'] = $adresse->getLigne3();
                $data['cp'] = $adresse->getCp();
                $data['ville'] = $adresse->getVille();
                $data['pays'] = $adresse->getPays();
                $data['cedexCode'] = $adresse->getCedexCode();
                $data['cedexLibelle'] = $adresse->getCedexLibelle();
                $adresses[] = $data;
            }
        }
        return $adresses;
    }

    /**
     * Met à jour la liste des adresses d'une PersonneLien
     *
     * @param $lien
     * @param $data
     * @throws \Exception
     */
    public function updatePersonneAdresses($lien, $data)
    {
        $aSupprimer = [];
        $liensAdresses = $this->em->getRepository(LienAdresse::class)->findBy(['lien' => $lien]);
        /** @var LienAdresse $lienAdresse */
        foreach ($liensAdresses as $lienAdresse) {
            $existe = false;
            foreach ($data as $id => $item) {
                if ($item['uuid'] === $lienAdresse->getUuid()->toString()) {
                    $existe = true;
                    $lienAdresse->setPrincipal((bool)$item['principal']);
                    $lienAdresse->setType($this->getTypeId($item['type']));
                    $this->updateAdresse($lienAdresse->getAdresse(), $item);
                    $this->em->persist($lienAdresse);
                    unset($data[$id]);
                }
            }
            if (!$existe) {
                $aSupprimer[] = $lienAdresse;
            }
        }
        foreach ($data as $item) {
            $this->add(
                $lien->getUuid()->toString(),
                $item,
                $this->getTypeId($item['type']),
                $item['principal']
            );
        }
        foreach ($aSupprimer as $item) {
            $this->em->remove($item);
        }
    }

    /**
     * Récupère la liste des types disponibles pour les adresses
     *
     * @return array
     */
    public function getAdressesTypes()
    {
        $data = [];
        foreach ($this->typeMapping as $id => $item) {
            $data['adresses'][] = ['id' => $id, 'text' => $item];
        }
        return $data;
    }

    /**
     *
     * Retourne l'id d'un type d'une adresse depuis son libellé
     *
     * @param $type
     * @return string
     */
    private function getTypeId($type): string
    {
        foreach ($this->typeMapping as $id => $texte) {
            if ($texte === $type) {
                return $id;
            }
        }
        // Par défaut, on renvoie le type "Adresse"
        return '1';
    }

    /**
     * Met à jour une adresse
     *
     * @param Adresse $adresse
     * @param $data
     */
    private function updateAdresse($adresse, $data){
        $modifie = false;
        if($adresse->getLigne1() !== $data['ligne1']){
            $modifie = true;
            $adresse->setLigne1($data['ligne1']);
        }
        if($adresse->getLigne2() !== $data['ligne2']){
            $modifie = true;
            $adresse->setLigne2($data['ligne2']);
        }
        if($adresse->getLigne3() !== $data['ligne3']){
            $modifie = true;
            $adresse->setLigne3($data['ligne3']);
        }
        if($adresse->getCp() !== $data['cp']){
            $modifie = true;
            $adresse->setCp($data['cp']);
        }
        if($adresse->getVille() !== $data['ville']){
            $modifie = true;
            $adresse->setVille($data['ville']);
        }
        if($adresse->getPays() !== $data['pays']){
            $modifie = true;
            $adresse->setPays($data['pays']);
        }
        if($adresse->getCedexCode() !== $data['cedexCode']){
            $modifie = true;
            $adresse->setCedexCode($data['cedexCode']);
        }
        if($adresse->getCedexLibelle() !== $data['cedexLibelle']){
            $modifie = true;
            $adresse->setCedexLibelle($data['cedexLibelle']);
        }
        if($modifie){
            $adresse->setDateModification(new DateTime());
            $adresse->setUserModification($this->tokenStorage->getToken()->getUser());
            $this->em->persist($adresse);
        }
    }
}