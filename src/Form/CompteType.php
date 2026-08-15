<?php

namespace App\Form;

use App\Entity\Utilisateur;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

final class CompteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('pseudo', null, [
                'label' => 'Nom d’utilisateur',
                'constraints' => [
                    new Assert\NotBlank(normalizer: 'trim'),
                    new Assert\Length(min: 3, max: 50, normalizer: 'trim'),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Adresse e-mail',
                'help' => 'Elle servira aussi d’identifiant de connexion.',
                'constraints' => [
                    new Assert\NotBlank(message: 'Ajoute une adresse e-mail.'),
                    new Assert\Email(message: 'Cette adresse e-mail n’est pas valide.'),
                ],
            ])
            ->add('biographie', TextareaType::class, ['required' => false, 'label' => 'À propos de moi', 'attr' => ['rows' => 5, 'maxlength' => 500]])
            ->add('localisation', null, ['required' => false, 'label' => 'Localisation'])
            ->add('statutProfil', null, ['required' => false, 'label' => 'Statut', 'help' => 'Une phrase courte affichée sur ton profil.'])
            ->add('dateNaissance', DateType::class, ['required' => false, 'label' => 'Date de naissance', 'widget' => 'single_text', 'input' => 'datetime_immutable'])
            ->add('avatarFichier', FileType::class, ['mapped' => false, 'required' => false, 'label' => 'Avatar', 'constraints' => [new File(maxSize: '5M', mimeTypes: ['image/jpeg', 'image/png', 'image/webp'])]])
            ->add('banniereFichier', FileType::class, ['mapped' => false, 'required' => false, 'label' => 'Bannière', 'constraints' => [new File(maxSize: '8M', mimeTypes: ['image/jpeg', 'image/png', 'image/webp'])]])
            ->add('enregistrer', SubmitType::class, [
                'label' => 'Enregistrer les modifications',
                'attr' => ['class' => 'btn btn-primary'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Utilisateur::class]);
    }
}
