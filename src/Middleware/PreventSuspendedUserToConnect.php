<?php

namespace App\Middleware;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class PreventSuspendedUserToConnect
{
    public function __construct(
        private readonly Security $security,
        private readonly RouterInterface $router,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $route = $request->attributes->get('_route');

        if (\in_array($route, ['app_login', 'app_logout'], true)) {
            return;
        }

        $user = $this->security->getUser();

        if (!$user instanceof User) {
            return;
        }

        if ('suspended' !== $user->getUserStatus()->value) {
            return;
        }

        $this->security->logout(false);

        $event->setResponse(
            new RedirectResponse($this->router->generate('app_login'))
        );
    }
}
