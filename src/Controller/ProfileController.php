<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Security\Voter\ProfileVoter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'app_profile', methods: ['GET'])]
    public function show(): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $this->denyAccessUnlessGranted(ProfileVoter::VIEW, $user);

        return $this->render('profile/show.html.twig', [
            'profileUser' => $user,
        ]);
    }
}
