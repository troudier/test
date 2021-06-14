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
class PersonneLien
{
    /**
     * @var \Ramsey\Uuid\UuidInterface
     * @ApiProperty(identifier=true)
     * @ORM\Column(name="pl_uuid", type="uuid", unique=true)
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
     * @ORM\Column(name="pl_libelle", type="string", length=255)
     */
    private $libelle;

    /**
     * @Groups({"read", "write"})
     * @ORM\Column(name="pl_type", type="string", length=255)
     */
    private $type;

    /**
     * @Groups({"read", "write"})
     * @ORM\Column(name="pl_visibilite", type="integer", length=1)
     */
    private $visibilite;

    /**
     * @Groups({"read", "write"})
     * @ORM\ManyToOne(targetEntity="App\Entity\PersonnePhysique", inversedBy="liens", cascade={"persist"})
     */
    private $personnePhysique;

    /**
     * @Groups({"read", "write"})
     * @ORM\ManyToOne(targetEntity="App\Entity\PersonneMorale", inversedBy="liens", cascade={"persist"})
     */
    private $personneMorale;

    /**
     * @Groups({"read", "write"})
     * @ORM\ManyToOne(targetEntity="App\Entity\PersonneLienFonction", inversedBy="liens", cascade={"persist"})
     */
    private $fonction;

    /**
     * @Groups({"read", "write"})
     * @ORM\Column(name="fonction_personnalisee", type="string", length=30, nullable=true)
     */
    private $fonctionPersonnalisee;

    /**
     * @Groups({"read", "write"})
     * @ORM\Column(name="pl_referent", type="boolean")
     */
    private $referent;

    /**
     * @Groups({"read", "write"})
     * @ORM\Column(name="pl_active", type="boolean")
     */
    private $active;

    /**
     * @Groups({"read", "write"})
     * @ORM\OneToMany(targetEntity="App\Entity\CampagneDestinataire", mappedBy="lien")
     */
    private $destinataires;

    /**
     * @Groups({"read", "write"})
     * @ORM\OneToMany(targetEntity="App\Entity\CampagneDestinataireEvent", mappedBy="lien")
     */
    private $destinataireEvents;

    /**
     * @Groups({"read", "write"})
     * @ORM\OneToMany(targetEntity="App\Entity\Lead", mappedBy="lien")
     */
    private $leads;

    /**
     * @Groups({"read", "write"})
     * @ORM\ManyToMany( targetEntity=Tag::class)
     */
    public $tags;

    /**
     * @Groups({"read", "write"})
     * @ORM\OneToMany(targetEntity="App\Entity\Evenement", mappedBy="lien")
     */
    private $evenements;

    /**
     * @Groups({"read", "write"})
     * @ORM\ManyToOne(targetEntity="App\Entity\PersonneStatut")
     */
    private $statut;
    /**
     * @Groups({"read", "write"})
     * @ORM\OneToMany(targetEntity="App\Entity\LienIntervenant", mappedBy="lien")
     */
    private $intervenants;

    /**
     * @Groups({"read", "write"})
     * @ORM\OneToMany(targetEntity="App\Entity\LienAdresse", mappedBy="lien")
     */
    private $adresses;

    /**
     * @Groups({"read", "write"})
     * @ORM\OneToMany(targetEntity="App\Entity\LienChamp", mappedBy="lien")
     */
    private $champs;

    /**
     * @Groups({"read", "write"})
     * @ORM\OneToMany(targetEntity="App\Entity\LienMail", mappedBy="lien")
     */
    private $mails;

    /**
     * @Groups({"read", "write"})
     * @ORM\OneToMany(targetEntity="App\Entity\LienSite", mappedBy="lien")
     */
    private $sites;

    /**
     * @Groups({"read", "write"})
     * @ORM\OneToMany(targetEntity="App\Entity\LienTelephone", mappedBy="lien")
     */
    private $telephones;

    /**
     * @Groups({"read", "write"})
     * @ORM\Column(name="pl_crea_date", type="datetime")
     */
    private $dateCreation;

    /**
     * @Groups({"read", "write"})
     * @ORM\Column(name="pl_modif_date", type="datetime")
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

    /**
     * @Groups({"read", "write"})*
     * @ORM\Column(name="pl_qualite", type="integer")
     */
    private $qualite;

