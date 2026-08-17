<?php

namespace App\Controller;

use App\Entity\Zone;
use App\Enum\BookingStatus;
use App\Repository\EquipmentRepository;
use App\Service\BookingService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ZoneController extends AbstractController
{
    public function __construct(
        private readonly BookingService $bookingService,
        private readonly EquipmentRepository $equipmentRepository,
        private readonly TranslatorInterface $translator,
    ) {
    }

    #[Route('/zone/{id}/equipments', name: 'app_zone_equipments', methods: ['GET'])]
    public function index(
        Zone $zone,
        Request $request,
    ): JsonResponse {
        $startDateParameter = $request->query->get('startDate');
        $endDateParameter = $request->query->get('endDate');

        if (!$startDateParameter || !$endDateParameter) {
            return new JsonResponse([
                'error' => $this->translator->trans('api.error.missing_dates'),
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $startDate = new \DateTimeImmutable($startDateParameter);
            $endDate = new \DateTimeImmutable($endDateParameter);
        } catch (\Exception) {
            return new JsonResponse([
                'error' => $this->translator->trans('api.error.invalid_date_format'),
            ], Response::HTTP_BAD_REQUEST);
        }

        if ($endDate <= $startDate) {
            return new JsonResponse([
                'error' => $this->translator->trans('api.error.invalid_date_order'),
            ], Response::HTTP_BAD_REQUEST);
        }

        /*
         * À adapter selon ton enum.
         *
         * Les réservations PENDING et CONFIRMED bloquent généralement
         * le stock. Une réservation CANCELLED ne doit pas être comptée.
         */
        $blockingStatuses = [
            BookingStatus::APPROVED->value,
            BookingStatus::PAID->value,
        ];

        $equipments = $this->equipmentRepository
            ->findAvailableForZoneAndPeriod(
                zone: $zone,
                startDate: $startDate,
                endDate: $endDate,
                blockingStatuses: $blockingStatuses,
            );

        return new JsonResponse($equipments);
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
