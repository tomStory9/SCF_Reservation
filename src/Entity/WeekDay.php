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

    #[ORM\Column]
    private ?int $dayNumber = null;

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

    public function getDayNumber(): ?int
    {
        return $this->dayNumber;
    }

    public function setDayNumber(int $dayNumber): static
    {
        $this->dayNumber = $dayNumber;

        return $this;
    }

    public function __toString(): string
    {
        return $this->getLabel();
    }
}
