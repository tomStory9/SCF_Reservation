<?php

namespace App\Service;

use App\Entity\Booking;
use App\Entity\BookingEquipment;
use App\Entity\User;
use App\Entity\Zone;
use App\Enum\BookingStatus;
use App\Repository\BlockoutPeriodRepository;
use App\Repository\BookingRepository;
use App\Repository\EquipmentRepository;
use App\Repository\PricingRepository;
use App\Repository\UserRoleRepository;
use App\Repository\ZoneRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

readonly class BookingService
{
    public function __construct(
        private BookingRepository $bookingRepository,
        private PricingRepository $pricingRepository,
        private ZoneRepository $zoneRepository,
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator,
        private UserRoleRepository $userRoleRepository,
        private EquipmentRepository $equipmentRepository,
        private KodaOverlapService $kodaOverlapService,
        private TranslatorInterface $translator,
        private BlockoutPeriodRepository $blockoutPeriodRepository,
        private StripePaiementService $stripePaymentService,
        private MailerService $mailerService
    ) {
    }

    public function getBookingsByZoneForCalendar(Zone $zone, string $type = 'training'): array
    {
        $conflictingCodes = $this->kodaOverlapService->getConflictingZoneCodes($zone);

        $bookings = $this->bookingRepository->getBookingsForZoneAndConflicts($zone, $conflictingCodes, $type);
        $events = [];

        foreach ($bookings as $booking) {
            $isFullDay = $booking->isFullDay();
            $isPending = BookingStatus::PENDING === $booking->getBookingStatus();
            $isConflict = $booking->getZone() !== $zone;

            // Pour les rooms, on affiche toujours en mode "jour" (pas d'heures)
            if ('room' === $type) {
                $format = 'Y-m-d';
                $startLabel = $booking->getStartDate()->format('d/m');
                $endLabel = $booking->getEndDate()->format('d/m');

                $title = $isConflict
                    ? $this->translator->trans('calendar.event.unavailable_other_configuration')
                    : sprintf('%s – %s', $startLabel, $endLabel);
            } elseif ($isFullDay) {
                $format = 'Y-m-d';
                $title = $isConflict
                    ? $this->translator->trans('calendar.event.unavailable_other_configuration')
                    : $this->translator->trans('calendar.event.full_day');
            } else {
                $format = 'Y-m-d\TH:i:s';
                $timeStr = sprintf(
                    '%s - %s',
                    $booking->getStartDate()->format('gA'),
                    $booking->getEndDate()->format('gA')
                );
                $title = $isConflict
                    ? $this->translator->trans('calendar.event.unavailable').' - '.$timeStr
                    : $timeStr;
            }

            $event = [
                'id' => (string) $booking->getId(),
                'title' => $title,
                'start' => $booking->getStartDate()->format($format),
                'end' => $booking->getEndDate()->format($format),
                'allDay' => $isFullDay || 'room' === $type,
            ];

            if ($isConflict) {
                $event['color'] = '#cbd5e1';
                $event['textColor'] = '#475569';
                $event['interactive'] = false;
            }

            if ($isPending && !$isConflict) {
                $event['backgroundColor'] = 'rgba(59, 130, 246, 0.6)';
                $event['title'] = '⏳ '.$event['title'];
            }

            $events[] = $event;
        }

        return $events;
    }

    public function getPrincingsByZone(Zone $zone, User $user): array
    {
        $pricings = $this->pricingRepository->getPrincingsByZone($zone);

        $userRole = $this->userRoleRepository->findRoleForUser($user);
        $tarifUser = $userRole->getTarif();

        $pricingsData = [];

        foreach ($pricings as $pricing) {
            $weekDay = $pricing->getWeekDay();
            $timeSlot = $pricing->getTimeSlot();

            if (!$weekDay || !$timeSlot) {
                continue;
            }

            $dayKey = $weekDay->getDayNumber();
            $periodType = $timeSlot->getPeriod()->value;

            if (!isset($pricingsData[$dayKey])) {
                $pricingsData[$dayKey] = [
                    'hourly' => [],
                    'period' => [],
                ];
            }

            $price = match ($tarifUser) {
                'B' => $pricing->getReducedPriceB(),
                'A' => $pricing->getReducedPriceA(),
                'FREE' => 0,
                default => $pricing->getFullPrice(),
            };

            if ('hourly' === $periodType) {
                $timeKey = $timeSlot->getStartTime()->format('H:i');
                $pricingsData[$dayKey]['hourly'][$timeKey] = $price;
            } else {
                $pricingsData[$dayKey]['period'][$periodType] = $price;
            }
        }

        return $pricingsData;
    }

    /**
     * @throws \DateMalformedStringException
     * @throws \Exception
     */
    public function createBooking(array $data, User $user): void
    {
        $zone = $this->zoneRepository->find($data['zoneId']);

        if (!$zone) {
            throw new \Exception($this->translator->trans('error.zone_not_found', domain: 'reservation'));
        }

        $userRole = $this->userRoleRepository->findRoleForUser($user);

        $maxAdvanceDays = $userRole && null !== $userRole->getMaxAdvanceBookingDays()
            ? $userRole->getMaxAdvanceBookingDays()
            : 30;

        $limitDate = new \DateTimeImmutable('today')
            ->modify(sprintf('+%d days', $maxAdvanceDays))
            ->setTime(23, 59, 59);

        $startDateStr = $data['startDate'];

        if ($data['isFullDay']) {
            $start = new \DateTimeImmutable($startDateStr.' 09:00:00');
            $end = new \DateTimeImmutable($startDateStr.' 21:00:00');
        } else {
            $start = new \DateTimeImmutable($startDateStr.' '.$data['startTime'].':00');
            $end = new \DateTimeImmutable($startDateStr.' '.$data['endTime'].':00');
        }

        if ($start > $limitDate) {
            throw new \Exception($this->translator->trans('error.advance_limit', ['%max_days%' => $maxAdvanceDays, '%limit_date%' => $limitDate->format('d/m/Y')], 'reservation'));
        }

        $durationSeconds = $end->getTimestamp() - $start->getTimestamp();
        $durationHours = $durationSeconds / 3600;

        $freeHours = $this->bookingRepository->getRemainingFreeHoursThisMonth($user);

        $basePrice = (int) ($data['basePrice'] ?? $data['price']);
        $finalPrice = $basePrice;

        if (($data['bookingMode'] ?? 'hour') === 'period') {
            if ($freeHours >= 4) {
                $finalPrice = 0;
            } elseif ($freeHours > 0) {
                $finalPrice = $basePrice * ((4 - $freeHours) / 4);
            }
        } else {
            if ($freeHours >= $durationHours) {
                $finalPrice = 0;
            } elseif ($freeHours > 0) {
                $finalPrice = $basePrice * (($durationHours - $freeHours) / $durationHours);
            }
        }

        $expectedPrice = (int) round($finalPrice);
        $receivedPrice = (int) $data['price'];

        if ($receivedPrice !== $expectedPrice) {
            throw new \Exception($this->translator->trans('error.price_changed', ['%expected_price%' => $expectedPrice, '%received_price%' => $receivedPrice], 'reservation'));
        }

        $booking = new Booking();
        $booking->setUserBooking($user);
        $booking->setZone($zone);

        $booking->setPrice($expectedPrice);

        $booking->setGuestCount((int) $data['guestNb']);
        $booking->setIsFullDay($data['isFullDay']);
        $booking->setBookingStatus(BookingStatus::PENDING);
        $booking->setCreatedDate(new \DateTimeImmutable());

        $booking->setStartDate($start);
        $booking->setEndDate($end);

        $errors = $this->validator->validate($booking);

        if (count($errors) > 0) {
            throw new \Exception($errors[0]->getMessage());
        }

        foreach ($data['equipments'] as $equipment) {
            $bookingEquipment = new BookingEquipment();
            $bookingEquipment->setEquipment($this->equipmentRepository->find($equipment['equipmentId']));
            $bookingEquipment->setQuantity($equipment['quantity']);
            $bookingEquipment->setTotalPrice($this->equipmentRepository->find($equipment['equipmentId'])->getUnitPrice() * $equipment['quantity']);
            $bookingEquipment->setBooking($booking);
            $this->entityManager->persist($bookingEquipment);
            $booking->addBookingEquipment($bookingEquipment);
        }
        $booking->setEquipmentPrice($this->equipmentRepository->calculateTotalEquipmentPrice($booking));
        $booking->setTotalPrice($booking->getPrice() + $booking->getEquipmentPrice());
        $this->entityManager->persist($booking);
        $this->entityManager->flush();
        $this->mailerService->sendNewBookingAdmin($booking, $booking->getUserBooking());
        $this->mailerService->sendBookingPending($booking, $booking->getUserBooking());
    }

    public function approveBooking(Booking $booking): void
    {
        if (BookingStatus::PENDING !== $booking->getBookingStatus()) {
            throw new \Exception('La réservation n\'est pas en attente de validation.');
        }

        $booking->setBookingStatus(BookingStatus::APPROVED);
        $booking->setStripeCheckoutUrl($this->stripePaymentService->createPaymentLink($booking->getTotalPrice(), $booking->getUserBooking()->getId(), $booking->getId()));
        $this->entityManager->flush();
        $this->mailerService->sendBookingConfirmationEmail($booking->getUserBooking(), $booking);
    }

    public function declineBooking(Booking $booking): void
    {
        if (BookingStatus::PENDING !== $booking->getBookingStatus()) {
            throw new \Exception('La réservation n\'est pas en attente de validation.');
        }

        $booking->setBookingStatus(BookingStatus::DECLINED);
        $this->entityManager->flush();
        $this->mailerService->sendBookingDeniedEmail($booking->getUserBooking(), $booking);
    }

    public function getUnavailiblePeriods(): array
    {
        $activePeriods = $this->blockoutPeriodRepository->findBy(['active' => true]);

        return array_map(function ($unavailability) {
            return [
                'id' => $unavailability->getId(),
                'start' => $unavailability->getStartDate()->format('Y-m-d\TH:i:s'),
                'end' => $unavailability->getEndDate()->format('Y-m-d\TH:i:s'),
            ];
        }, $activePeriods);
    }
}
