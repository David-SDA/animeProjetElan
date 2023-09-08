<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;

class ChangeEmailFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('currentEmail', EmailType::class, [
                'mapped' => false,
                'attr' => [
                    'value' => $options['currentEmail'],
                    'readonly' => true,
                ],
            ])
            ->add('newEmail', RepeatedType::class, [
                'mapped' => false,
                'type' => EmailType::class,
                'invalid_message' => 'The email fields must match.',
                'options' => ['attr' => ['class' => 'email-field']],
                'required' => true,
                'first_options'  => ['label' => 'Email'],
                'second_options' => ['label' => 'Confirm Email'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('currentEmail');
    }
}
