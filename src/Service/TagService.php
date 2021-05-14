<?php

namespace App\Service;

use App\Entity\PersonneLien;
use App\Entity\Tag;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;

class TagService
{

    /**
     *
     * @var string[]
     */

    private $targetMapping = [
        'personnes' => '1',
        'leads' => '2',
        'all' => '3'
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

    /**
     * Récupère les tags disponibles, filtrés  ou non (par target et / ou si systeme ou non)
     *
     * @param $query
     * @return \Doctrine\DBAL\Statement
     * @throws \Doctrine\DBAL\Exception
     */
    public function prepareListeTags($query)
    {
        $sql = 'SELECT  
                    tag_libelle as libelle,
                    tag_uuid as uuid,
                    tag_systeme as systeme
                 FROM tag ';
        $sqlPart = '';
        if ($query->has('target')) {
            $sqlPart = empty($sqlPart) ? "WHERE (" : $sqlPart . "AND (";
            $sqlPart .= 'tag.tag_target = ' . $this->targetMapping[addslashes($query->get('target'))] . ') ';
        }
        if ($query->has('systeme')) {
            $sqlPart = empty($sqlPart) ? "WHERE (" : $sqlPart . "AND (";
            $sqlPart .= 'tag.tag_systeme = ' . addslashes($query->get('systeme')) . ') ';
        }
        return $this->connexion->prepare($sql . $sqlPart);
    }

    /**
     * Vérifie si un Tag est lié à une PersonneLien
     *
     * @param PersonneLien $lien
     * @param Tag $tag
     * @return bool
     */
    public function hasTag(PersonneLien  $lien,Tag $tag){
        foreach($lien->getTags() as $item){
            if($tag === $item){
                return true;
            }
        }
        return false;
    }

}