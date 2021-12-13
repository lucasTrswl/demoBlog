<?php

namespace App\Form;

use App\Entity\Category;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class CategoryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre de la catégorie :',
                'required' => false,
                'attr' => [
                    'placeholder' =>"Saisir le contenue de l'article"
                    ],
                    "constraints" => [
                    new NotBlank([
                        'message' =>'Merci de saisir le nom de la catégorie.'
                    ]),
                    new Length([
                        'max' => 15,
                        'maxMessage' =>"Titre de catégorie trop longue (15 caractère max)"
                    ])
                    ]
                ])
                
            ->add('description', TextareaType::class, [
                'label' => 'Contenu de l\'article :',
                'required' => false,
                'attr' => [
                    'placeholder' =>"Saisir le contenue de l'article",
                    'rows' => 10
                ],
                "constraints" => [
                    new NotBlank([
                        'message' =>'Merci de saisir la description de la catégorie.'
                    ])
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Category::class,
        ]);
    }
}
