<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

final class NoteJeuType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('note', ChoiceType::class, [
                'label' => 'Ta note',
                'choices' => ['1 étoile' => 1, '2 étoiles' => 2, '3 étoiles' => 3, '4 étoiles' => 4, '5 étoiles' => 5],
                'expanded' => true,
                'constraints' => [new Assert\Choice(choices: [1, 2, 3, 4, 5])],
            ])
            ->add('contenu', TextareaType::class, [
                'label' => 'Ton avis (facultatif)',
                'required' => false,
                'attr' => [
                    'rows' => 4,
                    'maxlength' => 1500,
                    'placeholder' => 'Qu’as-tu pensé de ce jeu ?',
                ],
                'constraints' => [new Assert\Length(max: 1500)],
            ])
            ->add('enregistrer', SubmitType::class, [
                'label' => 'Enregistrer mon avis',
                'attr' => ['class' => 'btn btn-primary'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => null]);
    }
}
