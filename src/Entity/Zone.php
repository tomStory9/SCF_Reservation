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

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $code = null;

    #[ORM\ManyToOne(inversedBy: 'zones')]
    private ?Facility $facility = null;

    /**
     * @var Collection<int, Equipment>
     */
    #[ORM\OneToMany(targetEntity: Equipment::class, mappedBy: 'zone')]
    private Collection $equipment;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $jpDesc = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $enDesc = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $frDesc = null;

    public function __construct()
    {
        $this->pricings = new ArrayCollection();
        $this->bookings = new ArrayCollection();
        $this->equipment = new ArrayCollection();
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
     * @return Collection<int, Equipment>
     */
    public function getEquipment(): Collection
    {
        return $this->equipment;
    }

    public function addEquipment(Equipment $equipment): static
    {
        if (!$this->equipment->contains($equipment)) {
            $this->equipment->add($equipment);
            $equipment->setZone($this);
        }

        return $this;
    }

    public function removeEquipment(Equipment $equipment): static
    {
        if ($this->equipment->removeElement($equipment)) {
            // set the owning side to null (unless already changed)
            if ($equipment->getZone() === $this) {
                $equipment->setZone(null);
            }
        }

        return $this;
    }

    public function getJpDesc(): ?string
    {
        return $this->jpDesc;
    }

    public function setJpDesc(?string $jpDesc): static
    {
        $this->jpDesc = $jpDesc;

        return $this;
    }

    public function getEnDesc(): ?string
    {
        return $this->enDesc;
    }

    public function setEnDesc(?string $enDesc): static
    {
        $this->enDesc = $enDesc;

        return $this;
    }

    public function getFrDesc(): ?string
    {
        return $this->frDesc;
    }

    public function setFrDesc(?string $frDesc): static
    {
        $this->frDesc = $frDesc;

        return $this;
    }
}
