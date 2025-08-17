<?php

namespace App\Form;

use App\Entity\Enderecos;
use App\Entity\Logradouro;
use App\Entity\TipoEndereco;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EnderecoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('logradouro', EntityType::class, [
                'class' => Logradouro::class,
                'choice_label' => 'nome',
                'label' => 'Logradouro',
                'attr' => ['class' => 'form-select']
            ])
            ->add('tipo', EntityType::class, [
                'class' => TipoEndereco::class,
                'choice_label' => 'tipo',
                'label' => 'Tipo de Endereço',
                'attr' => ['class' => 'form-select']
            ])
            ->add('endNumero', IntegerType::class, [
                'label' => 'Número',
                'attr' => ['class' => 'form-control']
            ])
            ->add('complemento', TextType::class, [
                'label' => 'Complemento',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Enderecos::class,
        ]);
    }
}
