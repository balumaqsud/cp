<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Attribute;
use App\Entity\Category;
use App\Enum\AttributeType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class AttributeFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class)
            ->add('description', TextareaType::class, [
                'required' => false,
            ])
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'placeholder' => 'Choose a category',
            ])
            ->add('type', ChoiceType::class, [
                'choices' => array_combine(
                    array_map(static fn (AttributeType $type) => $type->name, AttributeType::cases()),
                    AttributeType::values(),
                ),
            ])
            ->add('optionsText', TextareaType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'Choices (one per line, for Choice type)',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Attribute::class,
        ]);
    }
}
