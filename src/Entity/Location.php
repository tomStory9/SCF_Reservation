<?php

namespace App\Entity;

use App\Repository\LocationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LocationRepository::class)]
class Location
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $typeLocation = null;

    /**
     * @var Collection<int, PriceByType>
     */
    #[ORM\OneToMany(targetEntity: PriceByType::class, mappedBy: 'location')]
    private Collection $priceByTypes;

    public function __construct()
    {
        $this->priceByTypes = new ArrayCollection();
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

    public function getTypeLocation(): ?string
    {
        return $this->typeLocation;
    }

    public function setTypeLocation(string $typeLocation): static
    {
        $this->typeLocation = $typeLocation;

        return $this;
    }

    /**
     * @return Collection<int, PriceByType>
     */
    public function getPriceByTypes(): Collection
    {
        return $this->priceByTypes;
    }

    public function addPriceByType(PriceByType $priceByType): static
    {
        if (!$this->priceByTypes->contains($priceByType)) {
            $this->priceByTypes->add($priceByType);
            $priceByType->setLocation($this);
        }

        return $this;
    }

    public function removePriceByType(PriceByType $priceByType): static
    {
        if ($this->priceByTypes->removeElement($priceByType)) {
            // set the owning side to null (unless already changed)
            if ($priceByType->getLocation() === $this) {
                $priceByType->setLocation(null);
            }
        }

        return $this;
    }
}
