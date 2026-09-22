<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Position;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class PositionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'position.title',
            ])
            ->add('shortDescription', TextareaType::class, [
                'required' => false,
                'label' => 'position.description',
            ])
            ->add('company', TextType::class, [
                'required' => false,
                'label' => 'position.company',
            ])
            ->add('level', ChoiceType::class, [
                'required' => false,
                'placeholder' => 'position.level_any',
                'label' => 'position.level',
                'choices' => [
                    'Junior' => 'Junior',
                    'Middle' => 'Middle',
                    'Senior' => 'Senior',
                    'C-level' => 'C-level',
                ],
                'choice_translation_domain' => false,
            ])
            ->add('isPublic', CheckboxType::class, [
                'required' => false,
                'label' => 'position.public_help',
            ])
            ->add('projectTagsText', TextType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'position.project_tags',
            ])
            ->add('maxProjects', IntegerType::class, [
                'required' => false,
                'label' => 'position.max_projects',
            ])
            ->add('version', HiddenType::class, [
                'mapped' => false,
                'required' => false,
            ]);

        $builder->addEventListener(FormEvents::POST_SET_DATA, $this->fillUnmapped(...));
        $builder->addEventListener(FormEvents::SUBMIT, $this->syncTags(...));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Position::class,
            'allow_extra_fields' => true,
        ]);
    }

    private function fillUnmapped(FormEvent $event): void
    {
        $position = $event->getData();
        $form = $event->getForm();
        if (!$position instanceof Position) {
            return;
        }

        $form->get('projectTagsText')->setData(implode(', ', $position->getProjectTags()));
        $form->get('version')->setData((string) $position->getVersion());
    }

    private function syncTags(FormEvent $event): void
    {
        $position = $event->getData();
        if (!$position instanceof Position) {
            return;
        }

        $raw = (string) $event->getForm()->get('projectTagsText')->getData();
        $tags = array_values(array_unique(array_filter(
            array_map(trim(...), explode(',', $raw)),
            static fn (string $tag): bool => $tag !== '',
        )));

        $position->setProjectTags($tags);
    }
}
