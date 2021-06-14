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
class Campagne
{
    /**
     * @var \Ramsey\Uuid\UuidInterface
     * @ApiProperty(identifier=true)
     * @ORM\Column(name="cmp_uuid", type="uuid", unique=true)
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
     * @ORM\Column(name="cmp_statut", type="integer")
     */
    private $statut;

    /**
     * @Groups({"read", "write"})
     * @ORM\Column(name="cmp_visibilite", type="integer")
     */
    private $visibilite;

    /**
     * @Groups({"read", "write"})
     * @ORM\Column(name="cmp_subject", type="text")
     */
    private $subject;

    /**
     * @Groups({"read", "write"})
     * @ORM\Column(name="cmp_body", type="text")
     */
    private $body;

    /**
     * @Groups({"read", "write"})
     * @ORM\Column(name="cmp_nb_dest", type="integer", length=11)
     */
    private $nbDest;

    /**
     * @Groups({"read", "write"})
     * @ORM\Column(name="cmp_nb_envoye", type="integer", length=11)
     */
    private $nbEnvoye;

    /**
     * @Groups({"read", "write"})
     * @ORM\Column(name="cmp_nb_ouvert", type="integer", length=11)
     */
    private $nbOuvert;

    /**
     * @Groups({"read", "write"})
     * @ORM\Column(name="cmp_nb_click", type="integer", length=11)
     */
    private $nbClick;

    /**
     * @Groups({"read", "write"})
     * @ORM\ManyToMany( targetEntity=Segment::class)
     */
    private $segments;

    /**
     * @Groups({"read", "write"})
     * @ORM\OneToMany(targetEntity="App\Entity\CampagneDestinataire", mappedBy="campagne")
     */
    private $destinataires;

    /**
     * @Groups({"read", "write"})
     * @ORM\OneToMany(targetEntity="App\Entity\CampagneDestinataireEvent", mappedBy="campagne")
     */
    private $destinataireEvents;

    /**
     * @Groups({"read", "write"})
     * @ORM\Column(name="cmp_crea_date", type="datetime")
     */
    private $dateCreation;

    /**
     * @Groups({"read", "write"})
     * @ORM\Column(name="cmp_modif_date", type="datetime")
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
    public function getStatut()
    {
        return $this->statut;
    }

    /**
     * @param mixed $statut
     */
    public function setStatut($statut): void
    {
        $this->statut = $statut;
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
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * @param mixed $subject
     */
    public function setSubject($subject): void
    {
        $this->subject = $subject;
    }

    /**
     * @return mixed
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @param mixed $body
     */
    public function setBody($body): void
    {
        $this->body = $body;
    }

    /**
     * @return mixed
     */
    public function getNbDest()
    {
        return $this->nbDest;
    }

    /**
     * @param mixed $nbDest
     */
    public function setNbDest($nbDest): void
    {
        $this->nbDest = $nbDest;
    }

    /**
     * @return mixed
     */
    public function getNbEnvoye()
    {
        return $this->nbEnvoye;
    }

    /**
     * @param mixed $nbEnvoye
     */
    public function setNbEnvoye($nbEnvoye): void
    {
        $this->nbEnvoye = $nbEnvoye;
    }

    /**
     * @return mixed
     */
    public function getNbOuvert()
    {
        return $this->nbOuvert;
    }

    /**
     * @param mixed $nbOuvert
     */
    public function setNbOuvert($nbOuvert): void
    {
        $this->nbOuvert = $nbOuvert;
    }

    /**
     * @return mixed
     */
    public function getNbClick()
    {
        return $this->nbClick;
    }

    /**
     * @param mixed $nbClick
     */
    public function setNbClick($nbClick): void
    {
        $this->nbClick = $nbClick;
    }

    /**
     * @return mixed
     */
    public function getSegments()
    {
        return $this->segments;
    }

    /**
     * @param mixed $segments
     */
    public function setSegments($segments): void
    {
        $this->segments = $segments;
    }

    /**
     * @return mixed
     */
    public function getDestinataires()
    {
        return $this->destinataires;
    }

    /**
     * @param mixed $destinataires
     */
    public function setDestinataires($destinataires): void
    {
        $this->destinataires = $destinataires;
    }

    /**
     * @return mixed
     */
    public function getDestinataireEvents()
    {
        return $this->destinataireEvents;
    }

    /**
     * @param mixed $destinatairesEvent
     */
    public function setDestinataireEvents($destinataireEvents): void
    {
        $this->destinataireEvents = $destinataireEvents;
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
