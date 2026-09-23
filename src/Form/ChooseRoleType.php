<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;

final class ChooseRoleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('role', ChoiceType::class, [
            'label' => 'auth.role',
            'choices' => [
                'user.role.ROLE_CANDIDATE' => User::ROLE_CANDIDATE,
                'user.role.ROLE_RECRUITER' => User::ROLE_RECRUITER,
            ],
            'placeholder' => 'auth.role_placeholder',
        ]);
    }
}
