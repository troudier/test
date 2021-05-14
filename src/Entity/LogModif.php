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
class LogModif
{

    /**
     * @var \Ramsey\Uuid\UuidInterface
     * @ApiProperty(identifier=true)
     * @ORM\Column(name="log_uuid", type="uuid", unique=true)
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
     * @ORM\Column(name="log_lot_uuid", type="uuid")
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class=UuidGenerator::class)
     */
    private $lotUuid;

    /**
     * @Groups({"read", "write"})
     * @ORM\Column(name="log_table_id", type="integer")
     */
    private $tableId;

    /**
     * @Groups({"read", "write"})
     * @ORM\Column(name="log_pk_id", type="integer")
     */
    private $pkId;

    /**
     * @Groups({"read", "write"})
     * @ORM\Column(name="log_user_uuid", type="uuid")
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class=UuidGenerator::class)
     */
    private $userUuid;

    /**
     * @Groups({"read", "write"})
     * @ORM\Column(name="log_date", type="datetime")
     */
    private $date;

    /**
     * @Groups({"read", "write"})
     * @ORM\Column(name="log_field_name_db", type="string", length=255)
     */
    private $fieldNameDB;

    /**
     * @Groups({"read", "write"})
     * @ORM\Column(name="log_field_name_ui", type="string", length=255)
     */
    private $fieldNameUI;

    /**
     * @Groups({"read", "write"})
     * @ORM\Column(name="log_old_value", type="text")
     */
    private $oldValue;
    /**
     * @Groups({"read", "write"})
     * @ORM\Column(name="log_new_value", type="text")
     */
    private $newValue;


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
    public function getLotUuid()
    {
        return $this->lotUuid;
    }

    /**
     * @param mixed $lotUuid
     */
    public function setLotUuid($lotUuid): void
    {
        $this->lotUuid = $lotUuid;
    }

    /**
     * @return mixed
     */
    public function getTableId()
    {
        return $this->tableId;
    }

    /**
     * @param mixed $tableId
     */
    public function setTableId($tableId): void
    {
        $this->tableId = $tableId;
    }

    /**
     * @return mixed
     */
    public function getPkId()
    {
        return $this->pkId;
    }

    /**
     * @param mixed $pkId
     */
    public function setPkId($pkId): void
    {
        $this->pkId = $pkId;
    }

    /**
     * @return mixed
     */
    public function getUserUuid()
    {
        return $this->userUuid;
    }

    /**
     * @param mixed $userUuid
     */
    public function setUserUuid($userUuid): void
    {
        $this->userUuid = $userUuid;
    }

    /**
     * @return mixed
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @param mixed $date
     */
    public function setDate($date): void
    {
        $this->date = $date;
    }

    /**
     * @return mixed
     */
    public function getFieldNameDB()
    {
        return $this->fieldNameDB;
    }

    /**
     * @param mixed $fieldNameDB
     */
    public function setFieldNameDB($fieldNameDB): void
    {
        $this->fieldNameDB = $fieldNameDB;
    }

    /**
     * @return mixed
     */
    public function getFieldNameUI()
    {
        return $this->fieldNameUI;
    }

    /**
     * @param mixed $fieldNameUI
     */
    public function setFieldNameUI($fieldNameUI): void
    {
        $this->fieldNameUI = $fieldNameUI;
    }

    /**
     * @return mixed
     */
    public function getOldValue()
    {
        return $this->oldValue;
    }

    /**
     * @param mixed $oldValue
     */
    public function setOldValue($oldValue): void
    {
        $this->oldValue = $oldValue;
    }

    /**
     * @return mixed
     */
    public function getNewValue()
    {
        return $this->newValue;
    }

    /**
     * @param mixed $newValue
     */
    public function setNewValue($newValue): void
    {
        $this->newValue = $newValue;
    }

}