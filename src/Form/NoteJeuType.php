<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
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
            ->add('enregistrer', SubmitType::class, [
                'label' => 'Enregistrer ma note',
                'attr' => ['class' => 'btn btn-primary'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => null]);
    }
}
