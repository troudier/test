<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Doctrine\UuidGenerator;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;

/**
 * @ApiResource(
 *     normalizationContext={"groups"={"read"}},
 *     denormalizationContext={"groups"={"write"}}
 * )
 * @ORM\Entity(repositoryClass=UserRepository::class)
 */
class User implements UserInterface
{
    /**
     * @var UuidInterface
     * @ApiProperty(identifier=true)
     * @ORM\Column(name="user_uuid", type="uuid", unique=true)
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
     * @ORM\Column(name="user_login", type="string", length=180, unique=true)
     */
    private $login;
    /**
     * @SerializedName("password")
     * @Groups({"write"})
     *
     * @var string The plain password
     * @ORM\Column(name="user_plain_password", type="string", nullable=true)
     */
    private $plainPassword;

    /**
     * @var string The hashed password
     * @ORM\Column(name="user_password", type="string")
     */
    private $password;

    /**
     * @Groups({"read", "write"})
     * @ORM\Column(name="user_roles", type="json")
     */
    private $roles = [];

    /**
     * @Groups({"read", "write"})
     * @ORM\ManyToMany( targetEntity=Organisation::class, cascade={"persist"})
     */
    public $organisations;

    /**
     * @Groups({"read", "write"})
     * @ORM\ManyToOne(targetEntity="App\Entity\Profil", cascade={"persist"})
     */
    private $profil;

    /**
     * @Groups({"read", "write"})
     * @ORM\OneToMany(targetEntity="App\Entity\UserDroit", mappedBy="user")
     */
    public $droits;

    /**
     * @Groups({"read", "write"})
     * @ORM\OneToMany(targetEntity="App\Entity\Evenement", mappedBy="userAssigne")
     */
    public $evenements;

    /**
     * @Groups({"read", "write"})
     * @ORM\OneToMany(targetEntity="App\Entity\SegmentIntervenant", mappedBy="user")
     */
    public $segmentIntervenants;

    /**
     * @Groups({"read", "write"})
     * @ORM\OneToMany(targetEntity="App\Entity\LienIntervenant", mappedBy="user")
     */
    public $lienIntervenants;

    /**
     * @Groups({"read", "write"})
     * @ORM\OneToMany(targetEntity="App\Entity\LeadIntervenant", mappedBy="user")
     */
    public $leadIntervenants;

    /**
     * @ORM\Column(type="string", nullable=true, unique=true)
     */
    private $apiToken;

    /**
     * @Groups({"read", "write"})
     * @ORM\Column(name="user_crea_date", type="datetime")
     */
    private $dateCreation;

    /**
     * @Groups({"read", "write"})
     * @ORM\Column(name="user_modif_date", type="datetime")
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

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUsername(): string
    {
        return (string) $this->login;
    }

    public function setUsername(string $username): self
    {
        $this->login = $username;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getPassword(): string
    {
        return (string) $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Returning a salt is only needed, if you are not using a modern
     * hashing algorithm (e.g. bcrypt or sodium) in your security.yaml.
     *
     * @see UserInterface
     */
    public function getSalt(): ?string
    {
        return null;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        $this->plainPassword = null;
    }

    public function getApiToken(): ?string
    {
        return $this->apiToken;
    }

    public function setApiToken(?string $apiToken): self
    {
        $this->apiToken = $apiToken;

        return $this;
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
    public function getOrganisations()
    {
        return $this->organisations;
    }

    /**
     * @param mixed $organisations
     */
    public function setOrganisations($organisations): void
    {
        $this->organisations = $organisations;
    }

    /**
     * @return mixed
     */
    public function getProfil()
    {
        return $this->profil;
    }

    /**
     * @param mixed $profil
     */
    public function setProfil($profil): void
    {
        $this->profil = $profil;
    }

    /**
     * @return mixed
     */
    public function getDroits()
    {
        return $this->droits;
    }

    /**
     * @param mixed $droits
     */
    public function setDroits($droits): void
    {
        $this->droits = $droits;
    }

    /**
     * @return mixed
     */
    public function getLogin()
    {
        return $this->login;
    }

    /**
     * @param mixed $login
     */
    public function setLogin($login): void
    {
        $this->login = $login;
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
     * @return string
     */
    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(string $plainPassword): void
    {
        $this->plainPassword = $plainPassword;
    }

    /**
     * @return mixed
     */
    public function getEvenements()
    {
        return $this->evenements;
    }

    /**
     * @param mixed $evenements
     */
    public function setEvenements($evenements): void
    {
        $this->evenements = $evenements;
    }

    /**
     * @return mixed
     */
    public function getSegmentIntervenants()
    {
        return $this->segmentIntervenants;
    }

    /**
     * @param mixed $segmentIntervenants
     */
    public function setSegmentIntervenants($segmentIntervenants): void
    {
        $this->segmentIntervenants = $segmentIntervenants;
    }

    /**
     * @return mixed
     */
    public function getLienIntervenants()
    {
        return $this->lienIntervenants;
    }

    /**
     * @param mixed $lienIntervenants
     */
    public function setLienIntervenants($lienIntervenants): void
    {
        $this->lienIntervenants = $lienIntervenants;
    }

    /**
     * @return mixed
     */
    public function getLeadIntervenants()
    {
        return $this->leadIntervenants;
    }

    /**
     * @param mixed $leadIntervenants
     */
    public function setLeadIntervenants($leadIntervenants): void
    {
        $this->leadIntervenants = $leadIntervenants;
    }
}
