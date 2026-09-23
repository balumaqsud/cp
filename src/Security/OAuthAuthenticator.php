<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\User;
use App\Service\OAuthUserService;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

final class OAuthAuthenticator extends OAuth2Authenticator
{
    private const CHECK_ROUTES = [
        'connect_google_check' => 'google',
        'connect_github_check' => 'github',
    ];

    public function __construct(
        private readonly ClientRegistry $clientRegistry,
        private readonly OAuthUserService $oauthUsers,
        private readonly UrlGeneratorInterface $urls,
    ) {
    }

    public function supports(Request $request): ?bool
    {
        $route = $request->attributes->get('_route');

        return \is_string($route) && isset(self::CHECK_ROUTES[$route]);
    }

    public function authenticate(Request $request): Passport
    {
        $route = $request->attributes->get('_route');
        $clientKey = self::CHECK_ROUTES[$route] ?? null;
        if ($clientKey === null) {
            throw new CustomUserMessageAuthenticationException('auth.flash.oauth_provider');
        }

        $client = $this->clientRegistry->getClient($clientKey);
        $accessToken = $this->fetchAccessToken($client);

        return new SelfValidatingPassport(
            new UserBadge($accessToken->getToken(), function () use ($client, $accessToken): User {
                /** @var ResourceOwnerInterface $oauthUser */
                $oauthUser = $client->fetchUserFromToken($accessToken);

                return $this->oauthUsers->findOrCreateFromOAuth(
                    $this->emailFromOwner($oauthUser),
                    $this->nameFromOwner($oauthUser),
                );
            }),
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $user = $token->getUser();
        if ($user instanceof User && !$user->hasAssignedRole()) {
            return new RedirectResponse($this->urls->generate('app_choose_role'));
        }

        return new RedirectResponse($this->urls->generate('app_home'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $message = $exception->getMessageKey();
        if ($exception->getMessageData() !== []) {
            $message = strtr($message, $exception->getMessageData());
        }

        $request->getSession()->getFlashBag()->add('danger', $message);

        return new RedirectResponse($this->urls->generate('app_login'));
    }

    private function emailFromOwner(ResourceOwnerInterface $owner): string
    {
        if (method_exists($owner, 'getEmail')) {
            return (string) $owner->getEmail();
        }

        $data = $owner->toArray();

        return (string) ($data['email'] ?? '');
    }

    private function nameFromOwner(ResourceOwnerInterface $owner): string
    {
        if (method_exists($owner, 'getName')) {
            $name = trim((string) $owner->getName());
            if ($name !== '') {
                return $name;
            }
        }

        if (method_exists($owner, 'getNickname')) {
            $nickname = trim((string) $owner->getNickname());
            if ($nickname !== '') {
                return $nickname;
            }
        }

        return '';
    }
}
