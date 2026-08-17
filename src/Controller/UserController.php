<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class UserController extends AbstractController
{
    public function __construct()
    {
    }

    #[IsGranted('ROLE_USER')]
    #[Route('/user', name: 'app_home_user')]
    public function index(): Response
    {
        $user = $this->getUser();

        return $this->render('user/index.html.twig', [
            'user' => $user,
            'bookings' => $user->getBookings(),
        ]);
    }

    #[IsGranted('ROLE_USER')]
    #[Route('/profil', name: 'app_user_profile')]
    public function profile(): Response
    {
        $user = $this->getUser();

        return $this->render('user/informations.html.twig', [
            'user' => $user,
        ]);
    }
}
