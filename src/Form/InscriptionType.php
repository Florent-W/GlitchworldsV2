<?php

namespace App\Form;

use App\Entity\Utilisateur;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

final class InscriptionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('pseudo', null, [
                'label' => 'Nom d’utilisateur',
                'help' => 'Choisis le pseudo avec lequel tu te connecteras.',
                'constraints' => [new Assert\Length(min: 3, max: 50)],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Adresse e-mail',
                'help' => 'Elle sert aussi à te connecter et à récupérer ton mot de passe.',
            ])
            ->add('motDePasseClair', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'first_options' => ['label' => 'Mot de passe'],
                'second_options' => ['label' => 'Confirmer le mot de passe'],
                'invalid_message' => 'Les mots de passe ne correspondent pas.',
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(min: 12, max: 4096),
                ],
            ])
            ->add('creer', SubmitType::class, [
                'label' => 'Créer mon compte',
                'attr' => ['class' => 'btn btn-primary btn-lg w-100'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Utilisateur::class]);
    }
}
