<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Security\Voter\UserVoter;
use App\Service\UserAdminService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AdminController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly UserAdminService $userAdmin,
    ) {
    }

    #[Route('/admin/users', name: 'app_admin_users', methods: ['GET'])]
    public function users(): Response
    {
        $this->denyAccessUnlessGranted(UserVoter::MANAGE);

        return $this->render('admin/users.html.twig', [
            'users' => $this->users->findAllOrdered(),
            'roles' => UserAdminService::ROLES,
        ]);
    }

    #[Route('/admin/users/bulk', name: 'app_admin_users_bulk', methods: ['POST'])]
    public function bulk(Request $request): Response
    {
        $this->denyAccessUnlessGranted(UserVoter::MANAGE);
        $actor = $this->getUser();
        if (!$actor instanceof User) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid('admin_users', $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $selected = $this->users->findByIds($this->selectedIds($request));
        if ($selected === []) {
            $this->addFlash('danger', 'user.flash.select_one');

            return $this->redirectToRoute('app_admin_users');
        }

        $action = $request->request->getString('toolbar_action');
        $role = $request->request->getString('role');

        try {
            $result = match ($action) {
                'block' => $this->userAdmin->applyBlock($selected, $actor, true),
                'unblock' => $this->userAdmin->applyBlock($selected, $actor, false),
                'assign' => $this->userAdmin->applyRoles($selected, $role, true),
                'remove_role' => $this->userAdmin->applyRoles($selected, $role, false),
                'delete' => $this->userAdmin->deleteSelected($selected, $actor),
                default => null,
            };
        } catch (\InvalidArgumentException $exception) {
            $this->addFlash('danger', $exception->getMessage());

            return $this->redirectToRoute('app_admin_users');
        }

        if ($result === null) {
            return $this->redirectToRoute('app_admin_users');
        }

        if ($result['skippedSelf']) {
            $this->addFlash('danger', 'user.flash.cannot_self');
        }

        if ($result['inUse']) {
            $this->addFlash('danger', 'user.flash.in_use');

            return $this->redirectToRoute('app_admin_users');
        }

        if ($result['applied'] > 0) {
            $this->addFlash('success', match ($action) {
                'block' => 'user.flash.blocked',
                'unblock' => 'user.flash.unblocked',
                'assign' => 'user.flash.role_assigned',
                'remove_role' => 'user.flash.role_removed',
                'delete' => 'user.flash.deleted',
                default => 'user.flash.deleted',
            });
        }

        return $this->redirectToRoute('app_admin_users');
    }

    /**
     * @return list<int>
     */
    private function selectedIds(Request $request): array
    {
        $ids = array_map(static fn (mixed $id): int => (int) $id, (array) $request->request->all('ids'));

        return array_values(array_filter($ids, static fn (int $id): bool => $id > 0));
    }
}
