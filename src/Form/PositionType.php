<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Position;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class PositionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class)
            ->add('shortDescription', TextareaType::class, [
                'required' => false,
            ])
            ->add('company', TextType::class, [
                'required' => false,
            ])
            ->add('level', ChoiceType::class, [
                'required' => false,
                'placeholder' => 'Any',
                'choices' => [
                    'Junior' => 'Junior',
                    'Middle' => 'Middle',
                    'Senior' => 'Senior',
                    'C-level' => 'C-level',
                ],
            ])
            ->add('isPublic', CheckboxType::class, [
                'required' => false,
                'label' => 'Public (any signed-in candidate)',
            ])
            ->add('projectTagsText', TextType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'Project tags (comma-separated)',
            ])
            ->add('maxProjects', IntegerType::class, [
                'required' => false,
                'label' => 'Maximum projects on CV',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Position::class,
        ]);
    }
}
