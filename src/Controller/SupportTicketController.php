<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\SupportTicketDTO;
use App\Entity\User;
use App\Form\SupportTicketType;
use App\Service\SupportTicketContext;
use App\Service\SupportTicketService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SupportTicketController extends AbstractController
{
    public function __construct(
        private readonly SupportTicketService $tickets,
        private readonly SupportTicketContext $context,
    ) {
    }

    #[Route('/support/ticket', name: 'app_support_ticket', methods: ['GET', 'POST'])]
    public function ticket(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $origin = $request->getSchemeAndHttpHost();
        $ticket = new SupportTicketDTO();
        $form = $this->createForm(SupportTicketType::class, $ticket);
        $form->get('from')->setData($this->context->link($request->query->getString('from'), $origin));
        $form->handleRequest($request);

        $from = $this->sanitizedFrom($form, $request, $origin);

        if ($form->isSubmitted() && $form->isValid()) {
            return $this->submit($this->requireUser(), $ticket, $from, $origin, $form);
        }

        return $this->renderTicket($form, $from);
    }

    private function submit(User $user, SupportTicketDTO $ticket, string $from, string $origin, FormInterface $form): Response
    {
        try {
            $this->tickets->submit($user, $ticket, $from, $origin);
        } catch (\RuntimeException $exception) {
            $this->addFlash('danger', $this->flashKey($exception));

            return $this->renderTicket($form, $from, Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->addFlash('success', 'support.flash.created');

        return new RedirectResponse($from);
    }

    private function sanitizedFrom(FormInterface $form, Request $request, string $origin): string
    {
        $raw = $form->isSubmitted()
            ? (string) $form->get('from')->getData()
            : $request->query->getString('from');

        return $this->context->link($raw, $origin);
    }

    private function flashKey(\RuntimeException $exception): string
    {
        return match ($exception->getMessage()) {
            'not_configured' => 'support.flash.not_configured',
            'no_admins' => 'support.flash.no_admins',
            default => 'support.flash.failed',
        };
    }

    private function renderTicket(FormInterface $form, string $from, int $status = Response::HTTP_OK): Response
    {
        return $this->render('support/ticket.html.twig', [
            'form' => $form,
            'returnLink' => $from,
        ], new Response(status: $status));
    }

    private function requireUser(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        return $user;
    }
}
