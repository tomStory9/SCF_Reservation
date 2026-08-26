<?php

namespace App\Twig;

use App\Entity\User;
use App\Repository\BookingRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class MemberExtension extends AbstractExtension
{
    public function __construct(
        private readonly BookingRepository $bookingRepository,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('remaining_free_hours', $this->getRemainingFreeHours(...)),
        ];
    }

    public function getRemainingFreeHours(?User $user): float
    {
        if (!$user instanceof User) {
            return 0.0;
        }

        return $this->bookingRepository->getRemainingFreeHoursThisMonth($user);
    }
}
