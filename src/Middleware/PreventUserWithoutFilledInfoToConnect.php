<?php

namespace App\Middleware;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class PreventUserWithoutFilledInfoToConnect
{
    public function __construct(
        private readonly Security $security,
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }
        // if route his /register/information, we don't want to redirect the user
        if ('/register/information' === $event->getRequest()->getPathInfo()) {
            return;
        }

        $user = $this->security->getUser();

        if ($user && !$user->isFilledInfo()) {
            $event->setResponse(
                new RedirectResponse('/register/information')
            );
        }
    }
}
