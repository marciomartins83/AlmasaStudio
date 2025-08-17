<?php

namespace App\Form;

use App\Entity\TipoEndereco;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class TipoEnderecoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('tipo', TextType::class, [
                'label' => 'Tipo de Endereço',
            ])
            // Add other fields if necessary based on the entity
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TipoEndereco::class,
        ]);
    }
}
