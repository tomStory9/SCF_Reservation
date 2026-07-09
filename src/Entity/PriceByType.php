<?php

namespace App\Entity;

use App\Repository\PriceByTypeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PriceByTypeRepository::class)]
class PriceByType
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?float $price = null;

    #[ORM\ManyToOne(inversedBy: 'priceByTypes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?StayType $stayType = null;

    #[ORM\ManyToOne(inversedBy: 'priceByTypes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Location $location = null;

    /**
     * @var Collection<int, Stays>
     */
    #[ORM\OneToMany(targetEntity: Stays::class, mappedBy: 'pricing')]
    private Collection $stays;

    public function __construct()
    {
        $this->stays = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getStayType(): ?StayType
    {
        return $this->stayType;
    }

    public function setStayType(?StayType $stayType): static
    {
        $this->stayType = $stayType;

        return $this;
    }

    public function getLocation(): ?Location
    {
        return $this->location;
    }

    public function setLocation(?Location $location): static
    {
        $this->location = $location;

        return $this;
    }

    /**
     * @return Collection<int, Stays>
     */
    public function getStays(): Collection
    {
        return $this->stays;
    }

    public function addStay(Stays $stay): static
    {
        if (!$this->stays->contains($stay)) {
            $this->stays->add($stay);
            $stay->setPricing($this);
        }

        return $this;
    }

    public function removeStay(Stays $stay): static
    {
        if ($this->stays->removeElement($stay)) {
            // set the owning side to null (unless already changed)
            if ($stay->getPricing() === $this) {
                $stay->setPricing(null);
            }
        }

        return $this;
    }
}
