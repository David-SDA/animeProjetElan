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
        /* On récupère l'année actuelle sous forme d'entier */
        $currentYear = (int) date('Y');
        /* Et on crée un tableau avec les années allant de cette année + 2 à 1940 */
        $years = array_reverse(range(1940, $currentYear + 2));

        $builder
            ->add('search', SearchType::class, [
                'required' => false,
                'attr' => [
                    'placeholder' => 'Search'
                ]
            ])
            ->add('season', ChoiceType::class, [
                'required' => false,
                'choices' => [
                    'Winter' => 'WINTER',
                    'Spring' => 'SPRING',
                    'Summer' => 'SUMMER',
                    'Fall' => 'FALL',
                ],
                'placeholder' => 'Any',
            ])
            ->add('seasonYear', ChoiceType::class, [
                'required' => false,
                'choices' => array_combine($years, $years),
                'placeholder' => 'Any',
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
                ],
                'placeholder' => 'Any',
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
                ],
                'placeholder' => 'Any',
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
