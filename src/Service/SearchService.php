<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\CurriculumVitae;
use App\Entity\Position;
use App\Entity\User;
use App\Repository\CurriculumVitaeRepository;
use App\Repository\PositionRepository;

final class SearchService
{
    public function __construct(
        private readonly PositionRepository $positions,
        private readonly CurriculumVitaeRepository $cvs,
        private readonly PositionAccessEvaluator $accessEvaluator,
        private readonly CvService $cvService,
    ) {
    }

    /**
     * @return array{positions: list<Position>, cvs: list<CurriculumVitae>}
     */
    public function search(string $query, ?User $user, string $tag = ''): array
    {
        $query = trim($query);
        $tag = mb_strtolower(trim($tag));
        if ($query === '' && $tag === '') {
            return ['positions' => [], 'cvs' => []];
        }

        $positions = $query !== ''
            ? $this->positions->searchFullText($query)
            : $this->positions->findByTag($tag);

        if ($query !== '' && $tag !== '') {
            $positions = array_values(array_filter(
                $positions,
                static function (Position $position) use ($tag): bool {
                    $tags = array_map(static fn (string $item) => mb_strtolower($item), $position->getProjectTags());

                    return \in_array($tag, $tags, true);
                },
            ));
        }

        $visibleCvs = [];
        if ($user !== null && $user->isRecruiter()) {
            $cvs = $query !== ''
                ? $this->cvs->searchPublishedFullText($query)
                : $this->cvs->findPublishedByTag($tag);

            if ($query !== '' && $tag !== '') {
                $byTag = [];
                foreach ($this->cvs->findPublishedByTag($tag) as $cv) {
                    $byTag[$cv->getId()] = true;
                }
                $cvs = array_values(array_filter(
                    $cvs,
                    static fn (CurriculumVitae $cv): bool => isset($byTag[$cv->getId()]),
                ));
            }

            $visibleCvs = $this->cvService->filterVisible($user, $cvs);
        }

        return [
            'positions' => $this->visiblePositions($positions, $user),
            'cvs' => $visibleCvs,
        ];
    }

    /**
     * @param list<Position> $positions
     * @return list<Position>
     */
    private function visiblePositions(array $positions, ?User $user): array
    {
        if ($user === null) {
            return array_values(array_filter(
                $positions,
                static fn (Position $position): bool => $position->isPublic(),
            ));
        }

        if ($user->isRecruiter()) {
            return array_values($positions);
        }

        return $this->accessEvaluator->visibleTo($user, $positions);
    }
}
