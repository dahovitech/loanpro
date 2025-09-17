<?php

namespace App\Form;

use App\Entity\Service;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ServiceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('slug', TextType::class, [
                'label' => 'forms.service.slug.label',
                'help' => 'forms.service.slug.help',
                'translation_domain' => 'admin',
                'required' => false,
                'attr' => [
                    'placeholder' => 'forms.service.slug.placeholder',
                    'maxlength' => 255,
                    'pattern' => '[a-z0-9\-]+',
                    'title' => 'Slug (lettres minuscules, chiffres et tirets uniquement)',
                ],
                'constraints' => [
                    new Assert\Length(['max' => 255]),
                    new Assert\Regex([
                        'pattern' => '/^[a-z0-9\-]*$/',
                        'message' => 'Le slug ne peut contenir que des lettres minuscules, chiffres et tirets'
                    ])
                ]
            ])
            ->add('isActive', CheckboxType::class, [
                'label' => 'forms.service.is_active.label',
                'help' => 'forms.service.is_active.help',
                'translation_domain' => 'admin',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input',
                ]
            ])
            ->add('sortOrder', IntegerType::class, [
                'label' => 'forms.service.sort_order.label',
                'help' => 'forms.service.sort_order.help',
                'translation_domain' => 'admin',
                'attr' => [
                    'min' => 0,
                    'step' => 1,
                    'class' => 'form-control',
                ],
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Range(['min' => 0, 'max' => 9999])
                ]
            ])
            ->add('translations', CollectionType::class, [
                'entry_type' => ServiceTranslationType::class,
                'entry_options' => [
                    'label' => false,
                    'in_collection' => true,
                ],
                'allow_add' => false,
                'allow_delete' => false,
                'by_reference' => false,
                'label' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Service::class,
        ]);
    }
}
