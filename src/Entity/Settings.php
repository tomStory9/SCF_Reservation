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

    #[ORM\Column]
    private ?int $minDayBooking = null;

    #[ORM\Column]
    private ?int $minDayRoomBooking = null;

    #[ORM\Column]
    private ?bool $IsPendingBookingBlocking = null;

    #[ORM\Column]
    private ?int $HourCheckInRoom = null;

    #[ORM\Column]
    private ?int $HourCheckOut = null;

    #[ORM\Column]
    private ?bool $isPendingRoomBlocking = null;

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

    public function getMinDayBooking(): ?int
    {
        return $this->minDayBooking;
    }

    public function setMinDayBooking(int $minDayBooking): static
    {
        $this->minDayBooking = $minDayBooking;

        return $this;
    }

    public function getMinDayRoomBooking(): ?int
    {
        return $this->minDayRoomBooking;
    }

    public function setMinDayRoomBooking(int $minDayRoomBooking): static
    {
        $this->minDayRoomBooking = $minDayRoomBooking;

        return $this;
    }

    public function isPendingBookingBlocking(): ?bool
    {
        return $this->IsPendingBookingBlocking;
    }

    public function setIsPendingBookingBlocking(bool $IsPendingBookingBlocking): static
    {
        $this->IsPendingBookingBlocking = $IsPendingBookingBlocking;

        return $this;
    }

    public function getHourCheckInRoom(): ?int
    {
        return $this->HourCheckInRoom;
    }

    public function setHourCheckInRoom(int $HourCheckInRoom): static
    {
        $this->HourCheckInRoom = $HourCheckInRoom;

        return $this;
    }

    public function getHourCheckOut(): ?int
    {
        return $this->HourCheckOut;
    }

    public function setHourCheckOut(int $HourCheckOut): static
    {
        $this->HourCheckOut = $HourCheckOut;

        return $this;
    }

    public function isPendingRoomBlocking(): ?bool
    {
        return $this->isPendingRoomBlocking;
    }

    public function setIsPendingRoomBlocking(bool $isPendingRoomBlocking): static
    {
        $this->isPendingRoomBlocking = $isPendingRoomBlocking;

        return $this;
    }
}
