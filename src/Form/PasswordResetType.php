<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class PasswordResetType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'first_options' => [
                    'label' => 'password_reset.form.new_password.label',
                    'attr' => [
                        'placeholder' => 'password_reset.form.new_password.placeholder',
                        'class' => 'form-control'
                    ]
                ],
                'second_options' => [
                    'label' => 'password_reset.form.confirm_password.label',
                    'attr' => [
                        'placeholder' => 'password_reset.form.confirm_password.placeholder',
                        'class' => 'form-control'
                    ]
                ],
                'invalid_message' => 'password_reset.form.password.mismatch',
                'constraints' => [
                    new NotBlank([
                        'message' => 'password_reset.form.password.required'
                    ]),
                    new Length([
                        'min' => 8,
                        'minMessage' => 'password_reset.form.password.min_length',
                        'max' => 255,
                        'maxMessage' => 'password_reset.form.password.max_length'
                    ]),
                    new Regex([
                        'pattern' => '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/',
                        'message' => 'password_reset.form.password.strength'
                    ])
                ]
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'password_reset.form.reset_password',
                'attr' => ['class' => 'btn btn-primary w-100']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}