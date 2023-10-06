<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DiscussionFilterFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('filter', ChoiceType::class, [
                'choices' => [
                    'Title ↑' => 'titreAsc',
                    'Title ↓' => 'titreDesc',
                    'Date ↑' => 'dateCreationAsc',
                    'Date ↓' => 'dateCreationDesc',
                ],
                'data' => 'dateCreationDesc',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
