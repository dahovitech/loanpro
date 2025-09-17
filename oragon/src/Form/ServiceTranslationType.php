<?php

namespace App\Form;

use App\Entity\ServiceTranslation;
use App\Entity\Language;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ServiceTranslationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'forms.service_translation.title.label',
                'translation_domain' => 'admin',
                'required' => false,
                'attr' => [
                    'placeholder' => 'forms.service_translation.title.placeholder',
                    'maxlength' => 255,
                    'class' => 'form-control',
                ],
                'constraints' => [
                    new Assert\Length(['max' => 255])
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'forms.service_translation.description.label',
                'translation_domain' => 'admin',
                'required' => false,
                'attr' => [
                    'rows' => 4,
                    'placeholder' => 'forms.service_translation.description.placeholder',
                    'class' => 'form-control',
                ],
                'constraints' => [
                    new Assert\Length(['max' => 1000])
                ]
            ])
            ->add('detail', TextareaType::class, [
                'label' => 'forms.service_translation.detail.label',
                'translation_domain' => 'admin',
                'required' => false,
                'attr' => [
                    'rows' => 8,
                    'placeholder' => 'forms.service_translation.detail.placeholder',
                    'class' => 'form-control',
                ],
                'constraints' => [
                    new Assert\Length(['max' => 5000])
                ]
            ])
        ;

        // Only add language field if we're not in a collection context
        if (!$options['in_collection']) {
            $builder->add('language', EntityType::class, [
                'class' => Language::class,
                'choice_label' => 'name',
                'label' => 'forms.service_translation.language.label',
                'translation_domain' => 'admin',
                'placeholder' => 'forms.service_translation.language.placeholder',
                'attr' => [
                    'class' => 'form-select',
                ],
                'constraints' => [
                    new Assert\NotNull()
                ]
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ServiceTranslation::class,
            'in_collection' => true, // By default, used in collection
        ]);
    }
}
