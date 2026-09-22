<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Attribute;
use App\Entity\CurriculumVitae;
use App\Entity\Position;
use App\Entity\User;
use App\Enum\CvStatus;
use App\Repository\AttributeRepository;
use App\Repository\AttributeValueRepository;
use App\Repository\CurriculumVitaeRepository;
use App\Repository\CvLikeRepository;
use App\Repository\PositionRepository;
use App\Security\Voter\CurriculumVitaeVoter;
use App\Security\Voter\PositionVoter;
use App\Service\AttributeValueHelper;
use App\Service\CvService;
use App\Service\PositionAccessEvaluator;
use App\Service\ProfileValueService;
use Doctrine\ORM\OptimisticLockException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CvController extends AbstractController
{
    public function __construct(
        private readonly CurriculumVitaeRepository $cvs,
        private readonly PositionRepository $positions,
        private readonly AttributeRepository $attributes,
        private readonly AttributeValueRepository $attributeValues,
        private readonly CvLikeRepository $likes,
        private readonly CvService $cvService,
        private readonly ProfileValueService $profileValues,
        private readonly PositionAccessEvaluator $accessEvaluator,
    ) {
    }

    #[Route('/cvs', name: 'app_cv_index', methods: ['GET'])]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted(CurriculumVitaeVoter::LIST);
        $user = $this->requireUser();
        $rows = $this->cvService->publishedVisibleTo($user);

        return $this->render('cv/index.html.twig', [
            'cvs' => $rows,
            'likeCounts' => $this->likes->countIndexedByCvIds($this->idsOf($rows)),
        ]);
    }

    #[Route('/cvs/new', name: 'app_cv_new', methods: ['GET'])]
    public function new(): Response
    {
        $user = $this->requireUser();
        if (!$user->isCandidate()) {
            throw $this->createAccessDeniedException();
        }

        $positions = $this->accessEvaluator->visibleTo($user, $this->positions->findAllWithAccessGraph());

        return $this->render('cv/new.html.twig', [
            'positions' => $positions,
        ]);
    }

    #[Route('/cvs/generate', name: 'app_cv_generate', methods: ['POST'])]
    public function generate(Request $request): Response
    {
        $user = $this->requireUser();
        if (!$this->isCsrfTokenValid('cv_generate', $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $positionId = $request->request->getInt('position_id');
        if ($positionId === 0) {
            $ids = array_map(static fn (mixed $id): int => (int) $id, (array) $request->request->all('ids'));
            $ids = array_values(array_filter($ids, static fn (int $id): bool => $id > 0));
            if (\count($ids) !== 1) {
                $this->addFlash('danger', 'cv.flash.select_one');

                return $this->redirectToRoute('app_cv_new');
            }
            $positionId = $ids[0];
        }

        $position = $this->positions->findOneWithTemplate($positionId) ?? throw $this->createNotFoundException();
        $this->denyAccessUnlessGranted(PositionVoter::GENERATE_CV, $position);

        $cv = $this->cvService->getOrCreate($user, $position);

        return $this->redirectToRoute('app_cv_show', ['id' => $cv->getId()]);
    }

    #[Route('/cvs/{id}', name: 'app_cv_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id): Response
    {
        $cv = $this->requireCv($id);
        $this->denyAccessUnlessGranted(CurriculumVitaeVoter::VIEW, $cv);

        return $this->renderCv($cv);
    }

    #[Route('/cvs/{id}/values', name: 'app_cv_values', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function saveValues(Request $request, int $id): Response
    {
        $cv = $this->requireCv($id);
        $this->denyAccessUnlessGranted(CurriculumVitaeVoter::EDIT, $cv);
        $owner = $cv->getUser() ?? throw $this->createNotFoundException();

        if (!$this->isCsrfTokenValid('cv_values', $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $allowed = $this->editableAttributes($cv);
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
            $versions = $this->profileValues->upsertMany($owner, $items, $allowed);
        } catch (OptimisticLockException) {
            $this->addFlash('danger', 'cv.flash.conflict');

            if ($this->wantsJson($request)) {
                return $this->json(['ok' => false, 'conflict' => true], Response::HTTP_CONFLICT);
            }

            return $this->redirectToRoute('app_cv_show', ['id' => $cv->getId()]);
        }

        if ($this->wantsJson($request)) {
            return $this->json(['ok' => true, 'versions' => $versions]);
        }

        $this->addFlash('success', 'cv.flash.saved');

        return $this->redirectToRoute('app_cv_show', ['id' => $cv->getId()]);
    }

    #[Route('/cvs/{id}/publish', name: 'app_cv_publish', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function publish(Request $request, int $id): Response
    {
        $cv = $this->requireCv($id);
        $this->denyAccessUnlessGranted(CurriculumVitaeVoter::PUBLISH, $cv);

        if (!$this->isCsrfTokenValid('cv_publish', $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException();
        }

        try {
            $this->cvService->publish($cv);
        } catch (\InvalidArgumentException) {
            $this->addFlash('danger', 'cv.flash.incomplete');

            return $this->redirectToRoute('app_cv_show', ['id' => $cv->getId()]);
        }

        $this->addFlash('success', 'cv.flash.published');

        return $this->redirectToRoute('app_cv_show', ['id' => $cv->getId()]);
    }

    #[Route('/cvs/{id}/like', name: 'app_cv_like', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function like(Request $request, int $id): Response
    {
        $cv = $this->requireCv($id);
        $this->denyAccessUnlessGranted(CurriculumVitaeVoter::LIKE, $cv);
        $user = $this->requireUser();

        if (!$this->isCsrfTokenValid('cv_like', $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $liked = $this->cvService->toggleLike($user, $cv);
        $this->addFlash('success', $liked ? 'cv.flash.liked' : 'cv.flash.unliked');

        return $this->redirectToRoute('app_cv_show', ['id' => $cv->getId()]);
    }

    private function renderCv(CurriculumVitae $cv): Response
    {
        $owner = $cv->getUser() ?? throw $this->createNotFoundException();
        $position = $cv->getPosition() ?? throw $this->createNotFoundException();
        $this->positions->findOneWithTemplate((int) $position->getId());

        $user = $this->requireUser();
        $builtIns = $this->attributes->findBuiltIns();
        $templateAttributes = [];
        $requiredIds = [];
        foreach ($position->getPositionAttributes() as $positionAttribute) {
            $attribute = $positionAttribute->getAttribute();
            if ($attribute === null) {
                continue;
            }
            if ($positionAttribute->isRequired() && $attribute->getId() !== null) {
                $requiredIds[] = $attribute->getId();
            }
            if (!$attribute->isBuiltIn()) {
                $templateAttributes[] = $attribute;
            }
        }

        $valueEntities = $this->attributeValues->findEntitiesIndexedByAttributeId($owner);
        $valueMap = [];
        foreach ($valueEntities as $attributeId => $row) {
            $valueMap[$attributeId] = $row->getValue();
        }

        $emptyIds = [];
        foreach ([...$builtIns, ...$templateAttributes] as $attribute) {
            $attributeId = $attribute->getId();
            if ($attributeId !== null && AttributeValueHelper::isEmpty($valueMap[$attributeId] ?? null)) {
                $emptyIds[] = $attributeId;
            }
        }

        $canEdit = $this->isGranted(CurriculumVitaeVoter::EDIT, $cv);
        $liked = $this->likes->findOneByRecruiterAndCv($user, $cv) !== null;

        return $this->render('cv/show.html.twig', [
            'cv' => $cv,
            'owner' => $owner,
            'position' => $position,
            'builtIns' => $builtIns,
            'templateAttributes' => $templateAttributes,
            'requiredIds' => $requiredIds,
            'values' => $valueEntities,
            'emptyIds' => $emptyIds,
            'projects' => $this->cvService->relevantProjects($owner, $position),
            'complete' => $this->cvService->isComplete($cv),
            'canEdit' => $canEdit,
            'canPublish' => $this->isGranted(CurriculumVitaeVoter::PUBLISH, $cv),
            'canLike' => $this->isGranted(CurriculumVitaeVoter::LIKE, $cv),
            'liked' => $liked,
            'likeCount' => $this->likes->countByCv($cv),
            'published' => $cv->getStatus() === CvStatus::Published->value,
        ]);
    }

    private function requireCv(int $id): CurriculumVitae
    {
        return $this->cvs->findOneWithOwnerAndPosition($id) ?? throw $this->createNotFoundException();
    }

    private function requireUser(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        return $user;
    }

    /**
     * @return array<int, Attribute>
     */
    private function editableAttributes(CurriculumVitae $cv): array
    {
        $position = $cv->getPosition() ?? throw $this->createNotFoundException();
        $this->positions->findOneWithTemplate((int) $position->getId());

        $allowed = [];
        foreach ($this->attributes->findBuiltIns() as $attribute) {
            if ($attribute->getId() !== null) {
                $allowed[$attribute->getId()] = $attribute;
            }
        }

        foreach ($position->getPositionAttributes() as $positionAttribute) {
            $attribute = $positionAttribute->getAttribute();
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

    private function wantsJson(Request $request): bool
    {
        return \in_array('application/json', $request->getAcceptableContentTypes(), true);
    }

    /**
     * @param list<CurriculumVitae> $cvs
     * @return list<int>
     */
    private function idsOf(array $cvs): array
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
