<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\CurriculumVitae;
use App\Entity\Position;
use App\Repository\CurriculumVitaeRepository;
use App\Repository\PositionRepository;
use App\Service\SupportTicketContext;
use PHPUnit\Framework\TestCase;

final class SupportTicketContextTest extends TestCase
{
    public function testPositionPathReturnsTitle(): void
    {
        $positions = $this->createMock(PositionRepository::class);
        $positions->expects(self::once())
            ->method('find')
            ->with(4)
            ->willReturn($this->position('Business Analyst'));

        $title = $this->context($positions, $this->cvsNever())->positionTitle('/positions/4');

        self::assertSame('Business Analyst', $title);
    }

    public function testPositionEditPathReturnsTitle(): void
    {
        $positions = $this->createMock(PositionRepository::class);
        $positions->expects(self::once())
            ->method('find')
            ->with(4)
            ->willReturn($this->position('Business Analyst'));

        $title = $this->context($positions, $this->cvsNever())->positionTitle('/positions/4/edit');

        self::assertSame('Business Analyst', $title);
    }

    public function testCvPathReturnsPositionTitle(): void
    {
        $cv = new CurriculumVitae();
        $cv->setPosition($this->position('Backend Engineer'));

        $cvs = $this->createMock(CurriculumVitaeRepository::class);
        $cvs->expects(self::once())
            ->method('find')
            ->with(9)
            ->willReturn($cv);

        $title = $this->context($this->positionsNever(), $cvs)->positionTitle('/cvs/9');

        self::assertSame('Backend Engineer', $title);
    }

    public function testUnrelatedPathsHaveNoPosition(): void
    {
        $context = $this->context($this->positionsNever(), $this->cvsNever());

        self::assertSame('', $context->positionTitle('/'));
        self::assertSame('', $context->positionTitle('/positions/new'));
        self::assertSame('', $context->positionTitle('/cvs/new'));
    }

    public function testLinkRejectsForeignHost(): void
    {
        $context = $this->context($this->positionsNever(), $this->cvsNever());

        self::assertSame(
            'http://localhost:8000/',
            $context->link('https://evil.example/positions/1', 'http://localhost:8000'),
        );
    }

    public function testLinkPrefixesRelativePath(): void
    {
        $context = $this->context($this->positionsNever(), $this->cvsNever());

        self::assertSame(
            'http://localhost:8000/positions/4?tab=1',
            $context->link('/positions/4?tab=1', 'http://localhost:8000'),
        );
    }

    public function testLinkKeepsSameOriginUrl(): void
    {
        $context = $this->context($this->positionsNever(), $this->cvsNever());

        self::assertSame(
            'http://localhost:8000/positions/4',
            $context->link('http://localhost:8000/positions/4', 'http://localhost:8000'),
        );
    }

    public function testLinkKeepsRenderOrigin(): void
    {
        $context = $this->context($this->positionsNever(), $this->cvsNever());

        self::assertSame(
            'https://app.onrender.com/positions/4',
            $context->link('https://app.onrender.com/positions/4', 'https://app.onrender.com'),
        );
    }

    public function testLinkRejectsProtocolRelativeUrl(): void
    {
        $context = $this->context($this->positionsNever(), $this->cvsNever());

        self::assertSame(
            'http://localhost:8000/',
            $context->link('//evil.example', 'http://localhost:8000'),
        );
    }

    private function context(PositionRepository $positions, CurriculumVitaeRepository $cvs): SupportTicketContext
    {
        return new SupportTicketContext($positions, $cvs);
    }

    private function positionsNever(): PositionRepository
    {
        $positions = $this->createMock(PositionRepository::class);
        $positions->expects(self::never())->method('find');

        return $positions;
    }

    private function cvsNever(): CurriculumVitaeRepository
    {
        $cvs = $this->createMock(CurriculumVitaeRepository::class);
        $cvs->expects(self::never())->method('find');

        return $cvs;
    }

    private function position(string $title): Position
    {
        $position = new Position();
        $position->setTitle($title);

        return $position;
    }
}
