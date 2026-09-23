<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\LoginDTO;
use App\DTO\RegistrationDTO;
use App\Entity\User;
use App\Exception\AuthException;
use App\Form\ChooseRoleType;
use App\Form\LoginType;
use App\Form\RegistrationType;
use App\Service\SecurityService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

class SecurityController extends AbstractController
{
    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(Request $request, SecurityService $securityService, TranslatorInterface $translator): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        $registrationDTO = new RegistrationDTO();
        $form = $this->createForm(RegistrationType::class, $registrationDTO);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $securityService->register($registrationDTO);

                return $this->redirectToRoute('app_login');
            } catch (AuthException $e) {
                $form->get('email')->addError(new FormError($translator->trans($e->getMessage())));
            }
        }

        return $this->render('security/register.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/login', name: 'app_login', methods: ['GET', 'POST'])]
    public function login(Request $request, SecurityService $securityService, Security $security, TranslatorInterface $translator): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        $loginDTO = new LoginDTO();
        $form = $this->createForm(LoginType::class, $loginDTO);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $user = $securityService->login($loginDTO);
                $security->login($user, 'form_login', 'main');

                return $this->redirectToRoute('app_home');
            } catch (AuthException $e) {
                $form->addError(new FormError($translator->trans($e->getMessage())));
            }
        }

        return $this->render('security/login.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/choose-role', name: 'app_choose_role', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function chooseRole(Request $request, EntityManagerInterface $entityManager, Security $security): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        if ($user->hasAssignedRole()) {
            return $this->redirectToRoute('app_home');
        }

        $form = $this->createForm(ChooseRoleType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var string $role */
            $role = $form->get('role')->getData();
            $user->setRoles([$role]);
            $entityManager->flush();
            $security->login($user, 'form_login', 'main');

            return $this->redirectToRoute('app_home');
        }

        return $this->render('security/choose_role.html.twig', [
            'form' => $form,
        ]);
    }
}
