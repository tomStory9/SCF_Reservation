<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\RoomBookingService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class RoomBookingController extends AbstractController
{
    public function __construct(
        private readonly RoomBookingService $roomBookingService,
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

        return $this->render('room_booking/index.html.twig', [
            'rooms' => $rooms,
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
            return new JsonResponse(['success' => false, 'error' => 'Utilisateur non connecté'], Response::HTTP_BAD_REQUEST);
        }

        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return new JsonResponse(['success' => false, 'error' => 'Données invalides.'], Response::HTTP_BAD_REQUEST);
        }

        $this->roomBookingService->createRoomBooking($data, $user);

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
