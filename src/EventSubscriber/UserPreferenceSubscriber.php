<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class UserPreferenceSubscriber
{
    public const LOCALES = ['en', 'uz'];
    public const THEMES = ['light', 'dark'];

    public function __construct(
        private readonly Security $security,
    ) {
    }

    #[AsEventListener(event: KernelEvents::REQUEST, priority: 7)]
    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $user = $this->security->getUser();

        $locale = (string) $request->cookies->get('_locale', 'en');
        $theme = (string) $request->cookies->get('_theme', 'light');

        if ($user instanceof User) {
            $locale = $user->getLocale();
            $theme = $user->getTheme();
        }

        if (!\in_array($locale, self::LOCALES, true)) {
            $locale = 'en';
        }
        if (!\in_array($theme, self::THEMES, true)) {
            $theme = 'light';
        }

        $request->setLocale($locale);
        $request->attributes->set('_theme', $theme);
    }
}
