<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\SalesforceCrmDTO;
use App\Entity\Project;
use App\Entity\User;
use App\Form\ProjectType;
use App\Form\SalesforceCrmType;
use App\Repository\ProjectRepository;
use App\Repository\UserRepository;
use App\Security\Voter\ProfileVoter;
use App\Service\CvService;
use App\Service\ProfileValueService;
use App\Service\ProfileViewService;
use App\Service\ProjectService;
use App\Service\RecentAttributeStore;
use App\Service\SalesforceCrmService;
use Doctrine\ORM\OptimisticLockException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ProfileController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly ProjectRepository $projects,
        private readonly ProfileValueService $profileValues,
        private readonly ProfileViewService $profileView,
        private readonly ProjectService $projectService,
        private readonly CvService $cvService,
        private readonly RecentAttributeStore $recentAttributes,
        private readonly SalesforceCrmService $salesforceCrm,
    ) {
    }

    #[Route('/profile', name: 'app_profile', methods: ['GET'])]
    public function me(Request $request): Response
    {
        return $this->renderProfile($this->requireCurrentUser(), $request->query->getString('tab'));
    }

    #[Route('/profile/{id}', name: 'app_profile_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Request $request, int $id): Response
    {
        return $this->renderProfile($this->requireProfileUser($id), $request->query->getString('tab'));
    }

    #[Route('/profile/{id}/values', name: 'app_profile_values', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function saveValues(Request $request, int $id): Response
    {
        $profileUser = $this->requireProfileUser($id);
        $this->denyAccessUnlessGranted(ProfileVoter::EDIT, $profileUser);

        if (!$this->isCsrfTokenValid('profile_values', $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException();
        }

        try {
            $versions = $this->profileValues->saveSubmitted($profileUser, $request->request->all('values'));
        } catch (OptimisticLockException) {
            $this->addFlash('danger', 'profile.flash.conflict');

            if ($this->wantsJson($request)) {
                return $this->json(['ok' => false, 'conflict' => true], Response::HTTP_CONFLICT);
            }

            return $this->redirectToRoute('app_profile_show', [
                'id' => $profileUser->getId(),
                'tab' => $this->safeTab($request->request->getString('tab')),
            ]);
        }

        if ($this->wantsJson($request)) {
            return $this->json(['ok' => true, 'versions' => $versions]);
        }

        $this->addFlash('success', 'profile.flash.saved');

        return $this->redirectToRoute('app_profile_show', [
            'id' => $profileUser->getId(),
            'tab' => $this->safeTab($request->request->getString('tab')),
        ]);
    }

    #[Route('/profile/{id}/info', name: 'app_profile_info', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function info(Request $request, int $id): Response
    {
        $profileUser = $this->requireProfileUser($id);
        $this->denyAccessUnlessGranted(ProfileVoter::EDIT, $profileUser);

        if (!$this->isCsrfTokenValid('profile_info', $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException();
        }

        return match ($request->request->getString('toolbar_action')) {
            'add' => $this->addLibraryAttribute($request, $profileUser),
            'remove' => $this->removeLibraryAttributes($request, $profileUser),
            default => $this->redirectToInfo($profileUser),
        };
    }

    #[Route('/profile/{id}/crm', name: 'app_profile_crm', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function crm(Request $request, int $id): Response
    {
        $profileUser = $this->requireProfileUser($id);
        $this->denyAccessUnlessGranted(ProfileVoter::EDIT, $profileUser);

        $crm = new SalesforceCrmDTO();
        $form = $this->createForm(SalesforceCrmType::class, $crm);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->salesforceCrm->push($profileUser, $crm);
            } catch (\RuntimeException $exception) {
                $this->addFlash('danger', $exception->getMessage() === 'not_configured'
                    ? 'crm.flash.not_configured'
                    : 'crm.flash.failed');

                return $this->renderCrm($profileUser, $form);
            }

            $this->addFlash('success', 'crm.flash.created');

            return $this->redirectToRoute('app_profile_show', ['id' => $profileUser->getId()]);
        }

        return $this->renderCrm($profileUser, $form);
    }

    #[Route('/profile/{id}/projects/new', name: 'app_profile_project_new', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function newProject(Request $request, int $id): Response
    {
        $profileUser = $this->requireProfileUser($id);
        $this->denyAccessUnlessGranted(ProfileVoter::EDIT, $profileUser);

        $project = new Project();
        $project->setOwner($profileUser);

        return $this->handleProjectForm($request, $profileUser, $project);
    }

    #[Route('/profile/{id}/projects/{projectId}/edit', name: 'app_profile_project_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+', 'projectId' => '\d+'])]
    public function editProject(Request $request, int $id, int $projectId): Response
    {
        $profileUser = $this->requireProfileUser($id);
        $this->denyAccessUnlessGranted(ProfileVoter::EDIT, $profileUser);

        return $this->handleProjectForm($request, $profileUser, $this->requireOwnedProject($profileUser, $projectId));
    }

    #[Route('/profile/{id}/projects/bulk', name: 'app_profile_project_bulk', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function projectBulk(Request $request, int $id): Response
    {
        $profileUser = $this->requireProfileUser($id);
        $this->denyAccessUnlessGranted(ProfileVoter::EDIT, $profileUser);

        if (!$this->isCsrfTokenValid('profile_projects', $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $ids = $this->selectedIds($request);

        return match ($request->request->getString('toolbar_action')) {
            'edit' => $this->redirectToSingleProjectEdit($profileUser, $ids),
            'delete' => $this->deleteProjects($profileUser, $ids),
            default => $this->redirectToProjects($profileUser),
        };
    }

    #[Route('/profile/{id}/cvs/bulk', name: 'app_profile_cv_bulk', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function cvBulk(Request $request, int $id): Response
    {
        $profileUser = $this->requireProfileUser($id);
        $this->denyAccessUnlessGranted(ProfileVoter::EDIT, $profileUser);

        if (!$this->isCsrfTokenValid('profile_cvs', $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException();
        }

        if ($request->request->getString('toolbar_action') !== 'delete') {
            return $this->redirectToCvs($profileUser);
        }

        if ($this->cvService->deleteForUser($profileUser, $this->selectedIds($request)) === 0) {
            $this->addFlash('danger', 'profile.flash.select_one');

            return $this->redirectToCvs($profileUser);
        }

        $this->addFlash('success', 'profile.flash.cvs_deleted');

        return $this->redirectToCvs($profileUser);
    }

    private function renderProfile(User $profileUser, string $tab): Response
    {
        $canViewFull = $this->isGranted(ProfileVoter::VIEW, $profileUser);
        $isPublicView = !$canViewFull && $this->isGranted(ProfileVoter::PUBLIC, $profileUser);
        if (!$canViewFull && !$isPublicView) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('profile/show.html.twig', $this->profileView->build(
            $profileUser,
            $this->requireCurrentUser(),
            $isPublicView,
            $this->isGranted(ProfileVoter::EDIT, $profileUser),
            $this->safeTab($tab),
        ));
    }

    private function renderCrm(User $profileUser, FormInterface $form): Response
    {
        return $this->render('profile/crm.html.twig', [
            'form' => $form,
            'profileUser' => $profileUser,
            ...$this->profileView->crmFields($profileUser),
        ]);
    }

    private function handleProjectForm(Request $request, User $profileUser, Project $project): Response
    {
        $form = $this->createForm(ProjectType::class, $project);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $isNew = $project->getId() === null;
            $this->projectService->save($project);
            $this->addFlash('success', $isNew ? 'profile.flash.project_created' : 'profile.flash.project_updated');

            return $this->redirectToProjects($profileUser);
        }

        return $this->render('profile/project_form.html.twig', [
            'form' => $form,
            'project' => $project,
            'profileUser' => $profileUser,
            'tagSuggestions' => $this->projects->findDistinctTags(),
        ]);
    }

    private function addLibraryAttribute(Request $request, User $profileUser): Response
    {
        $attributeId = $this->profileValues->attachLibrary($profileUser, $request->request->getInt('attribute_id'));
        if ($attributeId === null) {
            $this->addFlash('danger', 'profile.flash.select_attribute');

            return $this->redirectToInfo($profileUser);
        }

        $this->recentAttributes->remember([$attributeId]);
        $this->addFlash('success', 'profile.flash.attribute_added');

        return $this->redirectToInfo($profileUser);
    }

    private function removeLibraryAttributes(Request $request, User $profileUser): Response
    {
        $ids = $this->selectedIds($request);
        if ($ids === []) {
            $this->addFlash('danger', 'profile.flash.select_one');

            return $this->redirectToInfo($profileUser);
        }

        $this->profileValues->detachLibraryAttributes($profileUser, $ids);
        $this->addFlash('success', 'profile.flash.attribute_removed');

        return $this->redirectToInfo($profileUser);
    }

    /**
     * @param list<int> $ids
     */
    private function redirectToSingleProjectEdit(User $profileUser, array $ids): Response
    {
        if (\count($ids) !== 1) {
            $this->addFlash('danger', 'profile.flash.select_one');

            return $this->redirectToProjects($profileUser);
        }

        return $this->redirectToRoute('app_profile_project_edit', [
            'id' => $profileUser->getId(),
            'projectId' => $ids[0],
        ]);
    }

    /**
     * @param list<int> $ids
     */
    private function deleteProjects(User $profileUser, array $ids): Response
    {
        if ($this->projectService->deleteForOwner($profileUser, $ids) === 0) {
            $this->addFlash('danger', 'profile.flash.select_one');

            return $this->redirectToProjects($profileUser);
        }

        $this->addFlash('success', 'profile.flash.project_deleted');

        return $this->redirectToProjects($profileUser);
    }

    private function requireOwnedProject(User $profileUser, int $projectId): Project
    {
        return $this->projects->findOneByOwner($profileUser, $projectId) ?? throw $this->createNotFoundException();
    }

    private function requireCurrentUser(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        return $user;
    }

    private function requireProfileUser(int $id): User
    {
        $current = $this->requireCurrentUser();
        if ($id === $current->getId()) {
            return $current;
        }

        return $this->users->find($id) ?? throw $this->createNotFoundException();
    }

    /**
     * @return list<int>
     */
    private function selectedIds(Request $request): array
    {
        $ids = array_map(static fn (mixed $id): int => (int) $id, (array) $request->request->all('ids'));

        return array_values(array_filter($ids, static fn (int $id): bool => $id > 0));
    }

    private function wantsJson(Request $request): bool
    {
        return \in_array('application/json', $request->getAcceptableContentTypes(), true);
    }

    private function safeTab(string $tab): string
    {
        return \in_array($tab, ['me', 'info', 'projects', 'cvs'], true) ? $tab : 'me';
    }

    private function redirectToInfo(User $profileUser): Response
    {
        return $this->redirectToRoute('app_profile_show', ['id' => $profileUser->getId(), 'tab' => 'info']);
    }

    private function redirectToProjects(User $profileUser): Response
    {
        return $this->redirectToRoute('app_profile_show', ['id' => $profileUser->getId(), 'tab' => 'projects']);
    }

    private function redirectToCvs(User $profileUser): Response
    {
        return $this->redirectToRoute('app_profile_show', ['id' => $profileUser->getId(), 'tab' => 'cvs']);
    }
}
