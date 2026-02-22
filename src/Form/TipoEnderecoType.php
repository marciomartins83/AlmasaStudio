<?php

namespace App\Form;

use App\Entity\TiposEnderecos;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints as Assert;

class TipoEnderecoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('tipo', TextType::class, [
                'label' => 'Tipo de Endereço',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Campo obrigatório']),
                    new Assert\Length([
                        'min' => 2,
                        'max' => 255,
                        'minMessage' => 'Mínimo 2 caracteres',
                        'maxMessage' => 'Máximo 255 caracteres',
                    ]),
                ],
            ])
            // Add other fields if necessary based on the entity
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TiposEnderecos::class,
        ]);
    }
}
