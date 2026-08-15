<?php

namespace App\Form;

use App\Entity\CommentaireActualite;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class CommentaireActualiteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('contenu', TextareaType::class, ['label' => false, 'attr' => ['rows' => 4, 'maxlength' => 1000, 'placeholder' => 'Réagis à cette actualité...']])
            ->add('publier', SubmitType::class, ['label' => $options['bouton_libelle'], 'attr' => ['class' => 'btn btn-primary']]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => CommentaireActualite::class, 'bouton_libelle' => 'Publier']);
        $resolver->setAllowedTypes('bouton_libelle', 'string');
    }
}
