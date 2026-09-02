<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\BookingRepository;
use App\Repository\FacilityRepository;
use App\Repository\SettingsRepository;
use App\Repository\TimeSlotRepository;
use App\Repository\UserRoleRepository;
use App\Service\BookingService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

final class BookingController extends AbstractController
{
    public function __construct(
        private readonly FacilityRepository $facilityRepository,
        private readonly BookingService $bookingService,
        private readonly UserRoleRepository $userRoleRepository,
        private readonly BookingRepository $bookingRepository,
        private readonly TranslatorInterface $translator,
        private readonly SettingsRepository $settingsRepository,
        private readonly TimeSlotRepository $timeSlotRepository
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
            return new JsonResponse(['success' => false, 'error' => $this->translator->trans('api.error.user_not_authenticated')], Response::HTTP_BAD_REQUEST);
        }

        $remainingHours = $this->bookingRepository->getRemainingFreeHoursThisMonth($user);

        $userRole = $this->userRoleRepository->findRoleForUser($user);
        $maxAdvanceDays = $userRole && null !== $userRole->getMaxAdvanceBookingDays() ? $userRole->getMaxAdvanceBookingDays() : 30;

        $maxEndDate = new \DateTimeImmutable('today')->modify(sprintf('+%d days', $maxAdvanceDays + 1))->format('Y-m-d');

        $facilities = $this->facilityRepository->findAll();

        $blockedPeriods = $this->bookingService->getUnavailiblePeriods();

        $minDays = $this->settingsRepository->getSettings()->getMinDayBooking();

        $periods = $this->timeSlotRepository->findAllPeriod();
        $calendarPeriods = [];
        foreach ($periods as $periodData) {
            $period = $periodData['period'];

            $calendarPeriods[$period->value] = [
                'start' => $periodData['startTime']->format('H:i'),
                'end' => $periodData['endTime']->format('H:i'),
            ];
        }

        return $this->render('user/reservation.html.twig', [
            'user' => $user,
            'facilities' => $facilities,
            'maxEndDate' => $maxEndDate,
            'remainingHours' => $remainingHours,
            'blockedPeriods' => $blockedPeriods,
            'minDays' => $minDays,
            'periods' => $calendarPeriods,
        ]);
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
            return new JsonResponse(['success' => false, 'error' => $this->translator->trans('api.error.user_not_authenticated')], Response::HTTP_BAD_REQUEST);
        }

        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return new JsonResponse(['success' => false, 'error' => $this->translator->trans('api.error.invalid_data')], Response::HTTP_BAD_REQUEST);
        }

        if (true !== ($data['termsAccepted'] ?? false)) {
            return new JsonResponse([
                'success' => false,
                'error' => $this->translator->trans('api.error.terms_consent_required'),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $this->bookingService->createBooking($data, $user);
        } catch (\Exception $exception) {
            return new JsonResponse([
                'success' => false,
                'error' => $exception->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        }
        $this->addFlash(
            'success',
            $this->translator->trans('flash.booking_created')
        );

        return new JsonResponse([
            'success' => true,
            'redirectUrl' => $this->generateUrl('app_home_user'),
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route(
        '/booking/{bookingId}/approve',
        name: 'app_booking_approve',
        methods: ['POST']
    )]
    public function approveBooking(int $bookingId): JsonResponse
    {
        $booking = $this->bookingRepository->find($bookingId);

        if (!$booking) {
            return new JsonResponse([
                'success' => false,
                'error' => $this->translator->trans('admin.flash.booking_not_found'),
            ], Response::HTTP_NOT_FOUND);
        }

        $this->bookingService->approveBooking($booking);

        return new JsonResponse([
            'success' => true,
            'message' => $this->translator->trans('admin.flash.booking_approved'),
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route(
        '/booking/{bookingId}/decline',
        name: 'app_booking_decline',
        methods: ['POST']
    )]
    public function declineBooking(int $bookingId): JsonResponse
    {
        $booking = $this->bookingRepository->find($bookingId);

        if (!$booking) {
            return new JsonResponse([
                'success' => false,
                'error' => $this->translator->trans('admin.flash.booking_not_found'),
            ], Response::HTTP_NOT_FOUND);
        }

        $this->bookingService->declineBooking($booking);

        return new JsonResponse([
            'success' => true,
            'message' => $this->translator->trans('admin.flash.booking_declined'),
        ]);
    }
}
