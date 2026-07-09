<?php

namespace App\Entity;

use App\Repository\StayTypeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StayTypeRepository::class)]
class StayType
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column]
    private ?bool $active = null;

    /**
     * @var Collection<int, PriceByType>
     */
    #[ORM\OneToMany(targetEntity: PriceByType::class, mappedBy: 'stayType')]
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

    public function isActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;

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
            $priceByType->setStayType($this);
        }

        return $this;
    }

    public function removePriceByType(PriceByType $priceByType): static
    {
        if ($this->priceByTypes->removeElement($priceByType)) {
            // set the owning side to null (unless already changed)
            if ($priceByType->getStayType() === $this) {
                $priceByType->setStayType(null);
            }
        }

        return $this;
    }
}
