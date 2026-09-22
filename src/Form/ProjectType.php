<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Project;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ProjectType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'project.name',
            ])
            ->add('startDate', DateType::class, [
                'widget' => 'single_text',
                'label' => 'project.start',
            ])
            ->add('endDate', DateType::class, [
                'widget' => 'single_text',
                'required' => false,
                'label' => 'project.end',
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
                'label' => 'project.description',
            ])
            ->add('tagsText', TextType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'project.tags',
                'attr' => [
                    'list' => 'project-tag-suggestions',
                    'autocomplete' => 'off',
                ],
            ]);

        $builder->addEventListener(FormEvents::POST_SET_DATA, $this->fillTagsText(...));
        $builder->addEventListener(FormEvents::SUBMIT, $this->syncTags(...));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Project::class,
        ]);
    }

    private function fillTagsText(FormEvent $event): void
    {
        $project = $event->getData();
        $form = $event->getForm();
        if (!$project instanceof Project) {
            return;
        }

        $form->get('tagsText')->setData(implode(', ', $project->getTechnologyTags()));
    }

    private function syncTags(FormEvent $event): void
    {
        $project = $event->getData();
        if (!$project instanceof Project) {
            return;
        }

        $raw = (string) $event->getForm()->get('tagsText')->getData();
        $tags = array_values(array_unique(array_filter(
            array_map(trim(...), explode(',', $raw)),
            static fn (string $tag): bool => $tag !== '',
        )));

        $project->setTechnologyTags($tags);
    }
}
