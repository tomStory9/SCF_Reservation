<?php

namespace App\Entity;

use App\Enum\BookingStatus;
use App\Repository\BookingRepository;
use App\Validator\NoBookingOverlap;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BookingRepository::class)]
#[NoBookingOverlap]
class Booking
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'bookings')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $userBooking = null;

    #[ORM\Column]
    private ?int $price = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $startDate = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $endDate = null;

    #[ORM\Column(type: 'string', enumType: BookingStatus::class)]
    private ?BookingStatus $bookingStatus = BookingStatus::PENDING;

    #[ORM\ManyToOne(inversedBy: 'bookings')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Zone $zone = null;

    #[ORM\Column]
    private ?int $guestCount = null;

    #[ORM\Column]
    private ?bool $isFullDay = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdDate = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $CheckedInAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $CheckedOutAt = null;

    #[ORM\OneToOne(mappedBy: 'booking', cascade: ['persist', 'remove'], )]
    private ?Transaction $transaction = null;

    /**
     * @var Collection<int, BookingEquipment>
     */
    #[ORM\OneToMany(targetEntity: BookingEquipment::class, mappedBy: 'booking')]
    private Collection $bookingEquipment;

    #[ORM\Column(length: 25000, nullable: true)]
    private ?string $StripeCheckoutUrl = null;

    public function __construct()
    {
        $this->bookingEquipment = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserBooking(): ?User
    {
        return $this->userBooking;
    }

    public function setUserBooking(?User $userBooking): static
    {
        $this->userBooking = $userBooking;

        return $this;
    }

    public function getPrice(): ?int
    {
        return $this->price;
    }

    public function setPrice(int $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getStartDate(): ?\DateTimeImmutable
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeImmutable $startDate): static
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?\DateTimeImmutable
    {
        return $this->endDate;
    }

    public function setEndDate(\DateTimeImmutable $endDate): static
    {
        $this->endDate = $endDate;

        return $this;
    }

    public function getBookingStatus(): ?BookingStatus
    {
        return $this->bookingStatus;
    }

    public function setBookingStatus(BookingStatus $bookingStatus): static
    {
        $this->bookingStatus = $bookingStatus;

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

    public function getGuestCount(): ?int
    {
        return $this->guestCount;
    }

    public function setGuestCount(int $guestCount): static
    {
        $this->guestCount = $guestCount;

        return $this;
    }

    public function isFullDay(): ?bool
    {
        return $this->isFullDay;
    }

    public function setIsFullDay(bool $isFullDay): static
    {
        $this->isFullDay = $isFullDay;

        return $this;
    }

    public function getCreatedDate(): ?\DateTimeImmutable
    {
        return $this->createdDate;
    }

    public function setCreatedDate(\DateTimeImmutable $createdDate): static
    {
        $this->createdDate = $createdDate;

        return $this;
    }

    public function getCheckedInAt(): ?\DateTimeImmutable
    {
        return $this->CheckedInAt;
    }

    public function setCheckedInAt(?\DateTimeImmutable $CheckedInAt): static
    {
        $this->CheckedInAt = $CheckedInAt;

        return $this;
    }

    public function getCheckedOutAt(): ?\DateTimeImmutable
    {
        return $this->CheckedOutAt;
    }

    public function setCheckedOutAt(?\DateTimeImmutable $CheckedOutAt): static
    {
        $this->CheckedOutAt = $CheckedOutAt;

        return $this;
    }

    public function getTransaction(): ?Transaction
    {
        return $this->transaction;
    }

    public function setTransaction(?Transaction $transaction): static
    {
        // unset the owning side of the relation if necessary
        if (null === $transaction && null !== $this->transaction) {
            $this->transaction->setBooking(null);
        }

        // set the owning side of the relation if necessary
        if (null !== $transaction && $transaction->getBooking() !== $this) {
            $transaction->setBooking($this);
        }

        $this->transaction = $transaction;

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
            $bookingEquipment->setBooking($this);
        }

        return $this;
    }

    public function removeBookingEquipment(BookingEquipment $bookingEquipment): static
    {
        if ($this->bookingEquipment->removeElement($bookingEquipment)) {
            // set the owning side to null (unless already changed)
            if ($bookingEquipment->getBooking() === $this) {
                $bookingEquipment->setBooking(null);
            }
        }

        return $this;
    }

    public function getStripeCheckoutUrl(): ?string
    {
        return $this->StripeCheckoutUrl;
    }

    public function setStripeCheckoutUrl(?string $StripeCheckoutUrl): static
    {
        $this->StripeCheckoutUrl = $StripeCheckoutUrl;

        return $this;
    }
}
