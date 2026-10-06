<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\DTO\SupportTicketDTO;
use App\Entity\User;
use App\Enum\SupportPriority;
use App\Repository\CurriculumVitaeRepository;
use App\Repository\PositionRepository;
use App\Repository\UserRepository;
use App\Service\DropboxClient;
use App\Service\SupportTicketContext;
use App\Service\SupportTicketFactory;
use App\Service\SupportTicketService;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class SupportTicketServiceTest extends TestCase
{
    public function testSubmitWithoutAdminsDoesNotUpload(): void
    {
        $http = $this->createMock(HttpClientInterface::class);
        $http->expects(self::never())->method('request');

        $users = $this->createMock(UserRepository::class);
        $users->expects(self::once())
            ->method('findAdminEmails')
            ->willReturn([]);

        $service = $this->service(
            new DropboxClient($http, 'app-key', 'app-secret', 'refresh-token', '/SupportTickets'),
            $users,
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('no_admins');

        $service->submit($this->user(), $this->ticket(), 'http://localhost:8000/', 'http://localhost:8000');
    }

    public function testSubmitWithoutDropboxDoesNotQueryAdmins(): void
    {
        $http = $this->createMock(HttpClientInterface::class);
        $http->expects(self::never())->method('request');

        $users = $this->createMock(UserRepository::class);
        $users->expects(self::never())->method('findAdminEmails');

        $service = $this->service(new DropboxClient($http, '', '', '', '/SupportTickets'), $users);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('not_configured');

        $service->submit($this->user(), $this->ticket(), 'http://localhost:8000/', 'http://localhost:8000');
    }

    private function service(DropboxClient $dropbox, UserRepository $users): SupportTicketService
    {
        $positions = $this->createMock(PositionRepository::class);
        $positions->expects(self::never())->method('find');
        $cvs = $this->createMock(CurriculumVitaeRepository::class);
        $cvs->expects(self::never())->method('find');

        return new SupportTicketService(
            $dropbox,
            new SupportTicketFactory(),
            new SupportTicketContext($positions, $cvs),
            $users,
        );
    }

    private function user(): User
    {
        $user = new User();
        $user->setName('Ada Lovelace');
        $user->setEmail('ada@example.com');
        $user->setRoles(['ROLE_CANDIDATE']);

        return $user;
    }

    private function ticket(): SupportTicketDTO
    {
        $ticket = new SupportTicketDTO();
        $ticket->summary = 'Cannot publish';
        $ticket->priority = SupportPriority::High;

        return $ticket;
    }
}
