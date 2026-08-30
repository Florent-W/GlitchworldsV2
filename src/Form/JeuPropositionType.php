<?php

namespace App\Form;

use App\Entity\CategorieJeu;
use App\Entity\Genre;
use App\Entity\Jeu;
use App\Entity\Langue;
use App\Entity\Plateforme;
use App\Repository\CategorieJeuRepository;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
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
                'required' => false,
                'attr' => ['maxlength' => 160, 'rows' => 3],
                'help' => 'Facultative, 160 caractères maximum.',
            ])
            ->add('developpeur', null, [
                'label' => 'Développeur du jeu',
                'required' => false,
                'attr' => ['maxlength' => 160],
                'help' => 'Nom du studio, de l’équipe ou du développeur ayant créé le jeu.',
            ])
            ->add('contenu', TextareaType::class, [
                'label' => 'Contenu de la présentation',
                'required' => false,
                'help' => 'Tu peux utiliser le BBCode pour mettre en forme la fiche.',
                'attr' => ['rows' => 18],
            ])
            ->add('dateSortie', DateType::class, [
                'label' => 'Date de sortie',
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('categorie', EntityType::class, [
                'class' => CategorieJeu::class,
                'choice_label' => 'nom',
                'placeholder' => 'Choisir une catégorie',
                'query_builder' => static fn (CategorieJeuRepository $repository) => $repository->creerRequeteOrdonnee(),
            ])
            ->add('jeuxAssocies', EntityType::class, [
                'class' => Jeu::class,
                'choice_label' => 'nom',
                'multiple' => true,
                'required' => false,
                'label' => 'Jeux associés',
                'help' => 'Pour un mod, sélectionne le ou les jeux nécessaires ou concernés.',
                'query_builder' => static function (EntityRepository $repository) use ($options) {
                    $requete = $repository->createQueryBuilder('jeu')
                        ->andWhere('jeu.statut = :statut')
                        ->setParameter('statut', \App\Enum\StatutJeu::Approuve)
                        ->orderBy('jeu.nom', 'ASC');
                    $jeuCourant = $options['data'] ?? null;
                    if ($jeuCourant instanceof Jeu && $jeuCourant->getId() !== null) {
                        $requete->andWhere('jeu.id != :jeuCourant')->setParameter('jeuCourant', $jeuCourant->getId());
                    }

                    return $requete;
                },
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
            ->add('miniatureFichier', FileType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'Miniature',
                'help' => 'Utilisée dans les listes et comme jaquette sur la fiche. JPG, PNG, WebP ou GIF, 8 Mo maximum.',
                'attr' => ['accept' => 'image/jpeg,image/png,image/webp,image/gif'],
                'constraints' => [new Assert\Image(
                    maxSize: '8M',
                    mimeTypes: ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
                    mimeTypesMessage: 'Choisis une image JPG, PNG, WebP ou GIF.',
                )],
            ])
            ->add('banniereFichier', FileType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'Bannière',
                'help' => 'Affichée en haut de la fiche du jeu. JPG, PNG, WebP ou GIF, 8 Mo maximum.',
                'attr' => ['accept' => 'image/jpeg,image/png,image/webp,image/gif'],
                'constraints' => [new Assert\Image(
                    maxSize: '8M',
                    mimeTypes: ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
                    mimeTypesMessage: 'Choisis une image JPG, PNG, WebP ou GIF.',
                )],
            ])
            ->add('videoBackground', UrlType::class, [
                'label' => 'Vidéo en arrière-plan',
                'required' => false,
                'help' => 'URL YouTube de la vidéo utilisée en arrière-plan sur la fiche du jeu.',
                'attr' => [
                    'placeholder' => 'https://www.youtube.com/watch?v=...',
                ],
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
