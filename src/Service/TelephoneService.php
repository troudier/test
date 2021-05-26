<?php

namespace App\Service;

use App\Entity\IndicatifTelephone;
use App\Entity\LienTelephone;
use App\Entity\PersonneLien;
use App\Entity\Telephone;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class TelephoneService
{
    public array $typeMapping = [
        '-1' => 'OUTPUT',
        '0' => 'NPAI',
        '1' => 'Fixe',
        '2' => 'Domicile',
        '3' => 'Portable',
        '4' => 'Fax',
    ];

    private EntityManagerInterface $em;

    private TokenStorageInterface $tokenStorage;

    public function __construct(
        EntityManagerInterface $em,
        TokenStorageInterface $tokenStorage
    ) {
        $this->em = $em;
        $this->tokenStorage = $tokenStorage;
    }

    public function createTelephone($numeroTelephone, $indicatifUuid)
    {
        $telephone = new Telephone();
        $telephone->setUuid(Uuid::uuid4());
        $telephone->setValeur($numeroTelephone);
        $telephone->setDateCreation(new DateTime());
        $telephone->setUserCreation($this->tokenStorage->getToken()->getUser());
        $telephone->setDateModification(new DateTime());
        $telephone->setUserModification($this->tokenStorage->getToken()->getUser());
        if ($indicatifUuid) {
            $indicatif = $this->em->getRepository(IndicatifTelephone::class)
                ->findBy(['uuid' => $indicatifUuid]);
            $telephone->setIndicatif($indicatif[0]);
        }
        $this->em->persist($telephone);

        return $telephone;
    }

    /**
     * Ajoute un numéro de téléphone en relation avec une personne lien.
     *
     * @param $uuid
     * @param $numeroTelephone
     * @param $indicatifUuid
     *
     * @throws \Exception
     */
    public function add($uuid, $numeroTelephone, $indicatifUuid, $type = 1, $principal = false)
    {
        $personneLien = $this->em->getRepository(PersonneLien::class)
            ->findBy(['uuid' => $uuid]);
        $lien = new LienTelephone();
        $lien->setUuid(Uuid::uuid4());
        $lien->setTelephone($this->createTelephone($numeroTelephone, $indicatifUuid));
        $lien->setLien($personneLien[0]);
        $lien->setPrincipal($principal);
        $lien->setType((int) $type);
        $this->em->persist($lien);
        $this->em->flush();
    }

    /**
     * Recupère la liste des indicatifs téléphoniques disponibles.
     */
    public function getIndicatifs(): array
    {
        $return = [];
        /**
         * @var IndicatifTelephone $indicatif
         */
        foreach ($this->em->getRepository(IndicatifTelephone::class)->findAll() as $indicatif) {
            $item = [];
            $item['uuid'] = $indicatif->getUuid()->toString();
            $item['pays'] = $indicatif->getPays();
            $item['indicatif'] = $indicatif->getIndicatif();
            $item['validation_message'] = $indicatif->getMessageValidation();
            $item['code_pays'] = $indicatif->getCodePays();
            $return[] = $item;
        }

        return $return;
    }

    /**
     * Renvoie la liste des téléphones liés à une PersonneLien.
     */
    public function getPersonneTelephones(PersonneLien $lien): array
    {
        $telephones = [];
        /** @var LienTelephone $lienTelephone */
        foreach ($lien->getTelephones() as $lienTelephone) {
            $data = [];
            $data['uuid'] = $lienTelephone->getUuid()->toString();
            $data['principal'] = $lienTelephone->getPrincipal();
            $data['type'] = $this->typeMapping[$lienTelephone->getType()];
            $data['type_id'] = $lienTelephone->getType();
            $data['valeur'] = $lienTelephone->getTelephone()->getValeur();
            if ($lienTelephone->getTelephone()->getIndicatif()) {
                $data['indicatif']['uuid'] = $lienTelephone->getTelephone()->getIndicatif()->getUuid()->toString();
                $data['indicatif']['valeur'] = $lienTelephone->getTelephone()->getIndicatif()->getIndicatif();
            }
            if ('Fax' === $data['type']) {
                $telephones['faxes'][] = $data;
            } else {
                $telephones['telephones'][] = $data;
            }
        }

        return $telephones;
    }

    /**
     * Met à jour la liste des liens téléphones d'une personne (et leurs valeurs).
     *
     * @param PersonneLien $lien
     * @param array        $data
     *
     * @throws \Exception
     */
    public function updatePersonneTelephones($lien, $data)
    {
        if (isset($data['telephones']) && isset($data['faxes'])) {
            $data = array_merge_recursive($data['telephones'], $data['faxes']);
        } elseif (isset($data['telephones'])) {
            $data = $data['telephones'];
        } elseif (isset($data['faxes'])) {
            $data = $data['faxes'];
        }

        $aSupprimer = [];
        $lienTelephones = $this->em->getRepository(LienTelephone::class)->findBy(['lien' => $lien]);
        /** @var LienTelephone $lienTelephone */
        foreach ($lienTelephones as $lienTelephone) {
            $existe = false;
            foreach ($data as $id => $item) {
                if ($item['uuid'] === $lienTelephone->getUuid()->toString()) {
                    $existe = true;
                    $lienTelephone->setPrincipal((bool) $item['principal']);
                    $lienTelephone->setType($this->getTypeId($item['type']));
                    if ($lienTelephone->getTelephone()->getvaleur() !== $item['valeur']) {
                        $telephone = null;
                        if (isset($item['indicatif'])) {
                            $indicatif = $this->em->getRepository(IndicatifTelephone::class)
                                ->findBy(['uuid' => $item['indicatif']['uuid']]);
                            $telephone = $this->em->getRepository(Telephone::class)
                                ->findBy(['valeur' => $item['valeur'], 'indicatif' => $indicatif]);
                        } else {
                            $telephone = $this->em->getRepository(Telephone::class)
                                ->findBy(['valeur' => $item['valeur']]);
                        }
                        if (isset($telephone[0])) {
                            $lienTelephone->setTelephone($telephone[0]);
                        } else {
                            if (isset($item['indicatif'])) {
                                $lienTelephone->setTelephone($this->createTelephone(
                                    $item['valeur'],
                                    $item['indicatif']['uuid']
                                ));
                            } else {
                                $lienTelephone->setTelephone($this->createTelephone($item['valeur'], null));
                            }
                        }
                    }
                    $this->em->persist($lienTelephone);
                    unset($data[$id]);
                }
            }
            if (!$existe) {
                $aSupprimer[] = $lienTelephone;
            }
        }
        foreach ($data as $item) {
            if (isset($item['indicatif'])) {
                $this->add(
                    $lien->getUuid()->toString(),
                    $item['valeur'],
                    $item['indicatif']['uuid'],
                    $this->getTypeId($item['type']),
                    $item['principal']
                );
            } else {
                $this->add(
                    $lien->getUuid()->toString(),
                    $item['valeur'],
                    null,
                    $this->getTypeId($item['type']),
                    $item['principal']
                );
            }
        }
        foreach ($aSupprimer as $item) {
            $this->em->remove($item);
        }
    }

    /**
     * Retourne l'id d'un type de téléphone depuis son libellé.
     *
     * @param $type
     */
    private function getTypeId($type): string
    {
        foreach ($this->typeMapping as $id => $texte) {
            if ($texte === $type) {
                return $id;
            }
        }
        // Par défaut, on renvoie le type "Fixe"
        return '1';
    }
}
