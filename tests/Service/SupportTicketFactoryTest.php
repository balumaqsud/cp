<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\DTO\SupportTicketDTO;
use App\Entity\User;
use App\Enum\SupportPriority;
use App\Service\SupportTicketFactory;
use PHPUnit\Framework\TestCase;

final class SupportTicketFactoryTest extends TestCase
{
    public function testBuildIncludesRole(): void
    {
        $payload = (new SupportTicketFactory())->build(
            $this->user('Ada Lovelace', 'ada@example.com', ['ROLE_CANDIDATE']),
            $this->ticket('  Publish button does nothing  ', SupportPriority::High),
            'http://localhost/positions/4',
            'Business Analyst',
            ['admin@example.com'],
        );

        self::assertSame([
            'Reported by',
            'Position',
            'Link',
            'Priority',
            'Summary',
            'Admins',
        ], array_keys($payload));
        self::assertSame('Ada Lovelace <ada@example.com> (ROLE_CANDIDATE)', $payload['Reported by']);
        self::assertSame('Business Analyst', $payload['Position']);
        self::assertSame('http://localhost/positions/4', $payload['Link']);
        self::assertSame('High', $payload['Priority']);
        self::assertSame('Publish button does nothing', $payload['Summary']);
        self::assertSame(['admin@example.com'], $payload['Admins']);
    }

    public function testBuildJoinsAssignedRoles(): void
    {
        $payload = (new SupportTicketFactory())->build(
            $this->user('Ada Lovelace', 'ada@example.com', ['ROLE_CANDIDATE', 'ROLE_RECRUITER']),
            $this->ticket('Cannot publish', SupportPriority::Average),
            'http://localhost/',
            '',
            ['admin@example.com'],
        );

        self::assertSame(
            'Ada Lovelace <ada@example.com> (ROLE_CANDIDATE, ROLE_RECRUITER)',
            $payload['Reported by'],
        );
    }

    public function testBuildOmitsParenthesesWhenUserHasNoAssignedRole(): void
    {
        $payload = (new SupportTicketFactory())->build(
            $this->user('Ada Lovelace', 'ada@example.com', []),
            $this->ticket('Cannot publish', SupportPriority::Low),
            'http://localhost/',
            '',
            [],
        );

        self::assertSame('Ada Lovelace <ada@example.com>', $payload['Reported by']);
    }

    /**
     * @param list<string> $roles
     */
    private function user(string $name, string $email, array $roles): User
    {
        $user = new User();
        $user->setName($name);
        $user->setEmail($email);
        $user->setRoles($roles);

        return $user;
    }

    private function ticket(string $summary, SupportPriority $priority): SupportTicketDTO
    {
        $ticket = new SupportTicketDTO();
        $ticket->summary = $summary;
        $ticket->priority = $priority;

        return $ticket;
    }
}
