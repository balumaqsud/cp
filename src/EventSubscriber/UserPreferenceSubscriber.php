<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Contracts\Translation\TranslatorInterface;

final class UserPreferenceSubscriber
{
    public const LOCALES = ['en', 'uz'];
    public const THEMES = ['light', 'dark'];

    public function __construct(
        private readonly Security $security,
        private readonly TranslatorInterface $translator,
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

        $locale = $this->pick(
            $user instanceof User ? $user->getLocale() : null,
            $request->cookies->get('_locale'),
            self::LOCALES,
            'en',
        );
        $theme = $this->pick(
            $user instanceof User ? $user->getTheme() : null,
            $request->cookies->get('_theme'),
            self::THEMES,
            'light',
        );

        $request->setLocale($locale);
        $this->translator->setLocale($locale);
        $request->attributes->set('_theme', $theme);
    }

    public static function copyFromCookies(User $user, Request $request): bool
    {
        $changed = false;

        $locale = $request->cookies->get('_locale');
        if (\is_string($locale) && \in_array($locale, self::LOCALES, true) && $user->getLocale() !== $locale) {
            $user->setLocale($locale);
            $changed = true;
        }

        $theme = $request->cookies->get('_theme');
        if (\is_string($theme) && \in_array($theme, self::THEMES, true) && $user->getTheme() !== $theme) {
            $user->setTheme($theme);
            $changed = true;
        }

        return $changed;
    }

    /**
     * @param list<string> $allowed
     */
    private function pick(mixed $primary, mixed $cookie, array $allowed, string $default): string
    {
        if (\is_string($primary) && \in_array($primary, $allowed, true)) {
            return $primary;
        }

        if (\is_string($cookie) && \in_array($cookie, $allowed, true)) {
            return $cookie;
        }

        return $default;
    }
}
