<?php

namespace App\Middleware;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class LocaleSubscriber implements EventSubscriberInterface
{
    private string $defaultLocale;
    private array $supportedLocales;

    public function __construct(#[Autowire(env: 'APP_DEFAULT_LOCALE')] string $defaultLocale, #[Autowire(env: 'APP_SUPPORTED_LOCALES')] string $supportedLocales)
    {
        $this->defaultLocale = $defaultLocale;
        $this->supportedLocales = explode('|', $supportedLocales);
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if (!$event->isMainRequest()) {
            return;
        }

        $requestedLocale = $request->query->get('_locale');

        if (\is_string($requestedLocale) && \in_array($requestedLocale, $this->supportedLocales, true)) {
            $request->getSession()->set('_locale', $requestedLocale);
            $request->setLocale($requestedLocale);

            return;
        }

        $locale = $request->getSession()->get('_locale') ?? $request->cookies->get('_locale');

        if (\is_string($locale) && \in_array($locale, $this->supportedLocales, true)) {
            $request->setLocale($locale);
        } else {
            $browserLocale = $request->getPreferredLanguage($this->supportedLocales);
            $request->setLocale($browserLocale ?: $this->defaultLocale);
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [['onKernelRequest', 20]],
        ];
    }
}
