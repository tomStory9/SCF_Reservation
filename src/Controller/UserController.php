<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class UserController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    #[Route('/user', name: 'app_home_user')]
    public function index(): Response
    {
        $user = $this->getUser();

        return $this->render('user/index.html.twig', [
            'user' => $user,
            'bookings' => $user->getBookings(),
        ]);
    }

    #[Route('/reservation', name: 'app_reservation')]
    public function reservation(): Response
    {
        $user = $this->getUser();

        return $this->render('user/reservation.html.twig', [
            'user' => $user,
        ]);
    }
}
