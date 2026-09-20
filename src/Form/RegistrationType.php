<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

final class RegistrationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class)
            ->add('email', EmailType::class)
            ->add('password', PasswordType::class)
            ->add('passwordConfirm', PasswordType::class)
            ->add('role', ChoiceType::class, [
                'choices' => [
                    'Candidate' => User::ROLE_CANDIDATE,
                    'Recruiter' => User::ROLE_RECRUITER,
                ],
                'placeholder' => 'Choose a role',
            ]);
    }

    public function getBlockPrefix(): string
    {
        return 'registration';
    }
}
