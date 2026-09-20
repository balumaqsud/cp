<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\LoginDTO;
use App\DTO\RegistrationDTO;
use App\Form\LoginType;
use App\Form\RegistrationType;
use App\Service\SecurityService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SecurityController extends AbstractController
{
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
            { $form->get('email')->addError( new FormError($e->getMessage()) );
            }}
        return $this->render('security/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/login', name: 'app_login', methods: ['GET', 'POST'])]
    public function login(Request $request, SecurityService $securityService, Security $security): Response
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
            } catch (\Exception $e) {
                $form->addError(new FormError($e->getMessage()));
            }
        }

        return $this->render('security/login.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
