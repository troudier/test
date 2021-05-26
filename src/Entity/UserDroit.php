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
class UserDroit
{
    /**
     * @var \Ramsey\Uuid\UuidInterface
     * @ApiProperty(identifier=true)
     * @ORM\Column(name="ud_uuid", type="uuid", unique=true)
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
     * @ORM\Column(name="ud_systeme", type="boolean", options={"default":"0"})
     */
    private $systeme;

    /**
     * @Groups({"read", "write"})
     * @ORM\ManyToOne(targetEntity="App\Entity\Droit", cascade={"persist"})
     */
    private $droit;

    /**
     * @Groups({"read", "write"})
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="droits", cascade={"persist"})
     */
    private $user;

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
    public function getSysteme()
    {
        return $this->systeme;
    }

    /**
     * @param mixed $systeme
     */
    public function setSysteme($systeme): void
    {
        $this->systeme = $systeme;
    }

    /**
     * @return mixed
     */
    public function getDroit()
    {
        return $this->droit;
    }

    /**
     * @param mixed $droit
     */
    public function setDroit($droit): void
    {
        $this->droit = $droit;
    }

    /**
     * @return mixed
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param mixed $user
     */
    public function setUser($user): void
    {
        $this->user = $user;
    }
}
