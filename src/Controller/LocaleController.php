<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class LocaleController extends AbstractController
{
    #[Route('/switch-locale/{locale}', name: 'app_switch_locale')]
    public function switchLocale(
        string $locale,
        Request $request,
        #[Autowire(env: 'APP_SUPPORTED_LOCALES')]
        string $supportedLocales,
        #[Autowire(env: 'APP_DEFAULT_LOCALE')]
        string $defaultLocale
    ): Response {
        $supportedLocales = explode('|', $supportedLocales);
        if (!in_array($locale, $supportedLocales, true)) {
            $locale = $defaultLocale;
        }

        $referer = $request->headers->get('referer');

        $response = $this->redirect(
            $referer ?: $this->generateUrl('app_home')
        );

        $response->headers->setCookie(
            new Cookie(
                '_locale',
                $locale,
                time() + (365 * 24 * 60 * 60),
                '/',
                null,
                false,
                true,
                false,
                Cookie::SAMESITE_LAX
            )
        );

        $request->getSession()->set('_locale', $locale);

        return $response;
    }
}
