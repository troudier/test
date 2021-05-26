<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Doctrine\UuidGenerator;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ApiResource(
 *     normalizationContext={"groups"={"read"}},
 *     denormalizationContext={"groups"={"write"}}
 * )
 * @ORM\Entity
 */
class SegmentFiltre
{
    /**
     * @var \Ramsey\Uuid\UuidInterface
     * @ApiProperty(identifier=true)
     * @ORM\Column(name="sf_uuid", type="uuid", unique=true)
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
     * @ORM\Column(name="sf_ordre", type="integer")
     */
    private $ordre;

    /**
     * @Groups({"read", "write"})
     * @ORM\ManyToOne(targetEntity="App\Entity\ChampRequetable")
     */
    private $champ;

    /**
     * @Groups({"read", "write"})
     * @ORM\ManyToOne(targetEntity="App\Entity\Operateur")
     */
    private $operateur;

    /**
     * @Groups({"read", "write"})
     * @ORM\OneToMany(targetEntity="App\Entity\SegmentFiltreValeur", mappedBy="segmentFiltre")
     */
    private $valeurs;

    /**
     * @Groups({"read", "write"})
     * @ORM\ManyToOne(targetEntity="App\Entity\Segment", inversedBy="filtres", cascade={"persist"})
     */
    private $segment;

    /**
     * @Groups({"read", "write"})
     * @ORM\Column(name="sf_crea_date", type="datetime")
     */
    private $dateCreation;

    /**
     * @Groups({"read", "write"})
     * @ORM\Column(name="sf_modif_date", type="datetime")
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

    public function getUuid(): UuidInterface
    {
        return $this->uuid;
    }

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
    public function getOrdre()
    {
        return $this->ordre;
    }

    /**
     * @param mixed $ordre
     */
    public function setOrdre($ordre): void
    {
        $this->ordre = $ordre;
    }

    /**
     * @return mixed
     */
    public function getChamp()
    {
        return $this->champ;
    }

    /**
     * @param mixed $champ
     */
    public function setChamp($champ): void
    {
        $this->champ = $champ;
    }

    /**
     * @return mixed
     */
    public function getOperateur()
    {
        return $this->operateur;
    }

    /**
     * @param mixed $operateur
     */
    public function setOperateur($operateur): void
    {
        $this->operateur = $operateur;
    }

    /**
     * @return mixed
     */
    public function getValeurs()
    {
        return $this->valeurs;
    }

    /**
     * @param mixed $valeurs
     */
    public function setValeurs($valeurs): void
    {
        $this->valeurs = $valeurs;
    }

    /**
     * @return mixed
     */
    public function getSegment()
    {
        return $this->segment;
    }

    /**
     * @param mixed $segment
     */
    public function setSegment($segment): void
    {
        $this->segment = $segment;
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
}
