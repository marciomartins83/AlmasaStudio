<?php

namespace App\Form;

use App\Entity\PessoasPretendentes;
use App\Entity\TiposImoveis;
use App\Entity\Logradouros;
use App\Entity\Users;
use App\Entity\TiposAtendimento;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PessoaPretendenteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('tipoImovel', EntityType::class, [
                'class' => TiposImoveis::class,
                'choice_label' => 'tipo',
                'label' => 'Tipo de Imóvel Desejado',
                'placeholder' => 'Selecione...',
                'required' => false,
            ])
            ->add('quartosDesejados', IntegerType::class, [
                'label' => 'Quartos Desejados',
                'required' => false,
            ])
            ->add('aluguelMaximo', MoneyType::class, [
                'label' => 'Aluguel Máximo',
                'currency' => 'BRL',
                'required' => false,
            ])
            ->add('logradouroDesejado', EntityType::class, [
                'class' => Logradouros::class,
                'choice_label' => 'logradouro',
                'label' => 'Logradouro Desejado',
                'placeholder' => 'Selecione...',
                'required' => false,
            ])
            ->add('disponivel', CheckboxType::class, [
                'label' => 'Disponível para contato?',
                'required' => false,
            ])
            ->add('procuraAluguel', CheckboxType::class, [
                'label' => 'Procura Aluguel',
                'required' => false,
            ])
            ->add('procuraCompra', CheckboxType::class, [
                'label' => 'Procura Compra',
                'required' => false,
            ])
            ->add('atendente', EntityType::class, [
                'class' => Users::class,
                'choice_label' => 'name', // or 'email' depending on what's best
                'label' => 'Atendente',
                'placeholder' => 'Selecione...',
                'required' => false,
            ])
            ->add('tipoAtendimento', EntityType::class, [
                'class' => TiposAtendimento::class,
                'choice_label' => 'tipo',
                'label' => 'Tipo de Atendimento',
                'placeholder' => 'Selecione...',
                'required' => false,
            ])
            ->add('dataCadastro', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Data de Cadastro',
                'required' => false,
            ])
            ->add('observacoes', TextareaType::class, [
                'label' => 'Observações',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PessoasPretendentes::class,
        ]);
    }
}