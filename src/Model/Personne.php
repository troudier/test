<?php

namespace App\Model;

use Doctrine\ORM\Mapping\DiscriminatorColumn;
use Doctrine\ORM\Mapping\DiscriminatorMap;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\InheritanceType;

/**
 * @Entity
 * @InheritanceType("SINGLE_TABLE")
 * @DiscriminatorColumn(name="discr", type="string")
 * @DiscriminatorMap(
 *     {"personne" = "Personne", "personneMorale" = "PersonneMorale", "personnePhysique" = "PersonnePhysique"}
 *     )
 */
class Personne
{
}
