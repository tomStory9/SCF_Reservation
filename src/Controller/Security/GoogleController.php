<?php

namespace App\Controller\Security;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;


final class GoogleController extends AbstractController
{
    #[Route('/connect/google', name: 'app_google')]
        public function connectAction(ClientRegistry $clientRegistry)
    {
        return $clientRegistry
            ->getClient('google') 
            ->redirect([
               'openid',
                'email',
                'profile',
            ]);
    }
    #[Route('/connect/google/check',name:'connect_google_check')]
        public function connectCheckAction(Request $request, ClientRegistry $clientRegistry)
    {
       
    }


}
