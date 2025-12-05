<?php

namespace App\Form;

use App\Entity\PessoasSocios;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PessoaSocioType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('percentualParticipacao', NumberType::class, [
                'label' => 'Percentual de Participação (%)',
                'required' => false,
                'scale' => 2,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '0,00',
                    'min' => 0,
                    'max' => 100,
                    'step' => '0.01'
                ]
            ])
            ->add('dataEntrada', DateType::class, [
                'label' => 'Data de Entrada',
                'required' => false,
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control']
            ])
            ->add('tipoSocio', ChoiceType::class, [
                'label' => 'Tipo de Sócio',
                'required' => false,
                'placeholder' => 'Selecione...',
                'choices' => [
                    'Administrador' => 'administrador',
                    'Cotista' => 'cotista',
                    'Sócio-Gerente' => 'socio_gerente',
                    'Sócio Investidor' => 'socio_investidor',
                ],
                'attr' => ['class' => 'form-select']
            ])
            ->add('observacoes', TextareaType::class, [
                'label' => 'Observações',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3
                ]
            ])
            ->add('ativo', CheckboxType::class, [
                'label' => 'Ativo',
                'required' => false,
                'attr' => ['class' => 'form-check-input']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PessoasSocios::class,
        ]);
    }
}
