<?php

namespace App\Entity;

use App\Repository\UserRoleRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserRoleRepository::class)]
class UserRole
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $roleName = null;

    #[ORM\Column(length: 255)]
    private ?string $label = null;

    #[ORM\Column]
    private ?int $allocatedHoursPerMonth = null;

    #[ORM\Column(nullable: true)]
    private ?int $maxAdvanceBookingDays = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRoleName(): ?string
    {
        return $this->roleName;
    }

    public function setRoleName(string $roleName): static
    {
        $this->roleName = $roleName;

        return $this;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getAllocatedHoursPerMonth(): ?int
    {
        return $this->allocatedHoursPerMonth;
    }

    public function setAllocatedHoursPerMonth(int $allocatedHoursPerMonth): static
    {
        $this->allocatedHoursPerMonth = $allocatedHoursPerMonth;

        return $this;
    }

    public function __toString(): string
    {
        return sprintf('%s (%dh)', $this->label, $this->allocatedHoursPerMonth);
    }

    public function getMaxAdvanceBookingDays(): ?int
    {
        return $this->maxAdvanceBookingDays;
    }

    public function setMaxAdvanceBookingDays(?int $maxAdvanceBookingDays): static
    {
        $this->maxAdvanceBookingDays = $maxAdvanceBookingDays;

        return $this;
    }
}
