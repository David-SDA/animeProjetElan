<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class ChangeUsernameFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username', TextType::class, [
                'attr' => [
                    'value' => $options['currentUsername'],
                ],
                'constraints' => [
                    new Length([
                        'min' => 4,
                        'max' => 40,
                        'minMessage' => 'Your username must be at least 4 characters long',
                        'maxMessage' => 'Your username cannot be longer than 40 characters long',
                    ])
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('currentUsername');
    }
}
