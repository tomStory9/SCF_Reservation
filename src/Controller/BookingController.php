<?php

namespace App\Controller;

use App\Entity\Facility;
use App\Entity\Zone;
use App\Repository\BookingRepository;
use App\Repository\FacilityRepository;
use App\Repository\ZoneRepository;
use App\Service\BookingService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class BookingController extends AbstractController
{
    public function __construct(
        private readonly BookingRepository $bookingRepository,
        private readonly FacilityRepository $facilityRepository,
        private readonly ZoneRepository $zoneRepository,
        private readonly BookingService $bookingService,
    ) {
    }

    #[IsGranted('ROLE_USER')]
    #[Route('/booking', name: 'app_booking_index', methods: ['GET'])]
    public function index(): Response
    {
        $user = $this->getUser();
        $facilities = $this->facilityRepository->findAll();

        return $this->render('user/reservation.html.twig', [
            'user' => $user,
            'facilities' => $facilities,
        ]);
    }

    #[Route('/facility/{id}/zones', name: 'app_booking_training_zone', methods: ['GET'])]
    public function getTrainingZoneByFacility(Facility $facility): JsonResponse
    {
        $zones = $this->zoneRepository->getTrainingZonesByFacility($facility);

        $zonesJson = [];
        foreach ($zones as $zone) {
            $zonesJson[] = [
                'id' => $zone->getId(),
                'name' => $zone->getName(),
                'code' => $zone->getCode(),
                'maxCapacity' => $zone->getMaxCapacity(),
            ];
        }

        return new JsonResponse($zonesJson);
    }

    #[Route('zone/{id}/bookings', name: 'app_booking_by_zone', methods: ['GET'])]
    public function getExistingBookingsByZone(Zone $zone): JsonResponse
    {
        $events = $this->bookingService->getBookingsByZoneForCalendar($zone);

        return new JsonResponse($events);
    }

    #[Route('/zone/{id}/pricings', name: 'app_booking_pricings_by_zone', methods: ['GET'])]
    public function getPricingsByZone(Zone $zone): JsonResponse
    {
        $pricingsData = $this->bookingService->getPrincingsByZone($zone);

        return new JsonResponse($pricingsData);
    }
}
