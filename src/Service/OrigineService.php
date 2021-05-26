<?php

namespace App\Service;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;

class OrigineService
{
    /**
     * @var Connection
     */
    private $connexion;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
        $this->connexion = $this->em->getConnection();
    }

    /**
     * Récupère les origines disponibles, filtrés  ou non (si systeme ou non).
     *
     * @param $query
     *
     * @return \Doctrine\DBAL\Statement
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function prepareListeOrigines($query)
    {
        $sql = 'SELECT  
                    ori_libelle as libelle,
                    ori_uuid as uuid,
                    ori_systeme as systeme
                 FROM origine ';
        $sqlPart = '';

        if ($query->has('systeme')) {
            $sqlPart = empty($sqlPart) ? 'WHERE (' : $sqlPart.'AND (';
            $sqlPart .= 'ori.ori_systeme = '.addslashes($query->get('systeme')).') ';
        }

        return $this->connexion->prepare($sql.$sqlPart);
    }
}
