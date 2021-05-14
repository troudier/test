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
class Adresse
{

    /**
     * @var \Ramsey\Uuid\UuidInterface
     * @ApiProperty(identifier=true)
     * @ORM\Column(name="adr_uuid", type="uuid", unique=true)
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
     * @ORM\Column(name="adr_ligne1", type="string", length=255)
     */
    private $ligne1;

    /**
     * @Groups({"read", "write"})
     * @ORM\Column(name="adr_ligne2", type="string", length=45)
     */
    private $ligne2;

    /**
     * @Groups({"read", "write"})
     * @ORM\Column(name="adr_ligne3", type="string", length=45)
     */
    private $ligne3;

    /**
     * @Groups({"read", "write"})
     * @ORM\Column(name="adr_cp", type="string", length=45)
     */
    private $cp;

    /**
     * @Groups({"read", "write"})
     * @ORM\Column(name="adr_cedex_code", type="string", length=45)
     */
    private $cedexCode;

    /**
     * @Groups({"read", "write"})
     * @ORM\Column(name="adr_cedex_lib", type="string", length=45)
     */
    private $cedexLibelle;

    /**
     * @Groups({"read", "write"})
     * @ORM\Column(name="adr_ville", type="string", length=45)
     */
    private $ville;

    /**
     * @Groups({"read", "write"})
     * @ORM\Column(name="adr_pays", type="string", length=45)
     */
    private $pays;

    /**
     * @Groups({"read", "write"})
     * @ORM\OneToMany(targetEntity="App\Entity\LienAdresse", mappedBy="adresse")
     */
    private $liens;

    /**
     * @Groups({"read", "write"})
     * @ORM\Column(name="adr_crea_date", type="datetime")
     */
    private $dateCreation;

    /**
     * @Groups({"read", "write"})
     * @ORM\Column(name="adr_modif_date", type="datetime")
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
    public function getLigne1()
    {
        return $this->ligne1;
    }

    /**
     * @param mixed $ligne1
     */
    public function setLigne1($ligne1): void
    {
        $this->ligne1 = $ligne1;
    }

    /**
     * @return mixed
     */
    public function getLigne2()
    {
        return $this->ligne2;
    }

    /**
     * @param mixed $ligne2
     */
    public function setLigne2($ligne2): void
    {
        $this->ligne2 = $ligne2;
    }

    /**
     * @return mixed
     */
    public function getLigne3()
    {
        return $this->ligne3;
    }

    /**
     * @param mixed $ligne3
     */
    public function setLigne3($ligne3): void
    {
        $this->ligne3 = $ligne3;
    }

    /**
     * @return mixed
     */
    public function getCp()
    {
        return $this->cp;
    }

    /**
     * @param mixed $cp
     */
    public function setCp($cp): void
    {
        $this->cp = $cp;
    }

    /**
     * @return mixed
     */
    public function getVille()
    {
        return $this->ville;
    }

    /**
     * @param mixed $ville
     */
    public function setVille($ville): void
    {
        $this->ville = $ville;
    }

    /**
     * @return mixed
     */
    public function getPays()
    {
        return $this->pays;
    }

    /**
     * @param mixed $pays
     */
    public function setPays($pays): void
    {
        $this->pays = $pays;
    }

    /**
     * @return mixed
     */
    public function getLiens()
    {
        return $this->liens;
    }

    /**
     * @param mixed $liens
     */
    public function setLiens($liens): void
    {
        $this->liens = $liens;
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
    public function getCedexCode()
    {
        return $this->cedexCode;
    }

    /**
     * @param mixed $cedexCode
     */
    public function setCedexCode($cedexCode): void
    {
        $this->cedexCode = $cedexCode;
    }

    /**
     * @return mixed
     */
    public function getCedexLibelle()
    {
        return $this->cedexLibelle;
    }

    /**
     * @param mixed $cedexLibelle
     */
    public function setCedexLibelle($cedexLibelle): void
    {
        $this->cedexLibelle = $cedexLibelle;
    }

}