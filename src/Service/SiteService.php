<?php

namespace App\Service;

use App\Entity\LienSite;
use App\Entity\PersonneLien;
use App\Entity\Site;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class SiteService
{

    public array $typeMapping = [
        '-1'=> 'OUTPUT',
        '0' => 'NPAI',
        '1' => 'Site',
        '2' => 'Pro',
        '3' => 'Perso'
    ];

    private EntityManagerInterface  $em;

    private TokenStorageInterface  $tokenStorage;

    public function __construct(
        EntityManagerInterface $em,
        TokenStorageInterface $tokenStorage
    )
    {
        $this->em = $em;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * Créé un Site depuis une url
     *
     * @param $email
     * @return Site
     * @throws \Exception
     */
    private function createSite($valeur): Site
    {
        $site = new Site();
        $site->setUuid(Uuid::uuid4());
        $site->setValeur($valeur);
        $site->setDateCreation(new DateTime());
        $site->setUserCreation($this->tokenStorage->getToken()->getUser());
        $site->setDateModification(new DateTime());
        $site->setUserModification($this->tokenStorage->getToken()->getUser());
        $this->em->persist($site);
        return $site;
    }

    /**
     * Ajoute un site en relation avec une personne lien
     *
     * @param $uuid
     * @param $site
     * @throws \Exception
     */
    public function add($uuid, $site, $type = "1", $principal = false)
    {
        $personneLien =  $this->em->getRepository(PersonneLien::class)
            ->findBy( ['uuid' => $uuid]);
        $lien = new LienSite();
        $lien->setUuid(Uuid::uuid4());
        $lien->setSite($this->createSite($site));
        $lien->setLien($personneLien[0]);
        $lien->setPrincipal($principal);
        $lien->setType((int) $type);
        $this->em->persist($lien);
        $this->em->flush();
    }

    /**
     * Renvoie la liste des sites liés à une PersonneLien
     *
     * @param PersonneLien $lien
     * @return array
     */
    public function getPersonneSites(PersonneLien $lien): array
    {
        $sites = [];
        /** @var LienSite $lienSite */
        foreach($lien->getSites() as $lienSite){
            $data = [];
            $data['uuid'] = $lienSite->getUuid()->toString();
            $data['principal'] = $lienSite->getPrincipal();
            $data['type'] = $this->typeMapping[$lienSite->getType()];
            $data['type_id'] = $lienSite->getType();
            $data['valeur'] = $lienSite->getSite()->getValeur();
            $sites[] = $data;
        }
        return $sites;
    }


    /**
     * Met à jour la liste des liens Sites d'une personne (et leurs valeurs)
     *
     * @param PersonneLien $lien
     * @param array $data
     * @throws \Exception
     */
    public function updatePersonneSites($lien, $data){
        $aSupprimer = [];
        $lienSites = $this->em->getRepository(LienSite::class)->findBy(['lien' => $lien]);
        /** @var LienSite $lienSite */
        foreach($lienSites as $lienSite){
            $existe = false;
            foreach($data as $id => $item){

                if($item['uuid'] === $lienSite->getUuid()->toString()){
                    $existe = true;
                    $lienSite->setPrincipal((bool) $item['principal']);
                    $lienSite->setType($this->getTypeId($item['type']));
                    if($lienSite->getSite()->getvaleur() !== $item['valeur']){
                        $site = $this->em->getRepository(Site::class)
                            ->findBy(['valeur' => $item['valeur']]);
                        if(isset($site[0])){
                            $lienSite->setSite($site[0]);
                        }else{
                            $lienSite->setSite($this->createSite($item['valeur']));
                        }
                    }
                    $this->em->persist($lienSite);
                    unset($data[$id]);
                }
            }
            if(!$existe){
                $aSupprimer[] = $lienSite;
            }
        }
        foreach($data as $item){
            $this->add(
                $lien->getUuid()->toString(),
                $item['valeur'],
                $this->getTypeId($item['type']),
                $item['principal']
            );
        }
        foreach($aSupprimer as $item){
            $this->em->remove($item);
        }
    }

    /**
     *
     * Retourne l'id d'un type d'E-Mail depuis son libellé
     *
     * @param $type
     * @return string
     */
    private function getTypeId($type): string
    {
        foreach($this->typeMapping as $id => $texte){
            if($texte === $type){
                return $id;
            }
        }
        // Par défaut, on renvoie le type "Site"
        return '1';
    }

}