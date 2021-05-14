<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiProperty;
use Ramsey\Uuid\Doctrine\UuidGenerator;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ApiResource(
 *     normalizationContext={"groups"={"read"}},
 *     denormalizationContext={"groups"={"write"}}
 * )
 * @ORM\Entity
 */
class ChampRequetable
{

    /**
     * @var \Ramsey\Uuid\UuidInterface
     * @ApiProperty(identifier=true)
     * @ORM\Column(name="chr_uuid", type="uuid", unique=true)
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class=UuidGenerator::class)
     */
    public $uuid;

    /**
     * @ORM\Id
     * @ApiProperty(identifier=false)
     * @ORM\GeneratedValue
     * @ORM\Column(name="id", type="integer")
     */
    private $id;

    /**
     * @Groups({"read", "write"})
     * @ORM\Column(name="chr_libelle", type="string", length=255)
     */
    private $libelle;

    /**
     * @Groups({"read", "write"})
     * @ORM\Column(name="chr_table_bd", type="string", length=255)
     */
    private $tableBD;

    /**
     * @Groups({"read", "write"})
     * @ORM\Column(name="chr_champ_bd", type="string", length=255)
     */
    private $champBD;

    /**
     * @Groups({"read", "write"})
     * @ORM\Column(name="chr_join", type="string", length=255)
     */
    private $join;

    /**
     * @Groups({"read", "write"})
     * @ORM\Column(name="chr_type", type="integer")
     */
    private $type;

    /**
     * @Groups({"read", "write"})
     * @ORM\Column(name="chr_type_input", type="integer")
     */
    private $typeInput;

    /**
     * @Groups({"read", "write"})
     * @ORM\Column(name="chr_crea_date", type="datetime")
     */
    private $dateCreation;

    /**
     * @Groups({"read", "write"})
     * @ORM\Column(name="chr_modif_date", type="datetime")
     */
    private $dateModification;

    /**
     * @Groups({"read", "write"})
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     */
    private $userCreation;

    /**
     * @Groups({"read", "write"})
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     */
    private $userModification;

    public function __construct(UuidInterface $uuid = null)
    {
        $this->uuid = $uuid ?: Uuid::uuid4();
    }

    /**
     * @return UuidInterface
     */
    public function getUuid(): UuidInterface
    {
        return $this->uuid;
    }

    /**
     * @param UuidInterface $uuid
     */
    public function setUuid(UuidInterface $uuid): void
    {
        $this->uuid = $uuid;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id): void
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getLibelle()
    {
        return $this->libelle;
    }

    /**
     * @param mixed $libelle
     */
    public function setLibelle($libelle): void
    {
        $this->libelle = $libelle;
    }

    /**
     * @return mixed
     */
    public function getChampBD()
    {
        return $this->champBD;
    }

    /**
     * @param mixed $champBD
     */
    public function setChampBD($champBD): void
    {
        $this->champBD = $champBD;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     */
    public function setType($type): void
    {
        $this->type = $type;
    }

    /**
     * @return mixed
     */
    public function getDateCreation()
    {
        return $this->dateCreation;
    }

    /**
     * @param mixed $dateCreation
     */
    public function setDateCreation($dateCreation): void
    {
        $this->dateCreation = $dateCreation;
    }

    /**
     * @return mixed
     */
    public function getDateModification()
    {
        return $this->dateModification;
    }

    /**
     * @param mixed $dateModification
     */
    public function setDateModification($dateModification): void
    {
        $this->dateModification = $dateModification;
    }

    /**
     * @return mixed
     */
    public function getUserCreation()
    {
        return $this->userCreation;
    }

    /**
     * @param mixed $userCreation
     */
    public function setUserCreation($userCreation): void
    {
        $this->userCreation = $userCreation;
    }

    /**
     * @return mixed
     */
    public function getUserModification()
    {
        return $this->userModification;
    }

    /**
     * @param mixed $userModification
     */
    public function setUserModification($userModification): void
    {
        $this->userModification = $userModification;
    }

    /**
     * @return mixed
     */
    public function getTableBD()
    {
        return $this->tableBD;
    }

    /**
     * @param mixed $tableBD
     */
    public function setTableBD($tableBD): void
    {
        $this->tableBD = $tableBD;
    }

    /**
     * @return mixed
     */
    public function getTypeInput()
    {
        return $this->typeInput;
    }

    /**
     * @param mixed $typeInput
     */
    public function setTypeInput($typeInput): void
    {
        $this->typeInput = $typeInput;
    }

    /**
     * @return mixed
     */
    public function getJoin()
    {
        return $this->join;
    }

    /**
     * @param mixed $join
     */
    public function setJoin($join): void
    {
        $this->join = $join;
    }

}