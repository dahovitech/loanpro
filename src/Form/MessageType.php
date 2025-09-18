<?php

namespace App\Form;

use App\Entity\Message;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class MessageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('subject', TextType::class, [
                'label' => 'Sujet',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Sujet du message'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Le sujet ne peut pas être vide',
                    ]),
                    new Length([
                        'min' => 3,
                        'max' => 255,
                        'minMessage' => 'Le sujet doit faire au moins {{ limit }} caractères',
                        'maxMessage' => 'Le sujet ne peut pas dépasser {{ limit }} caractères',
                    ]),
                ],
            ])
            ->add('content', TextareaType::class, [
                'label' => 'Message',
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 5,
                    'placeholder' => 'Votre message...'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Le message ne peut pas être vide',
                    ]),
                    new Length([
                        'min' => 10,
                        'max' => 2000,
                        'minMessage' => 'Le message doit faire au moins {{ limit }} caractères',
                        'maxMessage' => 'Le message ne peut pas dépasser {{ limit }} caractères',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Message::class,
        ]);
    }
}