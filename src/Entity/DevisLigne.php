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
class DevisLigne
{
    /**
     * @var \Ramsey\Uuid\UuidInterface
     * @ApiProperty(identifier=true)
     * @ORM\Column(name="dl_uuid", type="uuid", unique=true)
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
     * @ORM\Column(name="dl_produit_id", type="integer", length=11)
     */
    private $produitId;

    /**
     * @Groups({"read", "write"})
     * @ORM\Column(name="dl_libelle", type="string", length=255)
     */
    private $libelle;

    /**
     * @Groups({"read", "write"})
     * @ORM\Column(name="dl_prix_unitaire_ht", type="integer")
     */
    private $prixUnitaireHT;

    /**
     * @Groups({"read", "write"})
     * @ORM\Column(name="dl_quantite", type="integer", length=11)
     */
    private $quantite;

    /**
     * @Groups({"read", "write"})
     * @ORM\Column(name="dl_taux_tva", type="integer")
     */
    private $tauxTVA;

    /**
     * @Groups({"read", "write"})
     * @ORM\ManyToOne(targetEntity="App\Entity\Devis", inversedBy="lignes")
     */
    private $devis;

    /**
     * @Groups({"read", "write"})
     * @ORM\Column(name="dl_crea_date", type="datetime")
     */
    private $dateCreation;

    /**
     * @Groups({"read", "write"})
     * @ORM\Column(name="dl_modif_date", type="datetime")
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
    public function getProduitId()
    {
        return $this->produitId;
    }

    /**
     * @param mixed $produitId
     */
    public function setProduitId($produitId): void
    {
        $this->produitId = $produitId;
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
    public function getPrixUnitaireHT()
    {
        return $this->prixUnitaireHT;
    }

    /**
     * @param mixed $prixUnitaireHT
     */
    public function setPrixUnitaireHT($prixUnitaireHT): void
    {
        $this->prixUnitaireHT = $prixUnitaireHT;
    }

    /**
     * @return mixed
     */
    public function getQuantite()
    {
        return $this->quantite;
    }

    /**
     * @param mixed $quantite
     */
    public function setQuantite($quantite): void
    {
        $this->quantite = $quantite;
    }

    /**
     * @return mixed
     */
    public function getTauxTVA()
    {
        return $this->tauxTVA;
    }

    /**
     * @param mixed $tauxTVA
     */
    public function setTauxTVA($tauxTVA): void
    {
        $this->tauxTVA = $tauxTVA;
    }

    /**
     * @return mixed
     */
    public function getDevis()
    {
        return $this->devis;
    }

    /**
     * @param mixed $devis
     */
    public function setDevis($devis): void
    {
        $this->devis = $devis;
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
