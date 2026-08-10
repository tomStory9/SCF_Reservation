<?php

namespace App\Controller;

use App\Entity\Zone;
use App\Repository\EquipmentRepository;
use App\Service\BookingService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

final class ZoneController extends AbstractController
{
    public function __construct(
        private readonly BookingService $bookingService,
        private readonly EquipmentRepository $equipmentRepository,
    ) {
    }

    #[Route('/zone/{id}/equipments', name: 'app_zone_equipments', methods: ['GET'])]
    public function index(Zone $zone, SerializerInterface $ser): Response
    {
        $equipments = $this->equipmentRepository->findByZoneOrNull($zone);

        return new Response($ser->serialize(
            $equipments,
            'json',
            [
                'attributes' => [
                    'id',
                    'name',
                    'unitPrice',
                    'maxQuantity',
                ],
            ]
        ));
    }

    #[IsGranted('ROLE_USER')]
    #[Route('/zone/{id}/bookings', name: 'app_booking_by_zone', methods: ['GET'])]
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
