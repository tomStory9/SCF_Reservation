<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

final class RegisterController extends AbstractController
{   #[Route('/register', name: 'app_register')]
    public function index(AuthenticationUtils $authenticationUtils): Response
    {
     // if the user is already logged in, redirect to home page
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        // last error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last email typed by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/register/information', name: 'app_register_information')]
    public function info(): Response
    {   
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED');
        $user = $this->getUser();
        if ($user->isFilledInfo() === true) {
            return $this->redirectToRoute('app_home');
        }

        $form = $this->createForm(UserType::class, $user);
        
        if ($form->isSubmitted() && $form->isValid()) {

            $user->setFilledInfo(true);
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute('app_home');
        }
        
        return $this->render('register/form.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}