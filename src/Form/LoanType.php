<?php

namespace App\Form;

use App\Entity\Loan;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class LoanType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'label' => 'Prénom',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Votre prénom'
                ]
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Nom',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Votre nom'
                ]
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'votre.email@exemple.com'
                ]
            ])
            ->add('phone', TelType::class, [
                'label' => 'Téléphone',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '+33 1 23 45 67 89'
                ]
            ])
            ->add('address', TextareaType::class, [
                'label' => 'Adresse complète',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Votre adresse complète',
                    'rows' => 3
                ],
                'constraints' => [
                    new Assert\NotBlank(message: 'L\'adresse est obligatoire.')
                ]
            ])
            ->add('profession', TextType::class, [
                'label' => 'Profession',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Votre profession'
                ]
            ])
            ->add('employer', TextType::class, [
                'label' => 'Employeur',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Nom de votre employeur'
                ]
            ])
            ->add('amount', MoneyType::class, [
                'label' => 'Montant souhaité',
                'currency' => 'EUR',
                'attr' => [
                    'class' => 'form-control',
                    'min' => '1000',
                    'max' => '100000',
                    'step' => '100'
                ],
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Range([
                        'min' => 1000,
                        'max' => 100000,
                        'notInRangeMessage' => 'Le montant doit être entre {{ min }}€ et {{ max }}€.'
                    ])
                ]
            ])
            ->add('duration', IntegerType::class, [
                'label' => 'Durée (en mois)',
                'attr' => [
                    'class' => 'form-control',
                    'min' => '6',
                    'max' => '120'
                ],
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Range([
                        'min' => 6,
                        'max' => 120,
                        'notInRangeMessage' => 'La durée doit être entre {{ min }} et {{ max }} mois.'
                    ])
                ]
            ])
            ->add('purpose', ChoiceType::class, [
                'label' => 'Objectif du prêt',
                'choices' => [
                    'Achat immobilier' => 'real_estate',
                    'Rénovation' => 'renovation',
                    'Achat véhicule' => 'vehicle',
                    'Consolidation de dettes' => 'debt_consolidation',
                    'Projet personnel' => 'personal_project',
                    'Investissement' => 'investment',
                    'Autre' => 'other'
                ],
                'attr' => [
                    'class' => 'form-select'
                ]
            ])
            ->add('monthlyIncome', MoneyType::class, [
                'label' => 'Revenus mensuels nets',
                'currency' => 'EUR',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Vos revenus mensuels nets'
                ],
                'constraints' => [
                    new Assert\NotBlank(message: 'Les revenus mensuels sont obligatoires.'),
                    new Assert\Positive(message: 'Les revenus doivent être positifs.')
                ]
            ])
            ->add('monthlyCharges', MoneyType::class, [
                'label' => 'Charges mensuelles',
                'currency' => 'EUR',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Vos charges mensuelles (loyer, crédit en cours...)'
                ],
                'help' => 'Incluez loyer, crédits en cours, charges fixes...'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Loan::class,
        ]);
    }
}
