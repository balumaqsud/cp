<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final class SalesforceCrmDTO
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    public string $company = '';

    #[Assert\Length(max: 40)]
    public string $phone = '';

    #[Assert\Length(max: 255)]
    public string $title = '';

    #[Assert\Length(max: 32000)]
    public string $notes = '';

    public bool $newsletter = false;
}
