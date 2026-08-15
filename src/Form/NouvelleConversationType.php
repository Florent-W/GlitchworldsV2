<?php

namespace App\Form;

use App\Entity\Utilisateur;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Form\FormBuilderInterface;

final class NouvelleConversationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('destinataire', EntityType::class, ['class' => Utilisateur::class, 'choice_label' => 'pseudo', 'placeholder' => 'Choisir un membre'])
            ->add('contenu', TextareaType::class, ['attr' => ['rows' => 4, 'maxlength' => 2000]])
            ->add('fichier', FileType::class, ['mapped' => false, 'required' => false, 'constraints' => [new File(maxSize: '5M', mimeTypes: ['image/jpeg', 'image/png', 'image/webp', 'application/pdf', 'text/plain'])]]);
    }
}
