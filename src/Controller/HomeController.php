<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Repository\CurriculumVitaeRepository;
use App\Repository\PositionRepository;
use App\Repository\ProjectRepository;
use App\Repository\UserRepository;
use App\Service\PositionAccessEvaluator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    public function __construct(
        private readonly PositionRepository $positions,
        private readonly ProjectRepository $projects,
        private readonly CurriculumVitaeRepository $cvs,
        private readonly UserRepository $users,
        private readonly PositionAccessEvaluator $accessEvaluator,
    ) {
    }

    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function index(): Response
    {
        $user = $this->getUser();
        if ($user instanceof User && $user->isRecruiter()) {
            $latest = $this->positions->findLatestManaged(10);
        } elseif ($user instanceof User) {
            $latest = \array_slice(
                $this->accessEvaluator->visibleTo($user, $this->positions->findAllWithAccessGraph()),
                0,
                10,
            );
        } else {
            $latest = $this->positions->findLatest(10);
        }

        return $this->render('home/index.html.twig', [
            'latest' => $latest,
            'popular' => $this->positions->findPopular(5),
            'tagCloud' => $this->projects->tagCloud(),
            'stats' => [
                'cvs24h' => $this->cvs->countPublishedSince(new \DateTimeImmutable('-24 hours')),
                'cvs' => $this->cvs->countPublished(),
                'positions' => $this->positions->count([]),
                'candidates' => $this->users->countWithAnyRole(User::ROLE_CANDIDATE, User::ROLE_ADMIN),
                'recruiters' => $this->users->countWithAnyRole(User::ROLE_RECRUITER, User::ROLE_ADMIN),
            ],
        ]);
    }
}
