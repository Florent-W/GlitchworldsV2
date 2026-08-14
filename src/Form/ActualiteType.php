<?php

namespace App\Form;

use App\Entity\Actualite;
use App\Enum\CategorieActualite;
use App\Enum\StatutActualite;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ActualiteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', null, ['label' => 'Titre'])
            ->add('description', TextareaType::class, [
                'label' => 'Description courte',
                'help' => 'Utilisée dans la liste et pour le référencement.',
                'attr' => ['rows' => 3, 'maxlength' => 160],
            ])
            ->add('contenu', TextareaType::class, [
                'label' => 'Contenu',
                'attr' => ['rows' => 18],
            ])
            ->add('categorie', EnumType::class, [
                'class' => CategorieActualite::class,
                'choice_label' => static fn (CategorieActualite $categorie): string => $categorie->label(),
            ])
            ->add('statut', EnumType::class, [
                'class' => StatutActualite::class,
                'choice_label' => static fn (StatutActualite $statut): string => match ($statut) {
                    StatutActualite::Brouillon => 'Brouillon',
                    StatutActualite::EnAttente => 'En attente',
                    StatutActualite::Publiee => 'Publiée',
                },
            ])
            ->add('enregistrer', SubmitType::class, [
                'label' => $options['bouton_libelle'],
                'attr' => ['class' => 'btn btn-primary'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Actualite::class,
            'bouton_libelle' => 'Enregistrer',
        ]);
        $resolver->setAllowedTypes('bouton_libelle', 'string');
    }
}
