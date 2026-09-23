<?php

declare(strict_types=1);

namespace App\Form;

use App\DTO\RegistrationDTO;
use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class RegistrationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'auth.name',
            ])
            ->add('email', EmailType::class, [
                'label' => 'auth.email',
            ])
            ->add('password', PasswordType::class, [
                'label' => 'auth.password',
            ])
            ->add('passwordConfirm', PasswordType::class, [
                'label' => 'auth.password_confirm',
            ])
            ->add('role', ChoiceType::class, [
                'label' => 'auth.role',
                'choices' => [
                    'user.role.ROLE_CANDIDATE' => User::ROLE_CANDIDATE,
                    'user.role.ROLE_RECRUITER' => User::ROLE_RECRUITER,
                ],
                'placeholder' => 'auth.role_placeholder',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RegistrationDTO::class,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'registration';
    }
}
