<?php

namespace App\Entity;

use App\Repository\EquipmentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EquipmentRepository::class)]
class Equipment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    /**
     * @var Collection<int, BookingEquipment>
     */
    #[ORM\OneToMany(targetEntity: BookingEquipment::class, mappedBy: 'equipment')]
    private Collection $bookingEquipment;

    #[ORM\Column]
    private ?int $unitPrice = null;

    #[ORM\Column]
    private ?int $maxQuantity = null;

    #[ORM\ManyToOne(inversedBy: 'equipment')]
    private ?Zone $zone = null;

    public function __construct()
    {
        $this->bookingEquipment = new ArrayCollection();
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

    /**
     * @return Collection<int, BookingEquipment>
     */
    public function getBookingEquipment(): Collection
    {
        return $this->bookingEquipment;
    }

    public function addBookingEquipment(BookingEquipment $bookingEquipment): static
    {
        if (!$this->bookingEquipment->contains($bookingEquipment)) {
            $this->bookingEquipment->add($bookingEquipment);
            $bookingEquipment->setEquipment($this);
        }

        return $this;
    }

    public function removeBookingEquipment(BookingEquipment $bookingEquipment): static
    {
        if ($this->bookingEquipment->removeElement($bookingEquipment)) {
            // set the owning side to null (unless already changed)
            if ($bookingEquipment->getEquipment() === $this) {
                $bookingEquipment->setEquipment(null);
            }
        }

        return $this;
    }

    public function getUnitPrice(): ?int
    {
        return $this->unitPrice;
    }

    public function setUnitPrice(int $unitPrice): static
    {
        $this->unitPrice = $unitPrice;

        return $this;
    }

    public function getMaxQuantity(): ?int
    {
        return $this->maxQuantity;
    }

    public function setMaxQuantity(int $maxQuantity): static
    {
        $this->maxQuantity = $maxQuantity;

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
}
