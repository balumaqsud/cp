<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Repository\CurriculumVitaeRepository;
use App\Repository\PositionRepository;
use App\Security\Voter\CurriculumVitaeVoter;
use App\Security\Voter\PositionVoter;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class SearchService
{
    public function __construct(
        private readonly PositionRepository $positions,
        private readonly CurriculumVitaeRepository $cvs,
        private readonly AuthorizationCheckerInterface $authorization,
    ) {
    }

    /**
     * @return array{positions: list<\App\Entity\Position>, cvs: list<\App\Entity\CurriculumVitae>}
     */
    public function search(string $query, ?User $user): array
    {
        $query = trim($query);
        if ($query === '') {
            return ['positions' => [], 'cvs' => []];
        }

        $positions = [];
        foreach ($this->positions->searchFullText($query) as $position) {
            if ($this->authorization->isGranted(PositionVoter::VIEW, $position)) {
                $positions[] = $position;
            }
        }

        $cvs = [];
        if ($user !== null && $user->isRecruiter()) {
            foreach ($this->cvs->searchPublishedFullText($query) as $cv) {
                if ($this->authorization->isGranted(CurriculumVitaeVoter::VIEW, $cv)) {
                    $cvs[] = $cv;
                }
            }
        }

        return ['positions' => $positions, 'cvs' => $cvs];
    }
}
