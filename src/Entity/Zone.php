<?php

namespace App\Entity;

use App\Enum\ZoneType;
use App\Repository\ZoneRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ZoneRepository::class)]
class Zone
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: 'string', enumType: ZoneType::class)]
    private ?ZoneType $typeZone = null;

    /**
     * @var Collection<int, Pricing>
     */
    #[ORM\OneToMany(targetEntity: Pricing::class, mappedBy: 'zone')]
    private Collection $pricings;

    /**
     * @var Collection<int, Booking>
     */
    #[ORM\OneToMany(targetEntity: Booking::class, mappedBy: 'zone')]
    private Collection $bookings;

    #[ORM\Column(nullable: true)]
    private ?int $maxCapacity = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $code = null;

    #[ORM\ManyToOne(inversedBy: 'zones')]
    private ?Facility $facility = null;

    /**
     * @var Collection<int, ZoneEquipment>
     */
    #[ORM\OneToMany(targetEntity: ZoneEquipment::class, mappedBy: 'zone')]
    private Collection $zoneEquipment;

    public function __construct()
    {
        $this->pricings = new ArrayCollection();
        $this->bookings = new ArrayCollection();
        $this->zoneEquipment = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getTypeZone(): ?ZoneType
    {
        return $this->typeZone;
    }

    public function setTypeZone(ZoneType $typeZone): static
    {
        $this->typeZone = $typeZone;

        return $this;
    }

    /**
     * @return Collection<int, Pricing>
     */
    public function getPricings(): Collection
    {
        return $this->pricings;
    }

    public function addPricing(Pricing $pricing): static
    {
        if (!$this->pricings->contains($pricing)) {
            $this->pricings->add($pricing);
            $pricing->setZone($this);
        }

        return $this;
    }

    public function removePricing(Pricing $pricing): static
    {
        if ($this->pricings->removeElement($pricing)) {
            // set the owning side to null (unless already changed)
            if ($pricing->getZone() === $this) {
                $pricing->setZone(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Booking>
     */
    public function getBookings(): Collection
    {
        return $this->bookings;
    }

    public function addBooking(Booking $booking): static
    {
        if (!$this->bookings->contains($booking)) {
            $this->bookings->add($booking);
            $booking->setZone($this);
        }

        return $this;
    }

    public function removeBooking(Booking $booking): static
    {
        if ($this->bookings->removeElement($booking)) {
            // set the owning side to null (unless already changed)
            if ($booking->getZone() === $this) {
                $booking->setZone(null);
            }
        }

        return $this;
    }

    public function getMaxCapacity(): ?int
    {
        return $this->maxCapacity;
    }

    public function setMaxCapacity(?int $maxCapacity): static
    {
        $this->maxCapacity = $maxCapacity;

        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function __toString(): string
    {
        return $this->getName();
    }

    public function getFacility(): ?Facility
    {
        return $this->facility;
    }

    public function setFacility(?Facility $facility): static
    {
        $this->facility = $facility;

        return $this;
    }

    /**
     * @return Collection<int, ZoneEquipment>
     */
    public function getZoneEquipment(): Collection
    {
        return $this->zoneEquipment;
    }

    public function addZoneEquipment(ZoneEquipment $zoneEquipment): static
    {
        if (!$this->zoneEquipment->contains($zoneEquipment)) {
            $this->zoneEquipment->add($zoneEquipment);
            $zoneEquipment->setZone($this);
        }

        return $this;
    }

    public function removeZoneEquipment(ZoneEquipment $zoneEquipment): static
    {
        if ($this->zoneEquipment->removeElement($zoneEquipment)) {
            // set the owning side to null (unless already changed)
            if ($zoneEquipment->getZone() === $this) {
                $zoneEquipment->setZone(null);
            }
        }

        return $this;
    }
}
