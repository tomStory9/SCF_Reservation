<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class UserController extends AbstractController
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    #[Route('/user', name: 'app_home')]
    public function index(): Response
    {
        $user = $this->getUser();
        $bookings = $this->entityManager->getRepository(User::class)->findBy(['userBooking' => $user]);

        return
        $this->render('user/index.html.twig', [
            '$user' => $user,
            'bookings' => $bookings,
        ]);
    }
}
