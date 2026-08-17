<?php

namespace App\Entity;

use App\Repository\SettingsRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SettingsRepository::class)]
class Settings
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?bool $isRoomBookingEnabled = null;

    #[ORM\Column]
    private ?bool $isUserValidationRequired = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function isRoomBookingEnabled(): ?bool
    {
        return $this->isRoomBookingEnabled;
    }

    public function setIsRoomBookingEnabled(bool $isRoomBookingEnabled): static
    {
        $this->isRoomBookingEnabled = $isRoomBookingEnabled;

        return $this;
    }

    public function isUserValidationRequired(): ?bool
    {
        return $this->isUserValidationRequired;
    }

    public function setIsUserValidationRequired(bool $isUserValidationRequired): static
    {
        $this->isUserValidationRequired = $isUserValidationRequired;

        return $this;
    }
}
