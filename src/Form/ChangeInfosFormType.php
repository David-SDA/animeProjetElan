<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class ChangeInfosFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('dateNaissance', DateType::class,[
                'widget' => 'single_text',
                'required' => false,
                'data' => $options['currentDateOfBirth'],
            ])
            ->add('pays', TextType::class, [
                'required' => false,
                'attr' => [
                    'value' => $options['currentCountry'],
                ],
            ])
            ->add('ville', TextType::class, [
                'required' => false,
                'attr' => [
                    'value' => $options['currentCity'],
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'currentDateOfBirth' => null,
            'currentCountry' => null,
            'currentCity' => null,
        ]);
    }
}
