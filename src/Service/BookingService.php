<?php

namespace App\Service;

use App\Entity\Booking;
use App\Entity\BookingEquipment;
use App\Entity\User;
use App\Entity\Zone;
use App\Enum\BookingStatus;
use App\Repository\BookingRepository;
use App\Repository\EquipmentRepository;
use App\Repository\PricingRepository;
use App\Repository\UserRoleRepository;
use App\Repository\ZoneRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

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
    ) {
    }

    public function getBookingsByZoneForCalendar(Zone $zone): array
    {
        $conflictingCodes = $this->kodaOverlapService->getConflictingZoneCodes($zone);

        $bookings = $this->bookingRepository->getBookingsForZoneAndConflicts($zone, $conflictingCodes);

        $events = [];
        foreach ($bookings as $booking) {
            $isFullDay = $booking->isFullDay();

            $isConflict = $booking->getZone() !== $zone;

            if ($isFullDay) {
                $format = 'Y-m-d';
                $title = $isConflict ? 'Indisponible (Autre configuration)' : 'All day';
            } else {
                $format = 'Y-m-d\TH:i:s';
                $timeStr = sprintf(
                    '%s - %s',
                    $booking->getStartDate()->format('gA'),
                    $booking->getEndDate()->format('gA')
                );
                $title = $isConflict ? 'Indisponible - '.$timeStr : $timeStr;
            }

            $event = [
                'id' => (string) $booking->getId(),
                'title' => $title,
                'start' => $booking->getStartDate()->format($format),
                'end' => $booking->getEndDate()->format($format),
                'allDay' => $isFullDay,
            ];

            if ($isConflict) {
                $event['color'] = '#cbd5e1';
                $event['textColor'] = '#475569';

                $event['interactive'] = false;
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
            throw new \Exception('Zone introuvable.');
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
            throw new \Exception(sprintf('Votre statut vous permet de réserver au maximum %d jours à l\'avance (jusqu\'au %s).', $maxAdvanceDays, $limitDate->format('d/m/Y')));
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
            throw new \Exception(sprintf('Le tarif calculé (%d ¥) ne correspond pas au tarif affiché (%d ¥). Vos heures gratuites ont peut-être été mises à jour. Veuillez rafraîchir la page.', $expectedPrice, $receivedPrice));
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
    }
}
