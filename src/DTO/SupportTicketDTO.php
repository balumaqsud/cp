<?php

declare(strict_types=1);

namespace App\DTO;

use App\Enum\SupportPriority;
use Symfony\Component\Validator\Constraints as Assert;

final class SupportTicketDTO
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 2000)]
    public string $summary = '';

    #[Assert\NotNull]
    public ?SupportPriority $priority = null;
}
