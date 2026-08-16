<?php

namespace App\Form;

use App\Entity\CategorieJeu;
use App\Entity\Genre;
use App\Entity\Jeu;
use App\Entity\Langue;
use App\Entity\Plateforme;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

final class JeuPropositionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', null, ['label' => 'Titre du jeu'])
            ->add('description', TextareaType::class, [
                'label' => 'Description courte',
                'attr' => ['maxlength' => 160, 'rows' => 3],
                'help' => '160 caractères maximum.',
            ])
            ->add('contenu', TextareaType::class, [
                'label' => 'Présentation complète',
                'required' => false,
                'help' => 'Tu peux utiliser le BBCode pour mettre en forme la fiche.',
                'attr' => ['rows' => 18],
            ])
            ->add('developpeur', null, ['label' => 'Développeur ou équipe', 'required' => false])
            ->add('dateSortie', DateType::class, [
                'label' => 'Date de sortie',
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('categorie', EntityType::class, [
                'class' => CategorieJeu::class,
                'choice_label' => 'nom',
                'placeholder' => 'Choisir une catégorie',
            ])
            ->add('genres', EntityType::class, [
                'class' => Genre::class,
                'choice_label' => 'nom',
                'multiple' => true,
                'expanded' => true,
                'constraints' => [new Assert\Count(min: 1, minMessage: 'Choisis au moins un genre.')],
            ])
            ->add('plateformes', EntityType::class, [
                'class' => Plateforme::class,
                'choice_label' => 'nom',
                'multiple' => true,
                'expanded' => true,
                'constraints' => [new Assert\Count(min: 1, minMessage: 'Choisis au moins une plateforme.')],
            ])
            ->add('langues', EntityType::class, [
                'class' => Langue::class,
                'choice_label' => 'nom',
                'multiple' => true,
                'expanded' => true,
                'constraints' => [new Assert\Count(min: 1, minMessage: 'Choisis au moins une langue.')],
            ])
            ->add('envoyer', SubmitType::class, [
                'label' => $options['bouton_libelle'],
                'attr' => ['class' => 'btn btn-primary btn-lg'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Jeu::class,
            'bouton_libelle' => 'Envoyer pour validation',
        ]);
        $resolver->setAllowedTypes('bouton_libelle', 'string');
    }
}
