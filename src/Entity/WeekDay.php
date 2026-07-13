<?php

namespace App\Entity;

use App\Repository\WeekDayRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WeekDayRepository::class)]
class WeekDay
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $label = null;

    /**
     * @var Collection<int, Pricing>
     */
    #[ORM\OneToMany(targetEntity: Pricing::class, mappedBy: 'weekDay')]
    private Collection $pricings;

    public function __construct()
    {
        $this->pricings = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): static
    {
        $this->label = $label;

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
            $pricing->setWeekDay($this);
        }

        return $this;
    }

    public function removePricing(Pricing $pricing): static
    {
        if ($this->pricings->removeElement($pricing)) {
            // set the owning side to null (unless already changed)
            if ($pricing->getWeekDay() === $this) {
                $pricing->setWeekDay(null);
            }
        }

        return $this;
    }
}
