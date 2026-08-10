<?php

namespace App\Controller;

use App\Entity\Zone;
use App\Service\BookingService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class ZoneController extends AbstractController
{
    public function __construct(
        private readonly BookingService $bookingService,
    ) {
    }

    #[Route('/zone/{zoneId}/equipments', name: 'app_zone_equipments', methods: ['GET'])]
    public function index(Zone $zone): JsonResponse
    {
        $equipments = $zone->getEquipment();

        return new JsonResponse($equipments);
    }

    #[IsGranted('ROLE_USER')]
    #[Route('zone/{id}/bookings', name: 'app_booking_by_zone', methods: ['GET'])]
    public function getExistingBookingsByZone(Zone $zone): JsonResponse
    {
        $events = $this->bookingService->getBookingsByZoneForCalendar($zone);

        return new JsonResponse($events);
    }

    #[IsGranted('ROLE_USER')]
    #[Route('/zone/{id}/pricings', name: 'app_booking_pricings_by_zone', methods: ['GET'])]
    public function getPricingsByZone(Zone $zone): JsonResponse
    {
        $pricingsData = $this->bookingService->getPrincingsByZone($zone, $this->getUser());

        return new JsonResponse($pricingsData);
    }
}
