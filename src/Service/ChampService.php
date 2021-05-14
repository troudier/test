<?php

namespace App\Service;

use App\Entity\Champ;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;

class ChampService
{

    private $targetMapping = [
        1 => [
            'morale'
        ],
        2 => [
            'physique'
        ],
        3 => [
            'morale',
            'physique'
        ],
        4 => [
            'lien'
        ],
        5 => [
            'morale',
            'lien'
        ],
        6 => [
            'physique',
            'lien'
        ],
        7 => [
            'physique',
            'lien',
            'morale'
        ]
    ];

    private $typeMapping = [
        10 => 'text',
        11 => 'mail',
        12 => 'tel',
        20 => 'select',
        21 => 'radio',
        22 => 'checkbox',
        30 => 'textarea',
        40 => 'date'
    ];

    /**
     * @var Connection
     */
    private $connexion;

    /**
     *
     * @param EntityManagerInterface $em
     */

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
        $this->connexion = $this->em->getConnection();
    }

    public function getChampsDisponibles($target)
    {
        $targetId = null;
        $targets = explode(',', $target);
        sort($targets);
        foreach ($this->targetMapping as $id => $mapping) {
            sort($mapping);
            if ($targets == $mapping) {
                $targetId = $id;
            }
        }
        $data = null;
        if ($targetId) {
            $champs = $this->em->getRepository(Champ::class)
                ->findBy(['target' => $targetId]);
            /** @var Champ $champ */
            foreach ($champs as $champ) {
                $item = [];
                $item['uuid'] = $champ->getUuid()->toString();
                $item['libelle'] = $champ->getLibelle();
                $item['type'] = $this->typeMapping[$champ->getType()];
                $item['valeurs'] = json_decode($champ->getValeurs());
                $item['requis'] = $champ->getRequired();
                $data[] = $item;
            }
        }
        return $data;
    }

}