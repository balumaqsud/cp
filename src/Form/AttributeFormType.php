<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Attribute;
use App\Entity\Category;
use App\Enum\AttributeType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

final class AttributeFormType extends AbstractType
{
    public function __construct(
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'attribute.name',
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
                'label' => 'attribute.description',
            ])
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'placeholder' => 'attribute.category_placeholder',
                'label' => 'attribute.category',
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'attribute.type',
                'choices' => [
                    'attribute.types.string' => AttributeType::String->value,
                    'attribute.types.text' => AttributeType::Text->value,
                    'attribute.types.numeric' => AttributeType::Numeric->value,
                    'attribute.types.boolean' => AttributeType::Boolean->value,
                    'attribute.types.date' => AttributeType::Date->value,
                    'attribute.types.period' => AttributeType::Period->value,
                    'attribute.types.choice' => AttributeType::Choice->value,
                    'attribute.types.image' => AttributeType::Image->value,
                ],
            ])
            ->add('optionsText', TextareaType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'attribute.options',
            ])
            ->add('version', HiddenType::class, [
                'mapped' => false,
                'required' => false,
            ]);

        $builder->addEventListener(FormEvents::POST_SET_DATA, $this->fillOptionsText(...));
        $builder->addEventListener(FormEvents::SUBMIT, $this->syncOptions(...));
        $builder->addEventListener(FormEvents::POST_SUBMIT, $this->requireChoiceOptions(...));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Attribute::class,
        ]);
    }

    private function fillOptionsText(FormEvent $event): void
    {
        $attribute = $event->getData();
        $form = $event->getForm();
        if (!$attribute instanceof Attribute) {
            return;
        }

        $form->get('optionsText')->setData(implode("\n", $attribute->getOptions()));
        $form->get('version')->setData((string) $attribute->getVersion());
    }

    private function syncOptions(FormEvent $event): void
    {
        $attribute = $event->getData();
        if (!$attribute instanceof Attribute) {
            return;
        }

        $lines = preg_split('/\R/u', (string) $event->getForm()->get('optionsText')->getData()) ?: [];
        $options = array_values(array_filter(array_map(trim(...), $lines), static fn (string $line) => $line !== ''));

        $attribute->setOptions(
            $attribute->getType() === AttributeType::Choice->value ? $options : [],
        );
    }

    private function requireChoiceOptions(FormEvent $event): void
    {
        $attribute = $event->getData();
        $form = $event->getForm();
        if (!$attribute instanceof Attribute) {
            return;
        }

        if ($attribute->getType() === AttributeType::Choice->value && $attribute->getOptions() === []) {
            $form->get('optionsText')->addError(
                new FormError($this->translator->trans('attribute.options_required')),
            );
        }
    }
}
