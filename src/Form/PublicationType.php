<?php

namespace App\Form;

use App\Entity\Publication;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class PublicationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('contenu', TextareaType::class, [
            'label' => false,
            'attr' => [
                'rows' => 2,
                'maxlength' => 1000,
                'placeholder' => 'Quoi de neuf dans la communauté ?',
            ],
        ])
        ->add('imageFichier', FileType::class, ['mapped' => false, 'required' => false, 'label' => 'Image', 'constraints' => [new File(maxSize: '5M', mimeTypes: ['image/jpeg', 'image/png', 'image/webp', 'image/gif'])]])
        ->add('lien', UrlType::class, ['required' => false, 'label' => 'Lien', 'attr' => ['placeholder' => 'https://…']])
        ->add('questionSondage', TextType::class, ['required' => false, 'label' => 'Question du sondage', 'attr' => ['maxlength' => 180]])
        ->add('optionsSondageTexte', TextareaType::class, [
            'mapped' => false,
            'required' => false,
            'label' => 'Choix du sondage',
            'help' => 'Un choix par ligne, de 2 à 6 choix.',
            'attr' => ['rows' => 6, 'placeholder' => "Premier choix\nDeuxième choix"],
            'constraints' => [
                new Callback(static function (mixed $valeur, ExecutionContextInterface $contexte): void {
                    $choix = preg_split('/\R/', (string) $valeur) ?: [];
                    $choix = array_unique(array_filter(array_map('trim', $choix)));

                    if (count($choix) > 6) {
                        $contexte->buildViolation('Un sondage ne peut pas contenir plus de 6 choix.')
                            ->addViolation();
                    }
                }),
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Publication::class]);
    }
}
