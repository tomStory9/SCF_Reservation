<?php

namespace App\Controller;

use App\Entity\Facility;
use App\Entity\User;
use App\Entity\Zone;
use App\Repository\BookingRepository;
use App\Repository\FacilityRepository;
use App\Repository\UserRoleRepository;
use App\Repository\ZoneRepository;
use App\Service\BookingService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class BookingController extends AbstractController
{
    public function __construct(
        private readonly FacilityRepository $facilityRepository,
        private readonly ZoneRepository $zoneRepository,
        private readonly BookingService $bookingService,
        private readonly UserRoleRepository $userRoleRepository,
        private readonly BookingRepository $bookingRepository,
    ) {
    }

    /**
     * @throws \DateMalformedStringException
     */
    #[IsGranted('ROLE_USER')]
    #[Route('/booking', name: 'app_booking_index', methods: ['GET'])]
    public function index(): Response
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return new JsonResponse(['success' => false, 'error' => 'Utilisateur non connecté'], Response::HTTP_BAD_REQUEST);
        }

        $remainingHours = $this->bookingRepository->getRemainingFreeHoursThisMonth($user);

        $userRole = $this->userRoleRepository->findRoleForUser($user);
        $maxAdvanceDays = $userRole && null !== $userRole->getMaxAdvanceBookingDays() ? $userRole->getMaxAdvanceBookingDays() : 30;

        $maxEndDate = new \DateTimeImmutable('today')->modify(sprintf('+%d days', $maxAdvanceDays + 1))->format('Y-m-d');

        $facilities = $this->facilityRepository->findAll();

        return $this->render('user/reservation.html.twig', [
            'user' => $user,
            'facilities' => $facilities,
            'maxEndDate' => $maxEndDate,
            'remainingHours' => $remainingHours,
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

    #[IsGranted('ROLE_USER')]
    #[Route('/zone/{id}/pricings', name: 'app_booking_pricings_by_zone', methods: ['GET'])]
    public function getPricingsByZone(Zone $zone): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return new JsonResponse(['success' => false, 'error' => 'Utilisateur non connecté'], Response::HTTP_BAD_REQUEST);
        }

        $pricingsData = $this->bookingService->getPrincingsByZone($zone, $user);

        return new JsonResponse($pricingsData);
    }

    /**
     * @throws \DateMalformedStringException
     */
    #[IsGranted('ROLE_USER')]
    #[Route('/booking/create', name: 'app_booking_create', methods: ['GET', 'POST'])]
    public function createBooking(Request $request): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return new JsonResponse(['success' => false, 'error' => 'Utilisateur non connecté'], Response::HTTP_BAD_REQUEST);
        }

        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return new JsonResponse(['success' => false, 'error' => 'Données invalides.'], Response::HTTP_BAD_REQUEST);
        }

        $this->bookingService->createBooking($data, $user);

        $this->addFlash(
            'success',
            'Votre réservation a bien été enregistrée et est en attente de validation.'
        );

        return new JsonResponse([
            'success' => true,
            'redirectUrl' => $this->generateUrl('app_home_user'),
        ]);
    }
}
