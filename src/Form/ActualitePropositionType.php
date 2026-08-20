<?php

namespace App\Form;

use App\Entity\Actualite;
use App\Entity\Jeu;
use App\Enum\CategorieActualite;
use App\Enum\StatutJeu;
use App\Repository\JeuRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

final class ActualitePropositionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', null, ['label' => 'Titre'])
            ->add('description', TextareaType::class, [
                'label' => 'Description courte',
                'help' => 'Utilisée dans la liste et pour le référencement (160 caractères maximum).',
                'attr' => ['rows' => 3, 'maxlength' => 160],
            ])
            ->add('contenu', TextareaType::class, [
                'label' => 'Contenu',
                'help' => 'Tu peux utiliser le BBCode pour mettre en forme l’article.',
                'attr' => ['rows' => 18],
            ])
            ->add('banniereFichier', FileType::class, [
                'label' => 'Bannière',
                'mapped' => false,
                'required' => false,
                'help' => 'Image large affichée en haut de l’article. JPG, PNG ou WebP, 5 Mo maximum.',
                'constraints' => [new Assert\Image(
                    maxSize: '5M',
                    mimeTypes: ['image/jpeg', 'image/png', 'image/webp'],
                    mimeTypesMessage: 'Choisis une image JPG, PNG ou WebP.',
                )],
            ])
            ->add('miniatureFichier', FileType::class, [
                'label' => 'Miniature',
                'mapped' => false,
                'required' => false,
                'help' => 'Vignette affichée dans les listes. JPG, PNG ou WebP, 5 Mo maximum.',
                'constraints' => [new Assert\Image(
                    maxSize: '5M',
                    mimeTypes: ['image/jpeg', 'image/png', 'image/webp'],
                    mimeTypesMessage: 'Choisis une image JPG, PNG ou WebP.',
                )],
            ])
            ->add('jeux', EntityType::class, [
                'class' => Jeu::class,
                'choice_label' => 'nom',
                'query_builder' => static fn (JeuRepository $repository) => $repository->createQueryBuilder('jeu')
                    ->andWhere('jeu.statut = :statut')
                    ->setParameter('statut', StatutJeu::Approuve)
                    ->orderBy('jeu.nom', 'ASC'),
                'multiple' => true,
                'required' => false,
                'label' => 'Jeux concernés',
                'help' => 'Maintiens Ctrl pour sélectionner plusieurs jeux.',
                'attr' => ['size' => 8],
            ])
            ->add('categorie', EnumType::class, [
                'class' => CategorieActualite::class,
                'choice_label' => static fn (CategorieActualite $categorie): string => $categorie->label(),
                'label' => 'Catégorie',
            ])
            ->add('envoyer', SubmitType::class, [
                'label' => $options['bouton_libelle'],
                'attr' => ['class' => 'btn btn-primary btn-lg'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Actualite::class,
            'bouton_libelle' => 'Envoyer pour validation',
        ]);
        $resolver->setAllowedTypes('bouton_libelle', 'string');
    }
}
