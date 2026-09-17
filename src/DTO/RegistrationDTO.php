<?php

declare(strict_types=1);

namespace App\DTO;

use App\Entity\User;
use Symfony\Component\Validator\Constraints as Assert;

final class RegistrationDTO
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    public string $name = '';

    #[Assert\NotBlank]
    #[Assert\Email]
    #[Assert\Length(max: 255)]
    public string $email = '';

    #[Assert\NotBlank]
    #[Assert\Length(min: 8, max: 255)]
    public string $password = '';

    #[Assert\NotBlank]
    #[Assert\EqualTo(
        propertyPath: 'password',
        message: 'Passwords do not match.',
    )]
    public string $passwordConfirm = '';

    #[Assert\Choice(
        choices: [
            User::ROLE_CANDIDATE,
            User::ROLE_RECRUITER,
        ],
        message: 'Please choose Candidate or Recruiter.',
    )]
    public string $role = User::ROLE_CANDIDATE;
}
