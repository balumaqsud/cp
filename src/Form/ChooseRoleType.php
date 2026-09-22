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
            'choices' => [
                'Candidate' => User::ROLE_CANDIDATE,
                'Recruiter' => User::ROLE_RECRUITER,
            ],
            'placeholder' => 'Choose a role',
        ]);
    }
}
