<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SearchFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $currentYear = (int) date('Y');
        $years = array_reverse(range(1940, $currentYear + 2));

        $builder
            ->add('search', SearchType::class, [
                'required' => false,
            ])
            ->add('season', ChoiceType::class, [
                'required' => false,
                'choices' => [
                    'Winter' => 'Winter',
                    'Spring' => 'Spring',
                    'Summer' => 'Summer',
                    'Fall' => 'Fall',
                ],
            ])
            ->add('year', ChoiceType::class, [
                'required' => false,
                'choices' => array_combine($years, $years),
            ])
            ->add('genre', ChoiceType::class, [
                'required' => false,
                'choices' => [
                    'Action' => 'Action',
                    'Adventure' => 'Adventure',
                    'Comedy' => 'Comedy',
                    'Drama' => 'Drama',
                    'Ecchi' => 'Ecchi',
                    'Fantasy' => 'Fantasy',
                    'Horror' => 'Horror',
                    'Mahou Shoujo' => 'Mahou Shoujo',
                    'Mecha' => 'Mecha',
                    'Music' => 'Music',
                    'Mystery' => 'Mystery',
                    'Psychological' => 'Psychological',
                    'Romance' => 'Romance',
                    'Sci-Fi' => 'Sci-Fi',
                    'Slice of Life' => 'Slice of Life',
                    'Sports' => 'Sports',
                    'Supernatural' => 'Supernatural',
                    'Thriller' => 'Thriller',
                ]
            ])
            ->add('format', ChoiceType::class, [
                'required' => false,
                'choices' => [
                    'TV' => 'TV',
                    'TV Short' => 'TV_SHORT',
                    'Movie' => 'MOVIE',
                    'Special' => 'SPECIAL',
                    'OVA' => 'OVA',
                    'ONA' => 'ONA',
                    'Music' => 'MUSIC',
                ]
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
