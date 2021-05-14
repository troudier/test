<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
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
class IndicatifTelephone
{

    /**
     * @var \Ramsey\Uuid\UuidInterface
     * @ApiProperty(identifier=true)
     * @ORM\Column(name="indtel_uuid", type="uuid", unique=true)
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
     * @ORM\Column(name="indtel_pays", type="string", length=255)
     */
    private $pays;

    /**
     * @Groups({"read", "write"})
     * @ORM\Column(name="indtel_indicatif", type="string", length=5)
     */
    private $indicatif;

    /**
     * @Groups({"read", "write"})
     * @ORM\Column(name="indtel_message_validation", type="text", nullable=true)
     */
    private $messageValidation;

    /**
     * @Groups({"read", "write"})
     * @ORM\Column(name="indtel_code_pays", type="string", length=2)
     */
    private $codePays;

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
    public function getIndicatif()
    {
        return $this->indicatif;
    }

    /**
     * @param mixed $indicatif
     */
    public function setIndicatif($indicatif): void
    {
        $this->indicatif = $indicatif;
    }

    /**
     * @return mixed
     */
    public function getMessageValidation()
    {
        return $this->messageValidation;
    }

    /**
     * @param mixed $messageValidation
     */
    public function setMessageValidation($messageValidation): void
    {
        $this->messageValidation = $messageValidation;
    }

    /**
     * @return mixed
     */
    public function getCodePays()
    {
        return $this->codePays;
    }

    /**
     * @param mixed $codePays
     */
    public function setCodePays($codePays): void
    {
        $this->codePays = $codePays;
    }

}