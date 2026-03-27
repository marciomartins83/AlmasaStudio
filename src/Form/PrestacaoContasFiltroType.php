<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\PrestacoesContas;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Formulário de filtros para geração de Prestação de Contas
 */
class PrestacaoContasFiltroType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // === PERÍODO ===
            ->add('tipoPeriodo', ChoiceType::class, [
                'label' => 'Tipo de Período',
                'choices' => [
                    'Personalizado' => PrestacoesContas::PERIODO_PERSONALIZADO,
                    'Diário' => PrestacoesContas::PERIODO_DIARIO,
                    'Semanal' => PrestacoesContas::PERIODO_SEMANAL,
                    'Quinzenal' => PrestacoesContas::PERIODO_QUINZENAL,
                    'Mensal' => PrestacoesContas::PERIODO_MENSAL,
                    'Trimestral' => PrestacoesContas::PERIODO_TRIMESTRAL,
                    'Semestral' => PrestacoesContas::PERIODO_SEMESTRAL,
                    'Anual' => PrestacoesContas::PERIODO_ANUAL,
                    'Bienal' => PrestacoesContas::PERIODO_BIENAL,
                ],
                'data' => PrestacoesContas::PERIODO_MENSAL,
                'attr' => ['class' => 'form-select'],
                'required' => true,
            ])
            ->add('dataInicio', DateType::class, [
                'label' => 'Data Início',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
                'required' => true,
            ])
            ->add('dataFim', DateType::class, [
                'label' => 'Data Fim',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
                'required' => true,
            ])
            ->add('competencia', TextType::class, [
                'label' => 'Competência',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'YYYY-MM',
                    'maxlength' => 7,
                ],
                'required' => false,
            ])

            // === VÍNCULOS (autocomplete) ===
            ->add('proprietario', HiddenType::class, [
                'required' => true,
            ])
            ->add('imovel', HiddenType::class, [
                'required' => false,
            ])

            // === ORIGEM DOS DADOS ===
            ->add('incluirFichaFinanceira', CheckboxType::class, [
                'label' => 'Incluir Ficha Financeira',
                'data' => true,
                'required' => false,
                'attr' => ['class' => 'form-check-input'],
                'label_attr' => ['class' => 'form-check-label'],
            ])
            ->add('incluirLancamentos', CheckboxType::class, [
                'label' => 'Incluir Lançamentos (Contas a Pagar/Receber)',
                'data' => true,
                'required' => false,
                'attr' => ['class' => 'form-check-input'],
                'label_attr' => ['class' => 'form-check-label'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'ajax_global',
        ]);
    }
}
