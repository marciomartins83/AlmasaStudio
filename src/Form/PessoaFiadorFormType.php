<?php

namespace App\Form;

use App\Entity\Pessoas;
use App\Entity\PessoasFiadores;
use App\Entity\FormasRetirada;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class PessoaFiadorFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // Campo oculto para ID da pessoa (quando selecionada na busca)
            ->add('pessoaId', HiddenType::class, [
                'required' => false,
                'mapped' => false,
                'attr' => ['id' => 'form_pessoaId']
            ])
            
            // Campos de Pessoa
            ->add('nome', TextType::class, [
                'label' => 'Nome Completo',
                'attr' => ['class' => 'form-control', 'id' => 'form_nome']
            ])
            
            ->add('searchTerm', TextType::class, [
                'label' => 'CPF/CNPJ',
                'required' => false,
                'mapped' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'CPF ou CNPJ da pessoa',
                    'readonly' => true,
                    'id' => 'form_searchTerm'
                ]
            ])
            
            ->add('dataNascimento', DateType::class, [
                'label' => 'Data de Nascimento',
                'widget' => 'single_text',
                'required' => false,
                'attr' => ['class' => 'form-control', 'id' => 'form_dataNascimento']
            ])
            
            ->add('estadoCivil', EntityType::class, [
                'class' => \App\Entity\EstadoCivil::class,
                'choice_label' => 'nome',
                'label' => 'Estado Civil',
                'required' => false,
                'placeholder' => 'Selecione...',
                'attr' => ['class' => 'form-select', 'id' => 'form_estadoCivil']
            ])
            
            ->add('nacionalidade', EntityType::class, [
                'class' => \App\Entity\Nacionalidade::class,
                'choice_label' => 'nome',
                'label' => 'Nacionalidade',
                'required' => false,
                'placeholder' => 'Selecione...',
                'attr' => ['class' => 'form-select', 'id' => 'form_nacionalidade']
            ])
            
            ->add('naturalidade', EntityType::class, [
                'class' => \App\Entity\Naturalidade::class,
                'choice_label' => 'nome',
                'label' => 'Naturalidade',
                'required' => false,
                'placeholder' => 'Selecione...',
                'attr' => ['class' => 'form-select', 'id' => 'form_naturalidade']
            ])
            
            ->add('nomePai', TextType::class, [
                'label' => 'Nome do Pai',
                'required' => false,
                'attr' => ['class' => 'form-control', 'id' => 'form_nomePai']
            ])
            
            ->add('nomeMae', TextType::class, [
                'label' => 'Nome da Mãe',
                'required' => false,
                'attr' => ['class' => 'form-control', 'id' => 'form_nomeMae']
            ])
            
            ->add('renda', NumberType::class, [
                'label' => 'Renda',
                'required' => false,
                'attr' => ['class' => 'form-control', 'step' => '0.01', 'id' => 'form_renda']
            ])
            
            ->add('observacoes', TextareaType::class, [
                'label' => 'Observações',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 3, 'id' => 'form_observacoes']
            ])
            
            // Dados do Fiador
            ->add('valorLimite', NumberType::class, [
                'label' => 'Valor Limite da Fiança',
                'required' => false,
                'attr' => ['class' => 'form-control', 'step' => '0.01', 'id' => 'form_valorLimite']
            ])
            
            ->add('tipoGarantia', ChoiceType::class, [
                'label' => 'Tipo de Garantia',
                'required' => false,
                'choices' => [
                    'Fiança Simples' => 'simples',
                    'Fiança Solidária' => 'solidaria',
                    'Caução' => 'caucao',
                    'Depósito Caução' => 'deposito',
                    'Seguro Fiança' => 'seguro'
                ],
                'placeholder' => 'Selecione...',
                'attr' => ['class' => 'form-select', 'id' => 'form_tipoGarantia']
            ])
            
            ->add('percentualGarantia', NumberType::class, [
                'label' => 'Percentual de Garantia (%)',
                'required' => false,
                'attr' => ['class' => 'form-control', 'step' => '0.01', 'max' => '100', 'id' => 'form_percentualGarantia']
            ])
            
            ->add('situacao', ChoiceType::class, [
                'label' => 'Situação',
                'required' => false,
                'choices' => [
                    'Ativo' => 'ativo',
                    'Inativo' => 'inativo',
                    'Pendente' => 'pendente',
                    'Suspenso' => 'suspenso'
                ],
                'placeholder' => 'Selecione...',
                'attr' => ['class' => 'form-select', 'id' => 'form_situacao']
            ])
            
            ->add('observacoesFiador', TextareaType::class, [
                'label' => 'Observações do Fiador',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 3, 'id' => 'form_observacoesFiador']
            ])
            
            // Campo Cônjuge
            ->add('conjuge', EntityType::class, [
                'class' => \App\Entity\Pessoas::class,
                'choice_label' => 'nome',
                'label' => 'Cônjuge',
                'required' => false,
                'placeholder' => 'Selecione o cônjuge...',
                'attr' => ['class' => 'form-select', 'id' => 'form_conjuge']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null, // Não vincular a nenhuma entidade específica
        ]);
    }
}