    /**
     * @Groups({"read", "write"})
     * @ORM\OneToMany(targetEntity="App\Entity\Memo", mappedBy="lien")
     */
    private $memos;

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
    public function getFonction()
    {
        return $this->fonction;
    }

    /**
     * @param mixed $fonction
     */
    public function setFonction($fonction): void
    {
        $this->fonction = $fonction;
    }

    /**
     * @return mixed
     */
    public function getFonctionPersonnalisee()
    {
        return $this->fonctionPersonnalisee;
    }

    /**
     * @param mixed $fonctionPersonnalisee
     */
    public function setFonctionPersonnalisee($fonctionPersonnalisee): void
    {
        $this->fonctionPersonnalisee = $fonctionPersonnalisee;
    }

    /**
     * @return mixed
     */
    public function getReferent()
    {
        return $this->referent;
    }

    /**
     * @param mixed $referent
     */
    public function setReferent($referent): void
    {
        $this->referent = $referent;
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
     * @param mixed $destinataireEvents
     */
    public function setDestinataireEvents($destinataireEvents): void
    {
        $this->destinataireEvents = $destinataireEvents;
    }

    /**
     * @return mixed
     */
    public function getLeads()
    {
        return $this->leads;
    }

    /**
     * @param mixed $leads
     */
    public function setLeads($leads): void
    {
        $this->leads = $leads;
    }

    /**
     * @return mixed
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * @param mixed $tags
     */
    public function setTags($tags): void
    {
        foreach ($this->tags as $id => $tag) {
            $found = false;
            foreach ($tags as $newTag) {
                if ($newTag === $tag) {
                    $found = true;
                }
            }
            //on supprime le tag dans l'existant s'il n'est pas dans les données envoyées
            if (!$found) {
                $this->tags->remove($id);
            }
        }

        foreach ($tags as $key => $newTag) {
            $found = false;
            foreach ($this->tags as $id => $tag) {
                if ($newTag === $tag) {
                    $found = $key;
                }
            }
            //on supprime le tag dans les données à ajouter s'il est déjà présent.
            if ($found) {
                unset($tags[$found]);
            }
        }
        //add products that exist in new but not in old
        foreach ($tags as $id => $tag) {
            $this->tags[$id] = $tag;
        }
    }

    public function addTag(Tag $tag)
    {
        $this->tags[] = $tag;
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
    public function getPersonnePhysique()
    {
        return $this->personnePhysique;
    }

    /**
     * @param mixed $personnePhysique
     */
    public function setPersonnePhysique($personnePhysique): void
    {
        $this->personnePhysique = $personnePhysique;
    }

    /**
     * @return mixed
     */
    public function getPersonneMorale()
    {
        return $this->personneMorale;
    }

    /**
     * @param mixed $personneMorale
     */
    public function setPersonneMorale($personneMorale): void
    {
        $this->personneMorale = $personneMorale;
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
    public function getAdresses()
    {
        return $this->adresses;
    }

    /**
     * @param mixed $adresses
     */
    public function setAdresses($adresses): void
    {
        $this->adresses = $adresses;
    }

    /**
     * @return mixed
     */
    public function getChamps()
    {
        return $this->champs;
    }

    /**
     * @param mixed $champs
     */
    public function setChamps($champs): void
    {
        $this->champs = $champs;
    }

    /**
     * @return mixed
     */
    public function getMails()
    {
        return $this->mails;
    }

    /**
     * @param mixed $mails
     */
    public function setMails($mails): void
    {
        $this->mails = $mails;
    }

    /**
     * @return mixed
     */
    public function getSites()
    {
        return $this->sites;
    }

    /**
     * @param mixed $sites
     */
    public function setSites($sites): void
    {
        $this->sites = $sites;
    }

    /**
     * @return mixed
     */
    public function getTelephones()
    {
        return $this->telephones;
    }

    /**
     * @param mixed $telephones
     */
    public function setTelephones($telephones): void
    {
        $this->telephones = $telephones;
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
    public function getQualite()
    {
        return $this->qualite;
    }

    /**
     * @param mixed $qualite
     */
    public function setQualite($qualite): void
    {
        $this->qualite = $qualite;
    }

    /**
     * @return mixed
     */
    public function getMemos()
    {
        return $this->memos;
    }

    /**
     * @param mixed $memos
     */
    public function setMemos($memos): void
    {
        $this->memos = $memos;
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
}
