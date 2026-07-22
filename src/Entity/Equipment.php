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
     * @var Collection<int, ZoneEquipment>
     */
    #[ORM\OneToMany(targetEntity: ZoneEquipment::class, mappedBy: 'equipment')]
    private Collection $zoneEquipment;

    public function __construct()
    {
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
            $zoneEquipment->setEquipment($this);
        }

        return $this;
    }

    public function removeZoneEquipment(ZoneEquipment $zoneEquipment): static
    {
        if ($this->zoneEquipment->removeElement($zoneEquipment)) {
            // set the owning side to null (unless already changed)
            if ($zoneEquipment->getEquipment() === $this) {
                $zoneEquipment->setEquipment(null);
            }
        }

        return $this;
    }
}
