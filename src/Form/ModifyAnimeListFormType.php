<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Validator\Constraints\Range;

class ModifyAnimeListFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('startedWatching', DateType::class, [
                'widget' => 'single_text',
                'required' => false,
                'data' => $options['startDate'],
            ])
            ->add('endedWatching', DateType::class, [
                'widget' => 'single_text',
                'required' => false,
                'data' => $options['endDate'],
            ])
            ->add('status', ChoiceType::class, [
                'required' => true,
                'choices' => [
                    'Watching' => 'Watching',
                    'Completed' => 'Completed',
                    'Plan to watch' => 'Plan to watch',
                ],
                'data' => $options['status'],
            ])
            ->add('nbEpisodesWatched', NumberType::class, [
                'html5' => true,
                'attr' => [
                    'min' => 0,
                    'max' => $options['maxEpisodes']
                ],
                'constraints' => [
                    new Range([
                        'min' => 0,
                        'max'=> $options['maxEpisodes']
                    ])
                ],
                'data' => $options['numberEpisodes'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'maxEpisodes' => null,
            'startDate' => null,
            'endDate' => null,
            'status' => "Plan to watch",
            'numberEpisodes' => null,
        ]);
    }
}
