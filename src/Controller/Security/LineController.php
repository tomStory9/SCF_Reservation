<?php

namespace App\Controller\Security;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class LineController extends AbstractController
{
    #[Route('/connect/line', name: 'connect_line')]
    public function connectAction(ClientRegistry $clientRegistry)
    {
        return $clientRegistry
            ->getClient('line')
            ->redirect([
                'profile',
                'openid',
                'email'
            ]);
    }

    #[Route('/connect/line/check', name: 'connect_line_check')]
    public function connectCheckAction(Request $request, ClientRegistry $clientRegistry)
    {
    }
}