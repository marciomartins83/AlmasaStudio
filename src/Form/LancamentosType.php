<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Lancamentos;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LancamentosType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // === DADOS PRINCIPAIS ===
            ->add('tipo', ChoiceType::class, [
                'label' => 'Tipo',
                'choices' => [
                    'Débito' => Lancamentos::TIPO_PAGAR,
                    'Crédito' => Lancamentos::TIPO_RECEBER,
                ],
                'attr' => ['class' => 'form-select'],
                'required' => true,
            ])
            ->add('dataMovimento', DateType::class, [
                'label' => 'Data Movimento',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
                'required' => true,
            ])
            ->add('dataVencimento', DateType::class, [
                'label' => 'Data Vencimento',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
                'required' => true,
            ])
            ->add('valor', NumberType::class, [
                'label' => 'Valor (R$)',
                'scale' => 2,
                'html5' => true,
                'attr' => [
                    'class' => 'form-control',
                    'step' => '0.01',
                    'min' => '0',
                ],
                'required' => true,
            ])
            ->add('historico', TextType::class, [
                'label' => 'Histórico',
                'attr' => [
                    'class' => 'form-control',
                    'maxlength' => 200,
                ],
                'required' => false,
            ])

            // === CLASSIFICAÇÃO ===
            ->add('planoContaId', HiddenType::class, [
                'mapped' => false,
                'required' => false,
            ])

            // === PARTIDAS DOBRADAS ===
            ->add('planoContaDebito', HiddenType::class, [
                'mapped' => false,
                'required' => false,
            ])
            ->add('planoContaCredito', HiddenType::class, [
                'mapped' => false,
                'required' => false,
            ])
            ->add('competencia', TextType::class, [
                'label' => 'Competência',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'MM/AAAA',
                    'maxlength' => 7,
                    'pattern' => '\d{2}/\d{4}',
                ],
                'required' => false,
            ])
            ->add('centroCusto', TextType::class, [
                'label' => 'Centro de Custo',
                'attr' => [
                    'class' => 'form-control',
                    'maxlength' => 20,
                ],
                'required' => false,
            ])

            // === RECORRÊNCIA (somente criação — mapped: false) ===
            ->add('recorrenciaTipo', ChoiceType::class, [
                'mapped'   => false,
                'required' => false,
                'label'    => 'Recorrência',
                'choices'  => [
                    'Nenhuma (único)'   => 'nenhuma',
                    'Semanal (7 dias)'  => 'semanal',
                    'Quinzenal (15 dias)' => 'quinzenal',
                    'Mensal'            => 'mensal',
                    'Bimestral'         => 'bimestral',
                    'Trimestral'        => 'trimestral',
                    'Semestral'         => 'semestral',
                    'Anual'             => 'anual',
                    'Bienal (2 anos)'   => 'bienal',
                ],
                'data' => 'nenhuma',
                'attr' => ['class' => 'form-select'],
            ])
            ->add('recorrenciaQtd', IntegerType::class, [
                'mapped'   => false,
                'required' => false,
                'label'    => 'Parcelas',
                'attr'     => [
                    'class'       => 'form-control',
                    'min'         => 2,
                    'max'         => 60,
                    'placeholder' => 'Ex: 12',
                ],
            ])

            // === PESSOAS (IDs via autocomplete AJAX — não carrega EntityType) ===
            ->add('pessoaCredorId', HiddenType::class, [
                'mapped' => false,
                'required' => false,
            ])
            ->add('pessoaPagadorId', HiddenType::class, [
                'mapped' => false,
                'required' => false,
            ])

            // === VÍNCULOS ===
            ->add('contratoId', HiddenType::class, [
                'mapped' => false,
                'required' => false,
            ])
            ->add('imovelId', HiddenType::class, [
                'mapped' => false,
                'required' => false,
            ])
            ->add('contaBancariaId', HiddenType::class, [
                'mapped' => false,
                'required' => false,
            ])

            // === DOCUMENTO ===
            ->add('tipoDocumento', ChoiceType::class, [
                'label' => 'Tipo Documento',
                'choices' => [
                    'Nota Fiscal' => 'nf',
                    'Recibo' => 'recibo',
                    'Fatura' => 'fatura',
                    'Contrato' => 'contrato',
                    'Boleto' => 'boleto',
                    'Outros' => 'outros',
                ],
                'placeholder' => 'Selecione...',
                'attr' => ['class' => 'form-select'],
                'required' => false,
            ])
            ->add('numeroDocumento', TextType::class, [
                'label' => 'Número Documento',
                'attr' => [
                    'class' => 'form-control',
                    'maxlength' => 50,
                ],
                'required' => false,
            ])

            // === VALORES ADICIONAIS ===
            ->add('valorDesconto', NumberType::class, [
                'label' => 'Desconto (R$)',
                'scale' => 2,
                'html5' => true,
                'attr' => [
                    'class' => 'form-control',
                    'step' => '0.01',
                    'min' => '0',
                ],
                'required' => false,
            ])
            ->add('valorJuros', NumberType::class, [
                'label' => 'Juros (R$)',
                'scale' => 2,
                'html5' => true,
                'attr' => [
                    'class' => 'form-control',
                    'step' => '0.01',
                    'min' => '0',
                ],
                'required' => false,
            ])
            ->add('valorMulta', NumberType::class, [
                'label' => 'Multa (R$)',
                'scale' => 2,
                'html5' => true,
                'attr' => [
                    'class' => 'form-control',
                    'step' => '0.01',
                    'min' => '0',
                ],
                'required' => false,
            ])

            // === RETENÇÕES ===
            ->add('reterInss', CheckboxType::class, [
                'label' => 'Reter INSS',
                'attr' => ['class' => 'form-check-input'],
                'required' => false,
            ])
            ->add('percInss', NumberType::class, [
                'label' => '% INSS',
                'scale' => 2,
                'html5' => true,
                'attr' => [
                    'class' => 'form-control',
                    'step' => '0.01',
                    'min' => '0',
                    'max' => '100',
                ],
                'required' => false,
            ])
            ->add('reterIss', CheckboxType::class, [
                'label' => 'Reter ISS',
                'attr' => ['class' => 'form-check-input'],
                'required' => false,
            ])
            ->add('percIss', NumberType::class, [
                'label' => '% ISS',
                'scale' => 2,
                'html5' => true,
                'attr' => [
                    'class' => 'form-control',
                    'step' => '0.01',
                    'min' => '0',
                    'max' => '100',
                ],
                'required' => false,
            ])

            // === PAGAMENTO ===
            ->add('formaPagamento', ChoiceType::class, [
                'label' => 'Forma de Pagamento',
                'choices' => [
                    'PIX' => 'pix',
                    'TED' => 'ted',
                    'Boleto' => 'boleto',
                    'Dinheiro' => 'dinheiro',
                    'Débito' => 'debito',
                    'Crédito' => 'credito',
                    'Cheque' => 'cheque',
                ],
                'placeholder' => 'Selecione...',
                'attr' => ['class' => 'form-select'],
                'required' => false,
            ])

            // === OBSERVAÇÕES ===
            ->add('observacoes', TextareaType::class, [
                'label' => 'Observações',
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                ],
                'required' => false,
            ]);

        // Competência: armazena YYYY-MM no banco, exibe MM/YYYY no form
        $builder->get('competencia')->addViewTransformer(new CallbackTransformer(
            function (?string $modelValue): string {
                if (!$modelValue) return '';
                if (preg_match('/^(\d{4})-(\d{2})$/', $modelValue, $m)) {
                    return $m[2] . '/' . $m[1];
                }
                return $modelValue;
            },
            function (?string $viewValue): ?string {
                if (!$viewValue) return null;
                if (preg_match('/^(\d{2})\/(\d{4})$/', $viewValue, $m)) {
                    return $m[2] . '-' . $m[1];
                }
                return $viewValue;
            }
        ));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Lancamentos::class,
        ]);
    }
}
