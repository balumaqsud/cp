<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Security\Voter\UserVoter;
use App\Service\UserAdminService;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
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

        $role = $request->request->getString('role');

        try {
            match ($request->request->getString('toolbar_action')) {
                'block' => $this->applyBlock($selected, $actor, true),
                'unblock' => $this->applyBlock($selected, $actor, false),
                'assign' => $this->applyRole($selected, $role, true),
                'remove_role' => $this->applyRole($selected, $role, false),
                'delete' => $this->deleteSelected($selected, $actor),
                default => null,
            };
        } catch (\InvalidArgumentException $exception) {
            $this->addFlash('danger', $exception->getMessage());

            return $this->redirectToRoute('app_admin_users');
        } catch (ForeignKeyConstraintViolationException) {
            $this->addFlash('danger', 'user.flash.in_use');

            return $this->redirectToRoute('app_admin_users');
        }

        return $this->redirectToRoute('app_admin_users');
    }

    /**
     * @param list<User> $users
     */
    private function applyBlock(array $users, User $actor, bool $blocked): void
    {
        $applied = $this->applySkippingSelf($users, $actor, function (User $user) use ($actor, $blocked): void {
            $this->userAdmin->setBlocked($user, $actor, $blocked);
        });

        if ($applied > 0) {
            $this->addFlash('success', $blocked ? 'user.flash.blocked' : 'user.flash.unblocked');
        }
    }

    /**
     * @param list<User> $users
     */
    private function applyRole(array $users, string $role, bool $assign): void
    {
        foreach ($users as $user) {
            if ($assign) {
                $this->userAdmin->addRole($user, $role);
            } else {
                $this->userAdmin->removeRole($user, $role);
            }
        }

        $this->addFlash('success', $assign ? 'user.flash.role_assigned' : 'user.flash.role_removed');
    }

    /**
     * @param list<User> $users
     */
    private function deleteSelected(array $users, User $actor): void
    {
        $applied = $this->applySkippingSelf($users, $actor, function (User $user) use ($actor): void {
            $this->userAdmin->delete($user, $actor);
        });

        if ($applied > 0) {
            $this->addFlash('success', 'user.flash.deleted');
        }
    }

    /**
     * @param list<User> $users
     * @param callable(User): void $apply
     */
    private function applySkippingSelf(array $users, User $actor, callable $apply): int
    {
        $applied = 0;
        $skippedSelf = false;
        foreach ($users as $user) {
            try {
                $apply($user);
                ++$applied;
            } catch (\InvalidArgumentException $exception) {
                if ($exception->getMessage() === 'user.flash.cannot_self') {
                    $skippedSelf = true;
                    continue;
                }

                throw $exception;
            }
        }

        if ($skippedSelf) {
            $this->addFlash('danger', 'user.flash.cannot_self');
        }

        return $applied;
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
