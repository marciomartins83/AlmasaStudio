<?php

namespace App\Form;

use App\Entity\EstadoCivil;
use App\Entity\Nacionalidade;
use App\Entity\Naturalidade;
use App\Entity\Pessoas;
use App\Form\ChavePixType;
use App\Form\DocumentoType;
use App\Form\EmailType;
use App\Form\EnderecoType;
use App\Form\TelefoneType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PessoaFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nome', TextType::class)
            ->add('dataNascimento', DateType::class, ['widget' => 'single_text'])
            ->add('estadoCivil', EntityType::class, [
                'class'        => EstadoCivil::class,
                'choice_label' => 'nome',
                'placeholder'  => 'Selecione...',
                'label'        => 'Estado Civil',
            ])
            ->add('nacionalidade', EntityType::class, [
                'class'        => Nacionalidade::class,
                'choice_label' => 'nome',
                'placeholder'  => 'Selecione...',
                'label'        => 'Nacionalidade',
            ])
            ->add('naturalidade', EntityType::class, [
                'class'        => Naturalidade::class,
                'choice_label' => 'nome',
                'placeholder'  => 'Selecione...',
                'label'        => 'Naturalidade',
            ])
            ->add('nomePai', TextType::class)
            ->add('nomeMae', TextType::class)
            ->add('renda', MoneyType::class, ['currency' => 'BRL'])
            ->add('observacoes', TextareaType::class, ['required' => false])

            /* Tipo de pessoa para carregar sub-formulário */
            ->add('tipoPessoa', ChoiceType::class, [
                'choices' => [
                    'Fiador'      => 'fiador',
                    'Corretor'    => 'corretor',
                    'Locador'     => 'locador',
                    'Locatário'   => 'locatario',
                    'Proprietário'=> 'proprietario',
                ],
                'attr' => ['data-url' => $options['sub_form_url']]
            ])

            /* Seções de contato: adicionar/remover linhas dinamicamente */
            ->add('telefones', CollectionType::class, [
                'entry_type'   => TelefoneType::class,
                'allow_add'    => true,
                'allow_delete' => true,
                'by_reference' => false,
                'prototype'    => true,
                'label'        => false,
            ])
            ->add('enderecos', CollectionType::class, [
                'entry_type'   => EnderecoType::class,
                'allow_add'    => true,
                'allow_delete' => true,
                'by_reference' => false,
                'prototype'    => true,
                'label'        => false,
            ])
            ->add('emails', CollectionType::class, [
                'entry_type'   => EmailType::class,
                'allow_add'    => true,
                'allow_delete' => true,
                'by_reference' => false,
                'prototype'    => true,
                'label'        => false,
            ])
            ->add('chavesPix', CollectionType::class, [
                'entry_type'   => ChavePixType::class,
                'allow_add'    => true,
                'allow_delete' => true,
                'by_reference' => false,
                'prototype'    => true,
                'label'        => false,
            ])
            ->add('documentos', CollectionType::class, [
                'entry_type'   => DocumentoType::class,
                'allow_add'    => true,
                'allow_delete' => true,
                'by_reference' => false,
                'prototype'    => true,
                'label'        => false,
            ])

            /* Campo oculto que receberá o ID do cônjuge (existente ou novo) */
            ->add('conjuge', HiddenType::class, ['required' => false])

            /* Campos NÃO-mapeados para busca / cadastro de novo cônjuge */
            ->add('conjugeSearch', TextType::class, [
                'mapped'   => false,
                'required' => false,
                'label'    => 'Cônjuge',
                'attr'     => ['placeholder' => 'Busque ou cadastre o cônjuge'],
            ])
            ->add('novoConjugeNome', TextType::class, ['mapped' => false, 'required' => false])
            ->add('novoConjugeCpf', TextType::class, ['mapped' => false, 'required' => false])
            ->add('novoConjugeDataNascimento', DateType::class, ['mapped' => false, 'required' => false, 'widget' => 'single_text'])
            ->add('novoConjugeEstadoCivil', EntityType::class, [
                'class'        => EstadoCivil::class,
                'choice_label' => 'nome',
                'mapped'       => false,
                'required'     => false,
                'placeholder'  => 'Selecione...',
            ])
            ->add('novoConjugeNacionalidade', EntityType::class, [
                'class'        => Nacionalidade::class,
                'choice_label' => 'nome',
                'mapped'       => false,
                'required'     => false,
                'placeholder'  => 'Selecione...',
            ])
            ->add('novoConjugeNaturalidade', EntityType::class, [
                'class'        => Naturalidade::class,
                'choice_label' => 'nome',
                'mapped'       => false,
                'required'     => false,
                'placeholder'  => 'Selecione...',
            ])
            ->add('novoConjugeNomePai', TextType::class, ['mapped' => false, 'required' => false])
            ->add('novoConjugeNomeMae', TextType::class, ['mapped' => false, 'required' => false])
            ->add('novoConjugeRenda', MoneyType::class, [
                'mapped'   => false,
                'required' => false,
                'currency' => 'BRL',
            ])
            ->add('novoConjugeObservacoes', TextareaType::class, [
                'mapped'   => false,
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class'   => Pessoas::class,
            'sub_form_url' => null,
        ]);
    }
}