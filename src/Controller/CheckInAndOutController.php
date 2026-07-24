<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CheckInAndOutController extends AbstractController
{
    #[Route('/check/in/out', name: 'app_check_in_out')]
    public function index(): Response
    {
        return $this->render('check_in&out/index.html.twig', [
            'controller_name' => 'CheckIn&OutController',
        ]);
    }
}
