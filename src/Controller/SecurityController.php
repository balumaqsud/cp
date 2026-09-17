<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\RegistrationDTO;
use App\Form\RegistrationType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\SecurityService;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController

{

    public function __construct(
        private readonly SecurityService $securityService,
        private readonly AuthenticationUtils $authenticationUtils,
    ) {}

    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(Request $request, SecurityService $securityService): Response {
        if($this->getUser()){
            return $this->redirectToRoute('app_home');
        }
        $registrationDTO = new RegistrationDTO();
        $form = $this->createForm(RegistrationType::class, $registrationDTO);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid())
            { try { $securityService->register($registrationDTO);
            return $this->redirectToRoute('app_login');
            }
            catch (\InvalidArgumentException $e)
            { $form->get('email')->addError( new \Symfony\Component\Form\FormError($e->getMessage()) );
            }}
        return $this->render('security/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/login', name: 'app_login', methods: ['GET', 'POST'])]
    public function login(Request $request, SecurityService $securityService): Response {
        if($this->getUser()){
            return $this->redirectToRoute('app_home');
        }
        return $this->render('security/login.html.twig', [
            'last_username' => $this->authenticationUtils->getLastUsername(),
            'error' => $this->authenticationUtils->getLastAuthenticationError(),
        ]);
    }
}
