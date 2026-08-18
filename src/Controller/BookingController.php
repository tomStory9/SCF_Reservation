<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\BookingRepository;
use App\Repository\FacilityRepository;
use App\Repository\UserRoleRepository;
use App\Service\BookingService;
use App\Service\MailerService;
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
        private readonly MailerService $mailerService,
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

        return $this->render('user/reservation.html.twig', [
            'user' => $user,
            'facilities' => $facilities,
            'maxEndDate' => $maxEndDate,
            'remainingHours' => $remainingHours,
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
    #[Route('/booking/{bookingId}/approve', name: 'app_booking_approve')]
    public function approveBooking(Request $request, int $bookingId): JsonResponse
    {
        $user = $this->getUser();

        $booking = $this->bookingRepository->find($bookingId);
        $this->bookingService->approveBooking($booking);

        $this->addFlash(
            'success',
            'La réservation a bien été validée.'
        );

        return new JsonResponse([
            'success' => true,
            'redirectUrl' => $this->generateUrl('app_home_user'),
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/booking/{bookingId}/decline', name: 'app_booking_decline')]
    public function declinebooking(Request $request, int $bookingId): JsonResponse
    {
        $user = $this->getUser();

        $booking = $this->bookingRepository->find($bookingId);
        $this->bookingService->declineBooking($booking);

        $this->addFlash(
            'warning',
            'La réservation a bien été decliné.'
        );

        return new JsonResponse([
            'success' => true,
            'redirectUrl' => $this->generateUrl('app_home_user'),
        ]);
    }
}
