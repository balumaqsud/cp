<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PreferenceController extends AbstractController
{
    #[Route('/preferences/locale/{locale}', name: 'app_locale', requirements: ['locale' => 'en|uz'])]
    public function locale(string $locale, Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if ($user instanceof User) {
            $user->setLocale($locale);
            $entityManager->flush();
        }

        $response = $this->redirectToReferer($request);
        $response->headers->setCookie($this->preferenceCookie($request, '_locale', $locale));

        return $response;
    }

    #[Route('/preferences/theme/{theme}', name: 'app_theme', requirements: ['theme' => 'light|dark'])]
    public function theme(string $theme, Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if ($user instanceof User) {
            $user->setTheme($theme);
            $entityManager->flush();
        }

        $response = $this->redirectToReferer($request);
        $response->headers->setCookie($this->preferenceCookie($request, '_theme', $theme));

        return $response;
    }

    private function redirectToReferer(Request $request): Response
    {
        $referer = $request->headers->get('referer');
        if (!\is_string($referer) || $referer === '') {
            return $this->redirectToRoute('app_home');
        }

        $refererHost = parse_url($referer, PHP_URL_HOST);
        if ($refererHost !== $request->getHost()) {
            return $this->redirectToRoute('app_home');
        }

        return $this->redirect($referer);
    }

    private function preferenceCookie(Request $request, string $name, string $value): Cookie
    {
        return Cookie::create($name)
            ->withValue($value)
            ->withExpires(new \DateTimeImmutable('+1 year'))
            ->withPath('/')
            ->withHttpOnly(false)
            ->withSecure($request->isSecure());
    }
}
