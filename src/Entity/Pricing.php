<?php

namespace App\Entity;

use App\Repository\PricingRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PricingRepository::class)]
class Pricing
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'pricings')]
    #[ORM\JoinColumn(nullable: false)]
    private ?WeekDay $weekDay = null;

    #[ORM\ManyToOne(inversedBy: 'pricings')]
    private ?TimeSlot $timeSlot = null;

    #[ORM\Column]
    private ?int $fullPrice = null;

    #[ORM\ManyToOne(inversedBy: 'pricings')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Zone $zone = null;

    #[ORM\Column]
    private ?int $reducedPriceA = null;

    #[ORM\Column]
    private ?int $reducedPriceB = null;

    #[ORM\Column(nullable: true)]
    private ?int $guestCount = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getWeekDay(): ?WeekDay
    {
        return $this->weekDay;
    }

    public function setWeekDay(?WeekDay $weekDay): static
    {
        $this->weekDay = $weekDay;

        return $this;
    }

    public function getTimeSlot(): ?TimeSlot
    {
        return $this->timeSlot;
    }

    public function setTimeSlot(?TimeSlot $timeSlot): static
    {
        $this->timeSlot = $timeSlot;

        return $this;
    }

    public function getFullPrice(): ?int
    {
        return $this->fullPrice;
    }

    public function setFullPrice(int $fullPrice): static
    {
        $this->fullPrice = $fullPrice;

        return $this;
    }

    public function getZone(): ?Zone
    {
        return $this->zone;
    }

    public function setZone(?Zone $zone): static
    {
        $this->zone = $zone;

        return $this;
    }

    public function getReducedPriceA(): ?int
    {
        return $this->reducedPriceA;
    }

    public function setReducedPriceA(int $reducedPriceA): static
    {
        $this->reducedPriceA = $reducedPriceA;

        return $this;
    }

    public function getReducedPriceB(): ?int
    {
        return $this->reducedPriceB;
    }

    public function setReducedPriceB(int $reducedPriceB): static
    {
        $this->reducedPriceB = $reducedPriceB;

        return $this;
    }

    public function getGuestCount(): ?int
    {
        return $this->guestCount;
    }

    public function setGuestCount(?int $guestCount): static
    {
        $this->guestCount = $guestCount;

        return $this;
    }
}
