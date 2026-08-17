<?php

namespace App\Controller;

use App\Entity\Booking;
use App\Repository\BookingRepository;
use App\Repository\ZoneRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

final class BookingCalendarController extends AbstractController
{
    public function __construct(
        private readonly BookingRepository $bookingRepository,
        private readonly ZoneRepository $zoneRepository,
    ) {
    }

    #[Route(
        path: '/booking-calendar',
        name: 'admin_booking_calendar',
    )]
    public function calendar(Request $request, SerializerInterface $serializer): Response
    {
        $today = new \DateTimeImmutable();

        $year = $request->query->getInt(
            'year',
            (int) $today->format('Y'),
        );

        $month = $request->query->getInt(
            'month',
            (int) $today->format('n'),
        );

        if ($month < 1 || $month > 12) {
            $month = (int) $today->format('n');
        }

        if ($year < 2000) {
            $year = (int) $today->format('Y');
        }

        $zones = $this->zoneRepository->findAll();

        $bookings = $this->bookingRepository->findBookingsByMonth(
            $year,
            $month,
        );
        $bookings = array_map(static function (Booking $booking): array {
            return [
                'id' => $booking->getId(),
                'price' => $booking->getPrice(),
                'startDate' => $booking->getStartDate()->format('Y-m-d H:i:s'),
                'endDate' => $booking->getEndDate()->format('Y-m-d H:i:s'),
                'status' => $booking->getBookingStatus(),
                'isFullDay' => $booking->isFullDay(),
                'equipmentPrice' => $booking->getEquipmentPrice(),
                'totalPrice' => $booking->getTotalPrice(),
                'guestCount' => $booking->getGuestCount(),
                'zone' => [
                    'id' => $booking->getZone()?->getId(),
                    'name' => $booking->getZone()?->getName(),
                ],
                'userBooking' => [
                    'id' => $booking->getUserBooking()?->getId(),
                    'name' => $booking->getUserBooking()?->getName(),
                    'lastName' => $booking->getUserBooking()?->getLastName(),
                ],
                'bookingEquipment' => array_map(static function ($equipment) {
                    return [
                        'id' => $equipment->getId(),
                        'price' => $equipment->getTotalPrice(),
                        'quantity' => $equipment->getQuantity(),
                        'equipment' => [
                            'id' => $equipment->getEquipment()?->getId(),
                            'name' => $equipment->getEquipment()?->getName(),
                            'unitPrice' => $equipment->getEquipment()?->getUnitPrice(),
                        ],
                    ];
                }, $booking->getBookingEquipment()->toArray()),
            ];
        }, $bookings);

        return $this->render(
            'admin/dashboard/booking_calendar.html.twig',
            [
                'zones' => $zones,
                'bookings' => $bookings,
                'currentYear' => $year,
                'currentMonth' => $month,
            ],
        );
    }
}
