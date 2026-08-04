<?php

namespace App\Service;

use App\Entity\Booking;
use App\Entity\User;
use App\Entity\Zone;
use App\Enum\BookingStatus;
use App\Repository\BookingRepository;
use App\Repository\PricingRepository;
use App\Repository\ZoneRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class BookingService
{
    public function __construct(
        private readonly BookingRepository $bookingRepository,
        private readonly PricingRepository $pricingRepository,
        private readonly ZoneRepository $zoneRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly ValidatorInterface $validator,
    ) {
    }

    public function getBookingsByZoneForCalendar(Zone $zone): array
    {
        $bookings = $this->bookingRepository->getBookingsByZone($zone);
        $events = [];
        foreach ($bookings as $booking) {
            $isFullDay = $booking->isFullDay();

            if ($isFullDay) {
                $format = 'Y-m-d';
                $title = 'Journée complète';
            } else {
                $format = 'Y-m-d\TH:i:s';

                $title = sprintf(
                    '%s - %s',
                    $booking->getStartDate()->format('gA'),
                    $booking->getEndDate()->format('gA')
                );
            }

            $events[] = [
                'id' => (string) $booking->getId(),
                'title' => $title,
                'start' => $booking->getStartDate()->format($format),
                'end' => $booking->getEndDate()->format($format),
                'allDay' => $isFullDay,
            ];
        }

        return $events;
    }

    public function getPrincingsByZone(Zone $zone): array
    {
        $pricings = $this->pricingRepository->getPrincingsByZone($zone);

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

            $priceValues = [
                'full' => $pricing->getFullPrice(),
                'reducedA' => $pricing->getReducedPriceA(),
                'reducedB' => $pricing->getReducedPriceB(),
            ];

            if ('hourly' === $periodType) {
                $timeKey = $timeSlot->getStartTime()->format('H:i');
                $pricingsData[$dayKey]['hourly'][$timeKey] = $priceValues;
            } else {
                $pricingsData[$dayKey]['period'][$periodType] = $priceValues;
            }
        }

        return $pricingsData;
    }

    /**
     * @throws \DateMalformedStringException
     * @throws \Exception
     */
    public function createBooking(array $data, User $user): array
    {
        $zone = $this->zoneRepository->find($data['zoneId']);

        if (!$zone) {
            throw new \Exception('Zone introuvable.');
        }

        $booking = new Booking();
        $booking->setUserBooking($user);
        $booking->setZone($zone);
        $booking->setPrice((int) $data['price']);
        $booking->setGuestCount((int) $data['guestNb']);
        $booking->setIsFullDay($data['isFullDay']);
        $booking->setBookingStatus(BookingStatus::PENDING);
        $booking->setCreatedDate(new \DateTimeImmutable());

        $startDateStr = $data['startDate'];

        if ($data['isFullDay']) {
            $start = new \DateTimeImmutable($startDateStr.' 09:00:00');
            $end = new \DateTimeImmutable($startDateStr.' 21:00:00');
        } else {
            $start = new \DateTimeImmutable($startDateStr.' '.$data['startTime'].':00');
            $end = new \DateTimeImmutable($startDateStr.' '.$data['endTime'].':00');
        }

        $booking->setStartDate($start);
        $booking->setEndDate($end);

        $errors = $this->validator->validate($booking);

        if (count($errors) > 0) {
            throw new \Exception($errors[0]->getMessage());
        }

        $this->entityManager->persist($booking);
        $this->entityManager->flush();

        return ['success' => true];
    }
}
