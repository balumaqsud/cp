<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\CurriculumVitae;
use App\Entity\User;
use App\Repository\CurriculumVitaeRepository;
use App\Repository\CvLikeRepository;
use App\Repository\PositionRepository;
use App\Security\Voter\CurriculumVitaeVoter;
use App\Security\Voter\PositionVoter;
use App\Service\CvService;
use App\Service\CvViewService;
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
        private readonly CvLikeRepository $likes,
        private readonly CvService $cvService,
        private readonly CvViewService $cvView,
        private readonly ProfileValueService $profileValues,
    ) {
    }

    #[Route('/cvs', name: 'app_cv_index', methods: ['GET'])]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted(CurriculumVitaeVoter::LIST);
        $rows = $this->cvService->publishedVisibleTo($this->requireUser());

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

        $positions = $user->getId() === null ? [] : $this->positions->findVisibleToCandidate($user->getId());

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

        $positionId = $this->selectedPositionId($request);
        if ($positionId === null) {
            $this->addFlash('danger', 'cv.flash.select_one');

            return $this->redirectToRoute('app_cv_new');
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

        return $this->render('cv/show.html.twig', $this->cvView->build(
            $cv,
            $this->requireUser(),
            $this->isGranted(CurriculumVitaeVoter::EDIT, $cv),
            $this->isGranted(CurriculumVitaeVoter::PUBLISH, $cv),
            $this->isGranted(CurriculumVitaeVoter::LIKE, $cv),
        ));
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

        try {
            $versions = $this->profileValues->saveSubmitted(
                $owner,
                $request->request->all('values'),
                $this->cvView->editableAttributes($cv),
            );
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

    private function selectedPositionId(Request $request): ?int
    {
        $positionId = $request->request->getInt('position_id');
        if ($positionId !== 0) {
            return $positionId;
        }

        $ids = array_map(static fn (mixed $id): int => (int) $id, (array) $request->request->all('ids'));
        $ids = array_values(array_filter($ids, static fn (int $id): bool => $id > 0));

        return \count($ids) === 1 ? $ids[0] : null;
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
