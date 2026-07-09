<?php

namespace App\Entity;

use App\Enum\StayStatus;
use App\Repository\StaysRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StaysRepository::class)]
class Stays
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?\DateTime $dateStart = null;

    #[ORM\Column]
    private ?\DateTime $dateEnd = null;

    #[ORM\ManyToOne(inversedBy: 'stays')]
    #[ORM\JoinColumn(nullable: false)]
    private ?PriceByType $pricing = null;

    #[ORM\Column(enumType: StayStatus::class)]
    private StayStatus $status = StayStatus::PENDING;

    #[ORM\Column]
    private ?bool $isValidate = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $dateValidated = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateStart(): ?\DateTime
    {
        return $this->dateStart;
    }

    public function setDateStart(\DateTime $dateStart): static
    {
        $this->dateStart = $dateStart;

        return $this;
    }

    public function getDateEnd(): ?\DateTime
    {
        return $this->dateEnd;
    }

    public function setDateEnd(\DateTime $dateEnd): static
    {
        $this->dateEnd = $dateEnd;

        return $this;
    }

    public function getPricing(): ?PriceByType
    {
        return $this->pricing;
    }

    public function setPricing(?PriceByType $pricing): static
    {
        $this->pricing = $pricing;

        return $this;
    }

    public function getStatus(): StayStatus
    {
        return $this->status;
    }

    public function setStatus(StayStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function isValidate(): ?bool
    {
        return $this->isValidate;
    }

    public function setIsValidate(bool $isValidate): static
    {
        $this->isValidate = $isValidate;

        return $this;
    }

    public function getDateValidated(): ?\DateTime
    {
        return $this->dateValidated;
    }

    public function setDateValidated(\DateTime $dateValidated): static
    {
        $this->dateValidated = $dateValidated;

        return $this;
    }
}
