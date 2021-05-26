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
class Segment
{
    /**
     * @var \Ramsey\Uuid\UuidInterface
     * @ApiProperty(identifier=true)
     * @ORM\Column(name="seg_uuid", type="uuid", unique=true)
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
     * @ORM\Column(name="seg_titre", type="string", length=45)
     */
    private $titre;

    /**
     * @Groups({"read", "write"})
     * @ORM\Column(name="seg_visibilite", type="integer")
     */
    private $visibilite;

    /**
     * @Groups({"read", "write"})
     * @ORM\Column(name="seg_active", type="boolean")
     */
    private $active;

    /**
     * @Groups({"read", "write"})
     * @ORM\Column(name="seg_nb_contacts_publics", type="integer", nullable=true)
     */
    private $nbContactsPublics;

    /**
     * @Groups({"read", "write"})
     * @ORM\Column(name="seg_nb_contacts_prives", type="integer", nullable=true)
     */
    private $nbContactsPrives;

    /**
     * @Groups({"read", "write"})
     * @ORM\Column(name="seg_derniere_date_execution", type="datetime", nullable=true)
     */
    private $derniereDateExecution;

    /**
     * @Groups({"read", "write"})
     * @ORM\OneToMany(targetEntity="App\Entity\SegmentIntervenant", mappedBy="segment")
     */
    private $intervenants;

    /**
     * @Groups({"read", "write"})
     * @ORM\OneToMany(targetEntity="App\Entity\SegmentFiltre", mappedBy="segment")
     */
    private $filtres;

    /**
     * @Groups({"read", "write"})
     * @ORM\Column(name="seg_crea_date", type="datetime")
     */
    private $dateCreation;

    /**
     * @Groups({"read", "write"})
     * @ORM\Column(name="seg_modif_date", type="datetime")
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
    public function getTitre()
    {
        return $this->titre;
    }

    /**
     * @param mixed $titre
     */
    public function setTitre($titre): void
    {
        $this->titre = $titre;
    }

    /**
     * @return mixed
     */
    public function getVisibilite()
    {
        return $this->visibilite;
    }

    /**
     * @param mixed $visibilite
     */
    public function setVisibilite($visibilite): void
    {
        $this->visibilite = $visibilite;
    }

    /**
     * @return mixed
     */
    public function getIntervenants()
    {
        return $this->intervenants;
    }

    /**
     * @param mixed $intervenants
     */
    public function setIntervenants($intervenants): void
    {
        $this->intervenants = $intervenants;
    }

    /**
     * @return mixed
     */
    public function getFiltres()
    {
        return $this->filtres;
    }

    /**
     * @param mixed $filtres
     */
    public function setFiltres($filtres): void
    {
        $this->filtres = $filtres;
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
    public function getDerniereDateExecution()
    {
        return $this->derniereDateExecution;
    }

    /**
     * @param mixed $derniereDateExecution
     */
    public function setDerniereDateExecution($derniereDateExecution): void
    {
        $this->derniereDateExecution = $derniereDateExecution;
    }

    /**
     * @return mixed
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * @param mixed $active
     */
    public function setActive($active): void
    {
        $this->active = $active;
    }

    /**
     * @return mixed
     */
    public function getNbContactsPublics()
    {
        return $this->nbContactsPublics;
    }

    /**
     * @param mixed $nbContactsPublics
     */
    public function setNbContactsPublics($nbContactsPublics): void
    {
        $this->nbContactsPublics = $nbContactsPublics;
    }

    /**
     * @return mixed
     */
    public function getNbContactsPrives()
    {
        return $this->nbContactsPrives;
    }

    /**
     * @param mixed $nbContactsPrives
     */
    public function setNbContactsPrives($nbContactsPrives): void
    {
        $this->nbContactsPrives = $nbContactsPrives;
    }
}
