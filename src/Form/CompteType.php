<?php

namespace App\Form;

use App\Entity\Utilisateur;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
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
            ])
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
