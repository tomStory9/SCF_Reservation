<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class RoomBookingController extends AbstractController
{
    #[Route('/room/booking', name: 'app_room_booking')]
    public function index(): Response
    {
        return $this->render('room_booking/index.html.twig', [
            'controller_name' => 'RoomBookingController',
        ]);
    }
}
