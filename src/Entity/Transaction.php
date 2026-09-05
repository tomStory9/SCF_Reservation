<?php

namespace App\Entity;

use App\Repository\TransactionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TransactionRepository::class)]
class Transaction
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'transaction', cascade: ['persist', 'remove'])]
    private ?Booking $booking = null;

    #[ORM\Column]
    private ?int $paidPrice = null;

    #[ORM\Column]
    private ?\DateTime $timestamp = null;

    #[ORM\Column(nullable: true)]
    private ?int $stripeFee = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $stripePaymentIntentId = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBooking(): ?Booking
    {
        return $this->booking;
    }

    public function setBooking(?Booking $booking): static
    {
        $this->booking = $booking;

        return $this;
    }

    public function getPaidPrice(): ?int
    {
        return $this->paidPrice;
    }

    public function setPaidPrice(int $paidPrice): static
    {
        $this->paidPrice = $paidPrice;

        return $this;
    }

    public function getTimestamp(): ?\DateTime
    {
        return $this->timestamp;
    }

    public function setTimestamp(\DateTime $timestamp): static
    {
        $this->timestamp = $timestamp;

        return $this;
    }

    public function getStripeFee(): ?int
    {
        return $this->stripeFee;
    }

    public function setStripeFee(?int $stripeFee): static
    {
        $this->stripeFee = $stripeFee;

        return $this;
    }

    public function getStripePaymentIntentId(): ?string
    {
        return $this->stripePaymentIntentId;
    }

    public function setStripePaymentIntentId(?string $stripePaymentIntentId): static
    {
        $this->stripePaymentIntentId = $stripePaymentIntentId;

        return $this;
    }
}
