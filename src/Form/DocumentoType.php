<?php

namespace App\Form;

use App\Entity\PessoasDocumentos;
use App\Entity\TiposDocumentos;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DocumentoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('tipo', EntityType::class, [
                'class'        => TiposDocumentos::class,
                'choice_label' => 'nome',
                'placeholder'  => 'Selecione o tipo',
                'label'        => 'Tipo de Documento',
                'attr'         => ['class' => 'form-select documento-tipo-select'],
            ])
            ->add('numero', TextType::class, [
                'label' => 'Número do Documento',
                'attr'  => [
                    'class'       => 'form-control documento-numero-input',
                    'placeholder' => 'Número do documento',
                ],
            ])
            ->add('orgao_emissor', TextType::class, [
                'label'    => 'Órgão Emissor',
                'required' => false,
                'attr'     => [
                    'class'       => 'form-control documento-orgao-input',
                    'placeholder' => 'Ex: SSP-SP',
                ],
            ])
            ->add('data_emissao', DateType::class, [
                'widget'   => 'single_text',
                'required' => false,
                'label'    => 'Data de Emissão',
                'attr'     => ['class' => 'form-control documento-emissao-date'],
            ])
            ->add('data_vencimento', DateType::class, [
                'widget'   => 'single_text',
                'required' => false,
                'label'    => 'Data de Vencimento',
                'attr'     => ['class' => 'form-control documento-vencimento-date'],
            ])
            ->add('observacoes', TextareaType::class, [
                'label'    => 'Observações',
                'required' => false,
                'attr'     => [
                    'class' => 'form-control documento-observacoes-textarea',
                    'rows'  => 2,
                    'placeholder' => 'Observações sobre o documento',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PessoasDocumentos::class,
        ]);
    }
}