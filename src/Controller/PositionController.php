<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\CurriculumVitae;
use App\Entity\Position;
use App\Entity\User;
use App\Form\DiscussionPostType;
use App\Form\PositionType;
use App\Repository\AttributeRepository;
use App\Repository\CurriculumVitaeRepository;
use App\Repository\CvLikeRepository;
use App\Repository\DiscussionPostRepository;
use App\Repository\PositionRepository;
use App\Security\Voter\PositionVoter;
use App\Service\CvService;
use App\Service\PositionService;
use App\Service\PositionViewService;
use App\Service\RecentAttributeStore;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\ORM\OptimisticLockException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PositionController extends AbstractController
{
    public function __construct(
        private readonly PositionRepository $positions,
        private readonly AttributeRepository $attributes,
        private readonly CurriculumVitaeRepository $cvs,
        private readonly CvLikeRepository $likes,
        private readonly DiscussionPostRepository $discussionPosts,
        private readonly PositionService $positionService,
        private readonly PositionViewService $positionView,
        private readonly CvService $cvService,
        private readonly RecentAttributeStore $recentAttributes,
    ) {
    }

    #[Route('/positions', name: 'app_position_index', methods: ['GET'])]
    public function index(): Response
    {
        $user = $this->getUser();

        return $this->render('position/index.html.twig', [
            'positions' => $this->positionService->listFor($user instanceof User ? $user : null),
            'canManage' => $this->isGranted(PositionVoter::MANAGE),
        ]);
    }

    #[Route('/positions/new', name: 'app_position_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $this->denyAccessUnlessGranted(PositionVoter::MANAGE);

        return $this->handleForm($request, new Position());
    }

    #[Route('/positions/{id}/edit', name: 'app_position_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, int $id): Response
    {
        $this->denyAccessUnlessGranted(PositionVoter::MANAGE);

        return $this->handleForm($request, $this->requirePosition($id));
    }

    #[Route('/positions/{id}', name: 'app_position_show', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function show(Request $request, int $id): Response
    {
        $position = $this->requirePosition($id);
        $this->denyAccessUnlessGranted(PositionVoter::VIEW, $position);

        $discussionForm = $this->createForm(DiscussionPostType::class);
        $discussionForm->handleRequest($request);

        if ($discussionForm->isSubmitted()) {
            $this->denyAccessUnlessGranted(PositionVoter::DISCUSS, $position);
            $user = $this->getUser();
            if ($discussionForm->isValid() && $user instanceof User) {
                $this->positionService->addDiscussionPost(
                    $position,
                    $user,
                    (string) $discussionForm->get('content')->getData(),
                );
                $this->addFlash('success', 'position.flash.posted');

                return $this->redirectToRoute('app_position_show', ['id' => $position->getId()]);
            }
        }

        $user = $this->getUser();
        $publishedCvs = [];
        if ($user instanceof User && $user->isRecruiter()) {
            $publishedCvs = $this->cvService->publishedVisibleForPosition($position, $user);
        }

        $existingCv = null;
        $canGenerate = false;
        if ($user instanceof User) {
            $canGenerate = $this->isGranted(PositionVoter::GENERATE_CV, $position);
            if ($canGenerate) {
                $existingCv = $this->cvs->findOneByUserAndPosition($user, $position);
            }
        }

        return $this->render('position/show.html.twig', [
            'position' => $position,
            'publishedCvs' => $publishedCvs,
            'likeCounts' => $this->likes->countIndexedByCvIds($this->cvIds($publishedCvs)),
            'posts' => $this->discussionPosts->findByPositionChronological($position),
            'discussionForm' => $discussionForm,
            'canDiscuss' => $this->isGranted(PositionVoter::DISCUSS, $position),
            'canManage' => $this->isGranted(PositionVoter::MANAGE),
            'canGenerate' => $canGenerate,
            'existingCv' => $existingCv,
        ]);
    }

    #[Route('/positions/{id}/discussion', name: 'app_position_discussion', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function discussion(int $id): Response
    {
        $position = $this->requirePosition($id);
        $this->denyAccessUnlessGranted(PositionVoter::VIEW, $position);

        return $this->render('position/_posts.html.twig', [
            'posts' => $this->discussionPosts->findByPositionChronological($position),
        ]);
    }

    #[Route('/positions/bulk', name: 'app_position_bulk', methods: ['POST'])]
    public function bulk(Request $request): Response
    {
        $this->denyAccessUnlessGranted(PositionVoter::MANAGE);

        if (!$this->isCsrfTokenValid('position_bulk', $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $ids = $this->selectedIds($request);

        return match ($request->request->getString('toolbar_action')) {
            'edit' => $this->redirectToSingleEdit($ids),
            'duplicate' => $this->duplicateSelected($ids),
            'delete' => $this->deleteSelected($ids),
            default => $this->redirectToRoute('app_position_index'),
        };
    }

    private function handleForm(Request $request, Position $position): Response
    {
        $form = $this->createForm(PositionType::class, $position);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $isNew = $position->getId() === null;
            if (!$isNew) {
                $submittedVersion = (int) $form->get('version')->getData();
                if ($submittedVersion !== $position->getVersion()) {
                    $this->addFlash('danger', 'position.flash.conflict');

                    return $this->redirectToRoute('app_position_edit', ['id' => $position->getId()]);
                }
            }

            $attributeIds = $this->selectedAttributeIds($request);
            $this->positionService->applyTemplate(
                $position,
                $attributeIds,
                $this->requiredFlags($request),
                $request->request->all('rules'),
                $this->attributes->findIndexedById(),
            );

            try {
                $this->positionService->save($position);
            } catch (OptimisticLockException) {
                $this->addFlash('danger', 'position.flash.conflict');

                return $this->redirectToRoute('app_position_edit', ['id' => $position->getId()]);
            }

            $this->recentAttributes->remember($attributeIds);
            $this->addFlash('success', $isNew ? 'position.flash.created' : 'position.flash.updated');

            return $this->redirectToRoute('app_position_show', ['id' => $position->getId()]);
        }

        return $this->render('position/form.html.twig', [
            'form' => $form,
            'position' => $position,
            ...$this->positionView->formContext(
                $position,
                $form->isSubmitted(),
                $this->selectedAttributeIds($request),
                $this->requiredFlags($request),
                $request->request->all('rules'),
                $request->query->getString('prefix'),
                $request->query->getInt('category'),
            ),
        ]);
    }

    private function requirePosition(int $id): Position
    {
        return $this->positions->findOneWithTemplate($id) ?? throw $this->createNotFoundException();
    }

    /**
     * @return list<int>
     */
    private function selectedIds(Request $request): array
    {
        $ids = array_map(static fn (mixed $id): int => (int) $id, (array) $request->request->all('ids'));

        return array_values(array_filter($ids, static fn (int $id): bool => $id > 0));
    }

    /**
     * @param list<int> $ids
     */
    private function redirectToSingleEdit(array $ids): Response
    {
        if (\count($ids) !== 1) {
            $this->addFlash('danger', 'position.flash.select_one');

            return $this->redirectToRoute('app_position_index');
        }

        return $this->redirectToRoute('app_position_edit', ['id' => $ids[0]]);
    }

    /**
     * @param list<int> $ids
     */
    private function duplicateSelected(array $ids): Response
    {
        $selected = $this->positions->findByIds($ids);
        if ($selected === []) {
            $this->addFlash('danger', 'position.flash.select_one');

            return $this->redirectToRoute('app_position_index');
        }

        $last = $this->positionService->duplicateAll($selected);
        $this->addFlash('success', 'position.flash.duplicated');

        return $this->redirectToRoute('app_position_edit', ['id' => $last->getId()]);
    }

    /**
     * @param list<int> $ids
     */
    private function deleteSelected(array $ids): Response
    {
        $selected = $this->positions->findByIds($ids);
        if ($selected === []) {
            $this->addFlash('danger', 'position.flash.select_one');

            return $this->redirectToRoute('app_position_index');
        }

        try {
            $this->positionService->deleteMany($selected);
        } catch (ForeignKeyConstraintViolationException) {
            $this->addFlash('danger', 'position.flash.in_use');

            return $this->redirectToRoute('app_position_index');
        }

        $this->addFlash('success', 'position.flash.deleted');

        return $this->redirectToRoute('app_position_index');
    }

    /**
     * @return list<int>
     */
    private function selectedAttributeIds(Request $request): array
    {
        $ids = array_map(static fn (mixed $id): int => (int) $id, (array) $request->request->all('attribute_ids'));

        return array_values(array_filter($ids, static fn (int $id): bool => $id > 0));
    }

    /**
     * @return array<int, bool>
     */
    private function requiredFlags(Request $request): array
    {
        $raw = $request->request->all('attribute_required');
        $flags = [];
        foreach ($this->selectedAttributeIds($request) as $id) {
            $flags[$id] = isset($raw[$id]) || isset($raw[(string) $id]);
        }

        return $flags;
    }

    /**
     * @param list<CurriculumVitae> $cvs
     * @return list<int>
     */
    private function cvIds(array $cvs): array
    {
        $ids = [];
        foreach ($cvs as $cv) {
            if ($cv->getId() !== null) {
                $ids[] = $cv->getId();
            }
        }

        return $ids;
    }
}
