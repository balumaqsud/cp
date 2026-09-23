<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\EventSubscriber\UserPreferenceSubscriber;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;

final class OAuthUserService
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly EntityManagerInterface $entityManager,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function findOrCreateFromOAuth(string $email, string $name): User
    {
        $email = mb_strtolower(trim($email));
        if ($email === '') {
            throw new CustomUserMessageAuthenticationException('auth.flash.oauth_email');
        }

        $user = $this->users->findOneByEmail($email);
        if ($user !== null) {
            if ($user->isBlocked()) {
                throw new CustomUserMessageAccountStatusException('auth.flash.blocked');
            }

            return $user;
        }

        $user = new User();
        $user->setEmail($email);
        $user->setName($name !== '' ? $name : $email);
        $user->setPassword(null);
        $user->setRoles([]);
        $this->applyGuestPreferences($user);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    private function applyGuestPreferences(User $user): void
    {
        $request = $this->requestStack->getCurrentRequest();
        if ($request === null) {
            return;
        }

        UserPreferenceSubscriber::copyFromCookies($user, $request);
    }
}
