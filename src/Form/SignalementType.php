<?php

namespace App\Form;

use App\Entity\Signalement;
use App\Enum\MotifSignalement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class SignalementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('motif', ChoiceType::class, ['choices' => MotifSignalement::cases(), 'choice_label' => static fn (MotifSignalement $motif) => $motif->label(), 'choice_value' => static fn (?MotifSignalement $motif) => $motif?->value])->add('details', TextareaType::class, ['required' => false, 'label' => 'Précisions', 'attr' => ['rows' => 5, 'maxlength' => 1000], 'help' => 'Explique brièvement le problème pour aider la modération.'])->add('envoyer', SubmitType::class, ['label' => 'Envoyer le signalement', 'attr' => ['class' => 'btn btn-danger']]);
    }
    public function configureOptions(OptionsResolver $resolver): void { $resolver->setDefaults(['data_class' => Signalement::class]); }
}
