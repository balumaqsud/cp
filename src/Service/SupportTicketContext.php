<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\CurriculumVitaeRepository;
use App\Repository\PositionRepository;

final class SupportTicketContext
{
    private const POSITION_PATH = '#^/positions/(\d+)(?:/(?:edit|api-token|discussion))?/?$#';
    private const CV_PATH = '#^/cvs/(\d+)/?$#';

    public function __construct(
        private readonly PositionRepository $positions,
        private readonly CurriculumVitaeRepository $cvs,
    ) {
    }

    public function link(string $from, string $origin): string
    {
        if ($this->isSameOrigin($from, $origin)) {
            return $from;
        }

        if ($this->isRelativePath($from)) {
            return $origin.$from;
        }

        return $origin.'/';
    }

    public function positionTitle(string $link): string
    {
        $path = $this->pathOf($link);
        $positionId = $this->matchId(self::POSITION_PATH, $path);
        if ($positionId !== null) {
            return $this->titleForPosition($positionId);
        }

        $cvId = $this->matchId(self::CV_PATH, $path);
        if ($cvId !== null) {
            return $this->titleForCv($cvId);
        }

        return '';
    }

    private function isSameOrigin(string $from, string $origin): bool
    {
        return $from === $origin || str_starts_with($from, $origin.'/');
    }

    private function isRelativePath(string $from): bool
    {
        return str_starts_with($from, '/') && !str_starts_with($from, '//');
    }

    private function pathOf(string $link): string
    {
        $path = parse_url($link, PHP_URL_PATH);

        return \is_string($path) ? $path : '';
    }

    private function matchId(string $pattern, string $path): ?int
    {
        if (preg_match($pattern, $path, $matches) !== 1) {
            return null;
        }

        return (int) $matches[1];
    }

    private function titleForPosition(int $id): string
    {
        return $this->positions->find($id)?->getTitle() ?? '';
    }

    private function titleForCv(int $id): string
    {
        return $this->cvs->find($id)?->getPosition()?->getTitle() ?? '';
    }
}
