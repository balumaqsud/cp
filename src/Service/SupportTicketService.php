<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\SupportTicketDTO;
use App\Entity\User;
use App\Repository\UserRepository;

final class SupportTicketService
{
    public function __construct(
        private readonly DropboxClient $dropbox,
        private readonly SupportTicketFactory $factory,
        private readonly SupportTicketContext $context,
        private readonly UserRepository $users,
    ) {
    }

    public function submit(User $user, SupportTicketDTO $ticket, string $from, string $origin): void
    {
        $this->assertConfigured();
        $link = $this->context->link($from, $origin);
        $this->dropbox->upload($this->filename(), $this->jsonPayload($user, $ticket, $link));
    }

    private function assertConfigured(): void
    {
        if (!$this->dropbox->isConfigured()) {
            throw new \RuntimeException('not_configured');
        }
    }

    /**
     * @return list<string>
     */
    private function adminEmails(): array
    {
        $emails = $this->users->findAdminEmails();
        if ($emails === []) {
            throw new \RuntimeException('no_admins');
        }

        return $emails;
    }

    private function jsonPayload(User $user, SupportTicketDTO $ticket, string $link): string
    {
        $json = json_encode(
            $this->factory->build($user, $ticket, $link, $this->context->positionTitle($link), $this->adminEmails()),
            JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        );

        if (!\is_string($json)) {
            throw new \RuntimeException('dropbox');
        }

        return $json;
    }

    private function filename(): string
    {
        return sprintf(
            'support-%s-%s.json',
            (new \DateTimeImmutable())->format('Ymd-His'),
            bin2hex(random_bytes(4)),
        );
    }
}
