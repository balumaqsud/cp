<?php

declare(strict_types=1);

namespace App\Form;

use App\DTO\SupportTicketDTO;
use App\Enum\SupportPriority;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class SupportTicketType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('summary', TextareaType::class, [
                'label' => 'support.summary',
            ])
            ->add('priority', EnumType::class, [
                'class' => SupportPriority::class,
                'label' => 'support.priority',
                'choice_label' => static fn (SupportPriority $priority): string => 'support.priority.'.$priority->value,
                'choice_translation_domain' => 'messages',
            ])
            ->add('from', HiddenType::class, [
                'mapped' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SupportTicketDTO::class,
        ]);
    }
}
