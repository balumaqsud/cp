<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\SupportTicketDTO;
use App\Entity\User;

final class SupportTicketFactory
{
    /**
     * @param list<string> $adminEmails
     * @return array{Reported by: string, Position: string, Link: string, Priority: string, Summary: string, Admins: list<string>}
     */
    public function build(User $reporter, SupportTicketDTO $ticket, string $link, string $positionTitle, array $adminEmails): array
    {
        $priority = $this->priorityValue($ticket);

        return [
            'Reported by' => $this->reportedBy($reporter),
            'Position' => $positionTitle,
            'Link' => $link,
            'Priority' => $priority,
            'Summary' => trim($ticket->summary),
            'Admins' => $adminEmails,
        ];
    }

    private function reportedBy(User $reporter): string
    {
        $label = $reporter->getName().' <'.$reporter->getEmail().'>';
        $roles = $reporter->getAssignedRoles();
        if ($roles === []) {
            return $label;
        }

        return $label.' ('.implode(', ', $roles).')';
    }

    private function priorityValue(SupportTicketDTO $ticket): string
    {
        if ($ticket->priority === null) {
            throw new \InvalidArgumentException('priority');
        }

        return $ticket->priority->value;
    }
}
