<?php

namespace App\Entity;

use App\Repository\UserLogEntryRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Loggable\LogEntryInterface;

#[ORM\Entity(repositoryClass: UserLogEntryRepository::class)]
#[ORM\Table(name: 'user_log_entry')]
#[ORM\Index(name: 'user_log_object_lookup_idx', columns: ['object_id', 'object_class'])]
#[ORM\Index(name: 'user_log_date_lookup_idx', columns: ['logged_at'])]
#[ORM\Index(name: 'user_log_actor_lookup_idx', columns: ['username'])]
#[ORM\Index(name: 'user_log_version_lookup_idx', columns: ['object_id', 'object_class', 'version'])]
class UserLogEntry implements LogEntryInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 8)]
    private ?string $action = null;

    #[ORM\Column(name: 'logged_at', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $loggedAt = null;

    #[ORM\Column(name: 'object_id', length: 64, nullable: true)]
    private ?string $objectId = null;

    #[ORM\Column(name: 'object_class', length: 191)]
    private ?string $objectClass = null;

    #[ORM\Column]
    private ?int $version = null;

    /**
     * @var array<string, mixed>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $data = null;

    #[ORM\Column(length: 191, nullable: true)]
    private ?string $username = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setAction(string $action): void
    {
        $this->action = $action;
    }

    public function getAction(): ?string
    {
        return $this->action;
    }

    public function setUsername(?string $username): void
    {
        $this->username = $username;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setObjectClass(string $objectClass): void
    {
        $this->objectClass = $objectClass;
    }

    public function getObjectClass(): ?string
    {
        return $this->objectClass;
    }

    public function setLoggedAt(): void
    {
        $this->loggedAt = new \DateTime();
    }

    public function getLoggedAt(): ?\DateTimeInterface
    {
        return $this->loggedAt;
    }

    public function setObjectId(string $objectId): void
    {
        $this->objectId = $objectId;
    }

    public function getObjectId(): ?string
    {
        return $this->objectId;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function setData(array $data): void
    {
        $this->data = $data;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getData(): ?array
    {
        return $this->data;
    }

    public function setVersion(int $version): void
    {
        $this->version = $version;
    }

    public function getVersion(): ?int
    {
        return $this->version;
    }
}
