<?php

namespace App\Service;

use App\Entity\Zone;
use App\Repository\BookingRepository;
use App\Repository\PricingRepository;

class BookingService
{
    public function __construct(
        private readonly BookingRepository $bookingRepository,
        private readonly PricingRepository $pricingRepository,
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

            $dayKey = $weekDay->getId();
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
}
