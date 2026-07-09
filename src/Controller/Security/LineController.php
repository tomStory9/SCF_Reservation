<?php

namespace App\Controller\Security;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class LineController extends AbstractController
{
    #[Route('/connect/line', name: 'connect_line')]
        public function connectAction(ClientRegistry $clientRegistry)
    {
        return $clientRegistry
            ->getClient('line') 
            ->redirect([
               'openid',
                'email',
                'profile',
            ]);
    }
    #[Route('/connect/line/check',name:'connect_line_check')]
        public function connectCheckAction(Request $request, ClientRegistry $clientRegistry)
    {
       
    }
}
