<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Lancamentos;
use App\Entity\PlanoContas;
use App\Entity\Pessoas;
use App\Entity\ImoveisContratos;
use App\Entity\Imoveis;
use App\Entity\ContasBancarias;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
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
                    'Pagar' => Lancamentos::TIPO_PAGAR,
                    'Receber' => Lancamentos::TIPO_RECEBER,
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
                'label' => 'Hist\u00f3rico',
                'attr' => [
                    'class' => 'form-control',
                    'maxlength' => 200,
                ],
                'required' => false,
            ])

            // === CLASSIFICAÇÃO ===
            ->add('planoConta', EntityType::class, [
                'class' => PlanoContas::class,
                'label' => 'Plano de Conta',
                'choice_label' => function (PlanoContas $plano) {
                    return $plano->getCodigo() . ' - ' . $plano->getDescricao();
                },
                'query_builder' => function ($repo) {
                    return $repo->createQueryBuilder('p')
                        ->where('p.ativo = true')
                        ->orderBy('p.codigo', 'ASC');
                },
                'placeholder' => 'Selecione...',
                'attr' => ['class' => 'form-select'],
                'required' => true,
            ])
            ->add('competencia', TextType::class, [
                'label' => 'Compet\u00eancia',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'YYYY-MM',
                    'maxlength' => 7,
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

            // === PESSOAS ===
            ->add('pessoaCredor', EntityType::class, [
                'class' => Pessoas::class,
                'label' => 'Credor/Fornecedor',
                'choice_label' => 'nome',
                'placeholder' => 'Selecione...',
                'attr' => [
                    'class' => 'form-select',
                    'data-pessoa-autocomplete' => 'credor',
                ],
                'required' => false,
            ])
            ->add('pessoaPagador', EntityType::class, [
                'class' => Pessoas::class,
                'label' => 'Pagador/Cliente',
                'choice_label' => 'nome',
                'placeholder' => 'Selecione...',
                'attr' => [
                    'class' => 'form-select',
                    'data-pessoa-autocomplete' => 'pagador',
                ],
                'required' => false,
            ])

            // === VÍNCULOS ===
            ->add('contrato', EntityType::class, [
                'class' => ImoveisContratos::class,
                'label' => 'Contrato',
                'choice_label' => function (ImoveisContratos $contrato) {
                    $imovel = $contrato->getImovel();
                    $locatario = $contrato->getPessoaLocatario();
                    return sprintf(
                        '#%d - %s (%s)',
                        $contrato->getId(),
                        $imovel ? $imovel->getCodigoInterno() : 'S/N',
                        $locatario ? $locatario->getNome() : 'S/I'
                    );
                },
                'placeholder' => 'Selecione...',
                'attr' => ['class' => 'form-select'],
                'required' => false,
            ])
            ->add('imovel', EntityType::class, [
                'class' => Imoveis::class,
                'label' => 'Imovel',
                'choice_label' => 'codigoInterno',
                'placeholder' => 'Selecione...',
                'attr' => ['class' => 'form-select'],
                'required' => false,
            ])
            ->add('contaBancaria', EntityType::class, [
                'class' => ContasBancarias::class,
                'label' => 'Conta Banc\u00e1ria',
                'choice_label' => 'descricao',
                'query_builder' => function ($repo) {
                    return $repo->createQueryBuilder('c')
                        ->where('c.ativo = true')
                        ->orderBy('c.descricao', 'ASC');
                },
                'placeholder' => 'Selecione...',
                'attr' => ['class' => 'form-select'],
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
                'label' => 'N\u00famero Documento',
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
                    'D\u00e9bito' => 'debito',
                    'Cr\u00e9dito' => 'credito',
                    'Cheque' => 'cheque',
                ],
                'placeholder' => 'Selecione...',
                'attr' => ['class' => 'form-select'],
                'required' => false,
            ])

            // === OBSERVAÇÕES ===
            ->add('observacoes', TextareaType::class, [
                'label' => 'Observa\u00e7\u00f5es',
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                ],
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Lancamentos::class,
        ]);
    }
}
