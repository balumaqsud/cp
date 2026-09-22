<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Attribute;
use App\Entity\Project;
use App\Entity\User;
use App\Form\ProjectType;
use App\Repository\AttributeRepository;
use App\Repository\AttributeValueRepository;
use App\Repository\CurriculumVitaeRepository;
use App\Repository\ProjectRepository;
use App\Repository\UserRepository;
use App\Security\Voter\ProfileVoter;
use App\Service\ProfileValueService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ProfileController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly AttributeRepository $attributes,
        private readonly AttributeValueRepository $attributeValues,
        private readonly ProjectRepository $projects,
        private readonly CurriculumVitaeRepository $cvs,
        private readonly ProfileValueService $profileValues,
        private readonly EntityManagerInterface $entityManager,
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

        $allowed = $this->editableAttributes($profileUser);
        $items = [];
        foreach ($request->request->all('values') as $attributeId => $raw) {
            $attributeId = (int) $attributeId;
            if (!isset($allowed[$attributeId]) || !\is_array($raw)) {
                continue;
            }

            $versionRaw = $raw['version'] ?? '';
            $items[] = [
                'attributeId' => $attributeId,
                'value' => $this->parsedValue($raw),
                'version' => $versionRaw === '' ? null : (int) $versionRaw,
            ];
        }

        try {
            $versions = $this->profileValues->upsertMany($profileUser, $items, $allowed);
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
        $project = $this->requireOwnedProject($profileUser, $projectId);

        return $this->handleProjectForm($request, $profileUser, $project);
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

        $selected = $this->cvs->findByUserAndIds($profileUser, $this->selectedIds($request));
        if ($selected === []) {
            $this->addFlash('danger', 'profile.flash.select_one');

            return $this->redirectToCvs($profileUser);
        }

        foreach ($selected as $cv) {
            $this->entityManager->remove($cv);
        }
        $this->entityManager->flush();
        $this->addFlash('success', 'profile.flash.cvs_deleted');

        return $this->redirectToCvs($profileUser);
    }

    private function renderProfile(User $profileUser, string $tab): Response
    {
        $this->denyAccessUnlessGranted(ProfileVoter::VIEW, $profileUser);

        $values = $this->attributeValues->findEntitiesIndexedByAttributeId($profileUser);
        $infoAttributes = [];
        foreach ($values as $row) {
            $attribute = $row->getAttribute();
            if ($attribute !== null && !$attribute->isBuiltIn()) {
                $infoAttributes[] = $attribute;
            }
        }

        $selectedInfoIds = array_map(
            static fn (Attribute $attribute): int => (int) $attribute->getId(),
            $infoAttributes,
        );

        $available = [];
        foreach ($this->attributes->findLibrary() as $attribute) {
            if (!\in_array($attribute->getId(), $selectedInfoIds, true)) {
                $available[] = $attribute;
            }
        }

        return $this->render('profile/show.html.twig', [
            'profileUser' => $profileUser,
            'tab' => $this->safeTab($tab),
            'canEdit' => $this->isGranted(ProfileVoter::EDIT, $profileUser),
            'builtIns' => $this->attributes->findBuiltIns(),
            'infoAttributes' => $infoAttributes,
            'availableAttributes' => $available,
            'values' => $values,
            'projects' => $this->projects->findByOwner($profileUser),
            'cvs' => $this->cvs->findByUser($profileUser),
            'tagSuggestions' => $this->projects->findDistinctTags(),
        ]);
    }

    private function handleProjectForm(Request $request, User $profileUser, Project $project): Response
    {
        $form = $this->createForm(ProjectType::class, $project);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $isNew = $project->getId() === null;
            $project->touch();
            if ($isNew) {
                $this->entityManager->persist($project);
            }
            $this->entityManager->flush();
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
        $attribute = $this->attributes->find($request->request->getInt('attribute_id'));
        if (!$attribute instanceof Attribute || $attribute->isBuiltIn()) {
            $this->addFlash('danger', 'profile.flash.select_attribute');

            return $this->redirectToInfo($profileUser);
        }

        $this->profileValues->attachLibraryAttribute($profileUser, $attribute);
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
        $selected = $this->projects->findByOwnerAndIds($profileUser, $ids);
        if ($selected === []) {
            $this->addFlash('danger', 'profile.flash.select_one');

            return $this->redirectToProjects($profileUser);
        }

        foreach ($selected as $project) {
            $this->entityManager->remove($project);
        }
        $this->entityManager->flush();
        $this->addFlash('success', 'profile.flash.project_deleted');

        return $this->redirectToProjects($profileUser);
    }

    private function requireOwnedProject(User $profileUser, int $projectId): Project
    {
        $project = $this->projects->find($projectId);
        if (!$project instanceof Project || $project->getOwner()?->getId() !== $profileUser->getId()) {
            throw $this->createNotFoundException();
        }

        return $project;
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
     * @return array<int, Attribute>
     */
    private function editableAttributes(User $profileUser): array
    {
        $allowed = [];
        foreach ($this->attributes->findBuiltIns() as $attribute) {
            if ($attribute->getId() !== null) {
                $allowed[$attribute->getId()] = $attribute;
            }
        }

        foreach ($this->attributeValues->findEntitiesIndexedByAttributeId($profileUser) as $row) {
            $attribute = $row->getAttribute();
            if ($attribute?->getId() !== null) {
                $allowed[$attribute->getId()] = $attribute;
            }
        }

        return $allowed;
    }

    /**
     * @param array<string, mixed> $raw
     */
    private function parsedValue(array $raw): mixed
    {
        if (\array_key_exists('from', $raw) || \array_key_exists('to', $raw)) {
            return [
                'from' => (string) ($raw['from'] ?? ''),
                'to' => (string) ($raw['to'] ?? ''),
            ];
        }

        return $raw['value'] ?? null;
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
