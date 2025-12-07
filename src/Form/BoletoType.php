<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Boletos;
use App\Entity\ConfiguracoesApiBanco;
use App\Entity\Imoveis;
use App\Entity\Pessoas;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class BoletoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // === CONFIGURAÇÃO ===
            ->add('configuracaoApi', EntityType::class, [
                'class' => ConfiguracoesApiBanco::class,
                'choice_label' => function (ConfiguracoesApiBanco $config) {
                    return sprintf(
                        '%s - %s (%s)',
                        $config->getBanco()->getNome(),
                        $config->getContaBancaria()->getDescricao() ?? $config->getContaBancaria()->getCodigo(),
                        $config->getAmbiente()
                    );
                },
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('c')
                        ->where('c.ativo = true')
                        ->orderBy('c.id', 'ASC');
                },
                'label' => 'Configuração API',
                'placeholder' => 'Selecione a configuração...',
                'required' => true,
                'attr' => [
                    'class' => 'form-select'
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Selecione uma configuração de API']),
                ],
            ])

            // === PAGADOR ===
            ->add('pessoaPagador', EntityType::class, [
                'class' => Pessoas::class,
                'choice_label' => 'nome',
                'label' => 'Pagador',
                'placeholder' => 'Digite para buscar...',
                'required' => true,
                'attr' => [
                    'class' => 'form-select',
                    'data-autocomplete' => 'pessoa'
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Selecione o pagador']),
                ],
            ])

            // === IMÓVEL (Opcional) ===
            ->add('imovel', EntityType::class, [
                'class' => Imoveis::class,
                'choice_label' => function (Imoveis $imovel) {
                    return sprintf('%s - %s', $imovel->getCodigoInterno(), $imovel->getDescricaoResumida() ?? 'Imóvel');
                },
                'label' => 'Imóvel',
                'placeholder' => 'Selecione (opcional)...',
                'required' => false,
                'attr' => [
                    'class' => 'form-select'
                ],
            ])

            // === IDENTIFICAÇÃO ===
            ->add('seuNumero', TextType::class, [
                'label' => 'Seu Número',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Número do documento (opcional)',
                    'maxlength' => 25
                ],
            ])

            // === VALORES ===
            ->add('valorNominal', MoneyType::class, [
                'label' => 'Valor do Boleto',
                'currency' => 'BRL',
                'required' => true,
                'attr' => [
                    'class' => 'form-control valor-monetario',
                    'placeholder' => '0,00'
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Informe o valor do boleto']),
                    new Assert\Positive(['message' => 'O valor deve ser positivo']),
                ],
            ])

            // === DATAS ===
            ->add('dataVencimento', DateType::class, [
                'label' => 'Data de Vencimento',
                'widget' => 'single_text',
                'required' => true,
                'attr' => [
                    'class' => 'form-control'
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Informe a data de vencimento']),
                ],
            ])

            ->add('dataLimitePagamento', DateType::class, [
                'label' => 'Data Limite Pagamento',
                'widget' => 'single_text',
                'required' => false,
                'attr' => [
                    'class' => 'form-control'
                ],
            ])

            // === DESCONTO ===
            ->add('tipoDesconto', ChoiceType::class, [
                'label' => 'Tipo de Desconto',
                'choices' => [
                    'Sem Desconto' => Boletos::DESCONTO_ISENTO,
                    'Valor Fixo até Data' => Boletos::DESCONTO_VALOR_DATA_FIXA,
                    'Percentual até Data' => Boletos::DESCONTO_PERCENTUAL_DATA_FIXA,
                ],
                'required' => true,
                'attr' => [
                    'class' => 'form-select',
                    'data-toggle-fields' => 'desconto'
                ],
            ])

            ->add('valorDesconto', MoneyType::class, [
                'label' => 'Valor/Percentual Desconto',
                'currency' => 'BRL',
                'required' => false,
                'attr' => [
                    'class' => 'form-control valor-monetario campo-desconto',
                    'placeholder' => '0,00'
                ],
            ])

            ->add('dataDesconto', DateType::class, [
                'label' => 'Data Limite Desconto',
                'widget' => 'single_text',
                'required' => false,
                'attr' => [
                    'class' => 'form-control campo-desconto'
                ],
            ])

            // === JUROS ===
            ->add('tipoJuros', ChoiceType::class, [
                'label' => 'Tipo de Juros',
                'choices' => [
                    'Sem Juros' => Boletos::JUROS_ISENTO,
                    'Valor por Dia' => Boletos::JUROS_VALOR_DIA,
                    'Percentual ao Mês' => Boletos::JUROS_PERCENTUAL_MES,
                ],
                'required' => true,
                'attr' => [
                    'class' => 'form-select',
                    'data-toggle-fields' => 'juros'
                ],
            ])

            ->add('valorJurosDia', MoneyType::class, [
                'label' => 'Valor/Percentual Juros',
                'currency' => 'BRL',
                'required' => false,
                'attr' => [
                    'class' => 'form-control valor-monetario campo-juros',
                    'placeholder' => '0,00'
                ],
            ])

            // === MULTA ===
            ->add('tipoMulta', ChoiceType::class, [
                'label' => 'Tipo de Multa',
                'choices' => [
                    'Sem Multa' => Boletos::MULTA_ISENTO,
                    'Valor Fixo' => Boletos::MULTA_VALOR_FIXO,
                    'Percentual' => Boletos::MULTA_PERCENTUAL,
                ],
                'required' => true,
                'attr' => [
                    'class' => 'form-select',
                    'data-toggle-fields' => 'multa'
                ],
            ])

            ->add('valorMulta', MoneyType::class, [
                'label' => 'Valor/Percentual Multa',
                'currency' => 'BRL',
                'required' => false,
                'attr' => [
                    'class' => 'form-control valor-monetario campo-multa',
                    'placeholder' => '0,00'
                ],
            ])

            ->add('dataMulta', DateType::class, [
                'label' => 'Data Início Multa',
                'widget' => 'single_text',
                'required' => false,
                'attr' => [
                    'class' => 'form-control campo-multa'
                ],
            ])

            // === MENSAGENS ===
            ->add('mensagemPagador', TextareaType::class, [
                'label' => 'Mensagem ao Pagador',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 4,
                    'maxlength' => 160,
                    'placeholder' => 'Mensagem que aparecerá no boleto (máximo 160 caracteres = 4 linhas de 40 caracteres)'
                ],
                'constraints' => [
                    new Assert\Length([
                        'max' => 160,
                        'maxMessage' => 'A mensagem pode ter no máximo {{ limit }} caracteres'
                    ]),
                ],
            ]);

        // Listener para configurar data multa padrão
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $data = $event->getData();

            // Se multa configurada mas sem data, usa vencimento + 1 dia
            if (!empty($data['tipoMulta']) && $data['tipoMulta'] !== Boletos::MULTA_ISENTO) {
                if (empty($data['dataMulta']) && !empty($data['dataVencimento'])) {
                    $vencimento = new \DateTime($data['dataVencimento']);
                    $vencimento->modify('+1 day');
                    $data['dataMulta'] = $vencimento->format('Y-m-d');
                    $event->setData($data);
                }
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Boletos::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'boleto_form',
        ]);
    }
}
