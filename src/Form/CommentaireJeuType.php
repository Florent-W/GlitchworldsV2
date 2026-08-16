<?php

namespace App\Form;

use App\Entity\CommentaireJeu;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class CommentaireJeuType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('contenu', TextareaType::class, [
                'label' => false,
                'help' => 'Entre 3 et 1 000 caractères.',
                'help_attr' => ['class' => 'form-text gw-text-violet'],
                'attr' => [
                    'rows' => 4,
                    'maxlength' => 1000,
                    'placeholder' => 'Partage ton avis sur ce jeu...',
                ],
            ])
            ->add('publier', SubmitType::class, [
                'label' => $options['bouton_libelle'],
                'attr' => ['class' => 'btn btn-primary'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CommentaireJeu::class,
            'bouton_libelle' => 'Publier',
        ]);
        $resolver->setAllowedTypes('bouton_libelle', 'string');
    }
}
