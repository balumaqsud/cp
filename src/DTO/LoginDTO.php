<?php

declare(strict_types=1);

namespace App\DTO;

use App\Entity\User;
use Symfony\Component\Validator\Constraints as Assert;


final class LoginDTO {

   public function __construct(string $email, string $password, bool $rememberMe) {
        $this->email = $email;
        $this->password = $password;
        $this->rememberMe = $rememberMe;
    }

    #[Assert\NotBlank]
    #[Assert\Email]
    public string $email = '';

    #[Assert\NotBlank]
    #[Assert\Length(min: 8, max: 255)]
    public string $password = '';

    #[Assert\Type('bool')]
    public bool $rememberMe = false;



}
