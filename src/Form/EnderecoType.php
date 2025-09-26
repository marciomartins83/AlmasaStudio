<?php

namespace App\Form;

use App\Entity\Enderecos;
use App\Entity\TiposEnderecos;
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
            // Campos que o JS já preenche (não mapeados diretamente na entidade)
            ->add('cep', TextType::class, [
                'label' => 'CEP',
                'mapped' => false,
                'attr' => [
                    'class' => 'form-control cep-input',
                    'placeholder' => '00000-000',
                    'maxlength' => 9,
                    'data-mask' => '00000-000',
                ],
            ])
            ->add('logradouro', TextType::class, [
                'label' => 'Logradouro',
                'attr' => [
                    'class' => 'form-control logradouro-field',
                    'placeholder' => 'Rua, Avenida...',
                ],
            ])
            ->add('bairro', TextType::class, [
                'label' => 'Bairro',
                'attr' => [
                    'class' => 'form-control bairro-field',
                    'placeholder' => 'Nome do bairro',
                ],
            ])
            ->add('cidade', TextType::class, [
                'label' => 'Cidade',
                'attr' => [
                    'class' => 'form-control cidade-field',
                    'placeholder' => 'Nome da cidade',
                ],
            ])
            ->add('estado', TextType::class, [
                'label' => 'Estado',
                'mapped' => false,
                'attr' => [
                    'class' => 'form-control estado-field',
                    'maxlength' => 2,
                ],
            ])

            // Campos da entidade Enderecos (relacionamentos e dados locais)
            ->add('idTipoEndereco', EntityType::class, [
                'class' => TiposEnderecos::class,
                'choice_label' => 'tipo',
                'label' => 'Tipo de Endereço',
                'attr' => [
                    'class' => 'form-select tipo-endereco-select',
                ],
            ])
            ->add('endNumero', TextType::class, [
                'label' => 'Número',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '123',
                ],
            ])
            ->add('complemento', TextType::class, [
                'label' => 'Complemento',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Apto, Sala...',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Enderecos::class,
        ]);
    }
}