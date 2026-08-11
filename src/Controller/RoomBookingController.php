<?php

namespace App\Controller;

use App\Repository\SettingsRepository;
use App\Service\RoomBookingService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class RoomBookingController extends AbstractController
{
    public function __construct(
        private readonly RoomBookingService $roomBookingService,
        private readonly SettingsRepository $settingsRepository,
    ) {
    }

    #[IsGranted('ROLE_USER')]
    #[Route('/room/booking', name: 'app_room_booking')]
    public function roomBooking(): Response
    {
        $setting = $this->settingsRepository->getSettings();

        if (!$setting->isRoomBookingEnabled()) {
            return $this->redirectToRoute('app_home_user');
        }

        $rooms = $this->roomBookingService->getBedRoomYamaichiWithPricing();

        return $this->render('room_booking/index.html.twig', [
            'rooms' => $rooms,
        ]);
    }
}
