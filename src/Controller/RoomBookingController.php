<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\SettingsRepository;
use App\Service\BookingService;
use App\Service\RoomBookingService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

final class RoomBookingController extends AbstractController
{
    public function __construct(
        private readonly RoomBookingService $roomBookingService,
        private readonly TranslatorInterface $translator,
        private readonly BookingService $bookingService,
        private readonly SettingsRepository $settingsRepository
    ) {
    }

    #[IsGranted('ROLE_USER')]
    #[Route('/room/booking', name: 'app_room_booking')]
    public function roomBooking(): Response
    {
        if (!$this->isGranted('FEATURE_ROOM_BOOKING')) {
            return $this->redirectToRoute('app_home_user');
        }

        $rooms = $this->roomBookingService->getBedRoomYamaichiWithPricing();

        $blockedPeriods = $this->bookingService->getUnavailiblePeriods();
        $minDays = $this->settingsRepository->getSettings()->getMinDayRoomBooking();

        return $this->render('room_booking/index.html.twig', [
            'rooms' => $rooms,
            'blockedPeriods' => $blockedPeriods,
            'minDays' => $minDays,
        ]);
    }

    /**
     * @throws \DateMalformedStringException
     */
    #[IsGranted('ROLE_USER')]
    #[Route('/room/booking/create', name: 'app_room_booking_create', methods: ['GET', 'POST'])]
    public function createRoomBooking(Request $request): JsonResponse
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
            $this->roomBookingService->createRoomBooking($data, $user);
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
}
