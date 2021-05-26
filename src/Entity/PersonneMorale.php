<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use App\Model\Personne;
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
class PersonneMorale extends Personne
{
    /**
     * @var \Ramsey\Uuid\UuidInterface
     * @ApiProperty(identifier=true)
     * @ORM\Column(name="pm_uuid", type="uuid", unique=true)
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
     * @ORM\ManyToOne(targetEntity="App\Entity\PersonneMorale", inversedBy="enfants")
     */
    private $parent;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\PersonneMorale", mappedBy="parent")
     */
    private $enfants;

    /**
     * @Groups({"read", "write"})
     * @ORM\Column(name="pm_visibilite", type="integer", length=1)
     */
    private $visibilite;

    /**
     * @Groups({"read", "write"})
     * @ORM\Column(name="pm_raison_sociale", type="string", length=45)
     */
    private $raisonSociale;

    /**
     * @Groups({"read", "write"})
     * @ORM\Column(name="pm_code_naf", type="string", length=45, nullable=true)
     */
    private $codeNaf;

    /**
     * @Groups({"read", "write"})
     * @ORM\Column(name="pm_siret", type="string", length=45, nullable=true)
     */
    private $siret;

    /**
     * @Groups({"read", "write"})
     * @ORM\ManyToOne(targetEntity="App\Entity\ChiffreAffaire", inversedBy="personnes", cascade={"persist"})
     */
    private $chiffreAffaire;

    /**
     * @Groups({"read", "write"})
     * @ORM\Column(name="pm_capital", type="integer", nullable=true)
     */
    private $capital;

    /**
     * @Groups({"read", "write"})
     * @ORM\ManyToOne(targetEntity="App\Entity\Effectif", inversedBy="personnes", cascade={"persist"})
     */
    private $effectif;

    /**
     * @Groups({"read", "write"})
     * @ORM\ManyToOne(targetEntity="App\Entity\FormeJuridique", inversedBy="personnes", cascade={"persist"})
     */
    private $formeJuridique;

    /**
     * @Groups({"read", "write"})
     * @ORM\OneToMany(targetEntity="App\Entity\PersonneLien", mappedBy="personneMorale")
     */
    private $liens;

    /**
     * @Groups({"read", "write"})
     * @ORM\Column(name="pm_crea_date", type="datetime")
     */
    private $dateCreation;

    /**
     * @Groups({"read", "write"})
     * @ORM\Column(name="pm_modif_date", type="datetime")
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
    public function getRaisonSociale()
    {
        return $this->raisonSociale;
    }

    /**
     * @param mixed $raisonSociale
     */
    public function setRaisonSociale($raisonSociale): void
    {
        $this->raisonSociale = $raisonSociale;
    }

    /**
     * @return mixed
     */
    public function getCodeNaf()
    {
        return $this->codeNaf;
    }

    /**
     * @param mixed $codeNaf
     */
    public function setCodeNaf($codeNaf): void
    {
        $this->codeNaf = $codeNaf;
    }

    /**
     * @return mixed
     */
    public function getSiret()
    {
        return $this->siret;
    }

    /**
     * @param mixed $siret
     */
    public function setSiret($siret): void
    {
        $this->siret = $siret;
    }

    /**
     * @return mixed
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @param mixed $parent
     */
    public function setParent($parent): void
    {
        $this->parent = $parent;
    }

    /**
     * @return mixed
     */
    public function getEnfants()
    {
        return $this->enfants;
    }

    /**
     * @param mixed $enfants
     */
    public function setEnfants($enfants): void
    {
        $this->enfants = $enfants;
    }

    /**
     * @return mixed
     */
    public function getChiffreAffaire()
    {
        return $this->chiffreAffaire;
    }

    /**
     * @param mixed $chiffreAffaire
     */
    public function setChiffreAffaire($chiffreAffaire): void
    {
        $this->chiffreAffaire = $chiffreAffaire;
    }

    /**
     * @return mixed
     */
    public function getCapital()
    {
        return $this->capital;
    }

    /**
     * @param mixed $capital
     */
    public function setCapital($capital): void
    {
        $this->capital = $capital;
    }

    /**
     * @return mixed
     */
    public function getEffectif()
    {
        return $this->effectif;
    }

    /**
     * @param mixed $effectif
     */
    public function setEffectif($effectif): void
    {
        $this->effectif = $effectif;
    }

    /**
     * @return mixed
     */
    public function getFormeJuridique()
    {
        return $this->formeJuridique;
    }

    /**
     * @param mixed $formeJuridique
     */
    public function setFormeJuridique($formeJuridique): void
    {
        $this->formeJuridique = $formeJuridique;
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
}
