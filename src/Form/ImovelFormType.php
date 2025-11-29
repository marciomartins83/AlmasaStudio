<?php

namespace App\Form;

use App\Entity\Imoveis;
use App\Entity\TiposImoveis;
use App\Entity\Enderecos;
use App\Entity\Pessoas;
use App\Entity\Condominios;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ImovelFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // ========== IDENTIFICAÇÃO ==========
            ->add('codigoInterno', TextType::class, [
                'label' => 'Código Interno',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: IMO-001'
                ],
                'required' => false
            ])

            // ========== RELACIONAMENTOS ==========
            ->add('tipoImovel', EntityType::class, [
                'class' => TiposImoveis::class,
                'choice_label' => 'tipo',
                'placeholder' => 'Selecione o tipo...',
                'label' => 'Tipo de Imóvel',
                'attr' => ['class' => 'form-select'],
                'required' => true
            ])

            ->add('endereco', EntityType::class, [
                'class' => Enderecos::class,
                'choice_label' => 'id', // Será customizado no frontend
                'placeholder' => 'Selecione o endereço...',
                'label' => 'Endereço',
                'attr' => ['class' => 'form-select'],
                'required' => true
            ])

            ->add('condominio', EntityType::class, [
                'class' => Condominios::class,
                'choice_label' => 'nome',
                'placeholder' => 'Nenhum',
                'label' => 'Condomínio',
                'attr' => ['class' => 'form-select'],
                'required' => false
            ])

            ->add('pessoaProprietario', EntityType::class, [
                'class' => Pessoas::class,
                'choice_label' => 'nome',
                'placeholder' => 'Selecione o proprietário...',
                'label' => 'Proprietário',
                'attr' => ['class' => 'form-select'],
                'required' => true
            ])

            ->add('pessoaFiador', EntityType::class, [
                'class' => Pessoas::class,
                'choice_label' => 'nome',
                'placeholder' => 'Nenhum',
                'label' => 'Fiador',
                'attr' => ['class' => 'form-select'],
                'required' => false
            ])

            ->add('pessoaCorretor', EntityType::class, [
                'class' => Pessoas::class,
                'choice_label' => 'nome',
                'placeholder' => 'Nenhum',
                'label' => 'Corretor Responsável',
                'attr' => ['class' => 'form-select'],
                'required' => false
            ])

            // ========== SITUAÇÃO ==========
            ->add('situacao', ChoiceType::class, [
                'label' => 'Situação',
                'choices' => [
                    'Disponível' => 'disponivel',
                    'Alugado' => 'alugado',
                    'Vendido' => 'vendido',
                    'Reservado' => 'reservado',
                    'Em Reforma' => 'em_reforma',
                    'Indisponível' => 'indisponivel',
                ],
                'placeholder' => 'Selecione...',
                'attr' => ['class' => 'form-select'],
                'required' => true
            ])

            ->add('tipoUtilizacao', ChoiceType::class, [
                'label' => 'Tipo de Utilização',
                'choices' => [
                    'Residencial' => 'residencial',
                    'Comercial' => 'comercial',
                    'Industrial' => 'industrial',
                    'Misto' => 'misto',
                ],
                'placeholder' => 'Selecione...',
                'attr' => ['class' => 'form-select'],
                'required' => false
            ])

            ->add('ocupacao', ChoiceType::class, [
                'label' => 'Ocupação',
                'choices' => [
                    'Vago' => 'vago',
                    'Ocupado' => 'ocupado',
                    'Ocupado pelo Proprietário' => 'ocupado_proprietario',
                ],
                'placeholder' => 'Selecione...',
                'attr' => ['class' => 'form-select'],
                'required' => false
            ])

            ->add('situacaoFinanceira', ChoiceType::class, [
                'label' => 'Situação Financeira',
                'choices' => [
                    'Regular' => 'regular',
                    'Inadimplente' => 'inadimplente',
                    'Quitado' => 'quitado',
                ],
                'placeholder' => 'Selecione...',
                'attr' => ['class' => 'form-select'],
                'required' => false
            ])

            // ========== DISPONIBILIDADE ==========
            ->add('aluguelGarantido', CheckboxType::class, [
                'label' => 'Aluguel Garantido',
                'required' => false,
                'attr' => ['class' => 'form-check-input']
            ])

            ->add('disponivelAluguel', CheckboxType::class, [
                'label' => 'Disponível para Aluguel',
                'required' => false,
                'attr' => ['class' => 'form-check-input']
            ])

            ->add('disponivelVenda', CheckboxType::class, [
                'label' => 'Disponível para Venda',
                'required' => false,
                'attr' => ['class' => 'form-check-input']
            ])

            ->add('disponivelTemporada', CheckboxType::class, [
                'label' => 'Disponível para Temporada',
                'required' => false,
                'attr' => ['class' => 'form-check-input']
            ])

            // ========== CARACTERÍSTICAS FÍSICAS ==========
            ->add('areaTotal', NumberType::class, [
                'label' => 'Área Total (m²)',
                'attr' => [
                    'class' => 'form-control',
                    'step' => '0.01',
                    'placeholder' => '0.00'
                ],
                'required' => false
            ])

            ->add('areaConstruida', NumberType::class, [
                'label' => 'Área Construída (m²)',
                'attr' => [
                    'class' => 'form-control',
                    'step' => '0.01',
                    'placeholder' => '0.00'
                ],
                'required' => false
            ])

            ->add('areaPrivativa', NumberType::class, [
                'label' => 'Área Privativa (m²)',
                'attr' => [
                    'class' => 'form-control',
                    'step' => '0.01',
                    'placeholder' => '0.00'
                ],
                'required' => false
            ])

            ->add('qtdQuartos', IntegerType::class, [
                'label' => 'Quartos',
                'attr' => ['class' => 'form-control', 'min' => '0'],
                'data' => 0,
                'required' => false
            ])

            ->add('qtdSuites', IntegerType::class, [
                'label' => 'Suítes',
                'attr' => ['class' => 'form-control', 'min' => '0'],
                'data' => 0,
                'required' => false
            ])

            ->add('qtdBanheiros', IntegerType::class, [
                'label' => 'Banheiros',
                'attr' => ['class' => 'form-control', 'min' => '0'],
                'data' => 0,
                'required' => false
            ])

            ->add('qtdSalas', IntegerType::class, [
                'label' => 'Salas',
                'attr' => ['class' => 'form-control', 'min' => '0'],
                'data' => 0,
                'required' => false
            ])

            ->add('qtdVagasGaragem', IntegerType::class, [
                'label' => 'Vagas de Garagem',
                'attr' => ['class' => 'form-control', 'min' => '0'],
                'data' => 0,
                'required' => false
            ])

            ->add('qtdPavimentos', IntegerType::class, [
                'label' => 'Pavimentos',
                'attr' => ['class' => 'form-control', 'min' => '1'],
                'data' => 1,
                'required' => false
            ])

            // ========== CONSTRUÇÃO ==========
            ->add('anoConstrucao', IntegerType::class, [
                'label' => 'Ano de Construção',
                'attr' => [
                    'class' => 'form-control',
                    'min' => '1900',
                    'max' => date('Y'),
                    'placeholder' => 'AAAA'
                ],
                'required' => false
            ])

            ->add('tipoConstrucao', ChoiceType::class, [
                'label' => 'Tipo de Construção',
                'choices' => [
                    'Alvenaria' => 'alvenaria',
                    'Madeira' => 'madeira',
                    'Mista' => 'mista',
                    'Concreto' => 'concreto',
                ],
                'placeholder' => 'Selecione...',
                'attr' => ['class' => 'form-select'],
                'required' => false
            ])

            ->add('aptosPorAndar', IntegerType::class, [
                'label' => 'Apartamentos por Andar',
                'attr' => ['class' => 'form-control', 'min' => '1'],
                'required' => false
            ])

            // ========== VALORES ==========
            ->add('valorAluguel', MoneyType::class, [
                'label' => 'Valor do Aluguel',
                'currency' => 'BRL',
                'attr' => ['class' => 'form-control', 'placeholder' => 'R$ 0,00'],
                'required' => false
            ])

            ->add('valorVenda', MoneyType::class, [
                'label' => 'Valor de Venda',
                'currency' => 'BRL',
                'attr' => ['class' => 'form-control', 'placeholder' => 'R$ 0,00'],
                'required' => false
            ])

            ->add('valorTemporada', MoneyType::class, [
                'label' => 'Valor Temporada',
                'currency' => 'BRL',
                'attr' => ['class' => 'form-control', 'placeholder' => 'R$ 0,00'],
                'required' => false
            ])

            ->add('valorCondominio', MoneyType::class, [
                'label' => 'Valor do Condomínio',
                'currency' => 'BRL',
                'attr' => ['class' => 'form-control', 'placeholder' => 'R$ 0,00'],
                'required' => false
            ])

            ->add('valorIptuMensal', MoneyType::class, [
                'label' => 'IPTU Mensal',
                'currency' => 'BRL',
                'attr' => ['class' => 'form-control', 'placeholder' => 'R$ 0,00'],
                'required' => false
            ])

            ->add('valorTaxaLixo', MoneyType::class, [
                'label' => 'Taxa de Lixo',
                'currency' => 'BRL',
                'attr' => ['class' => 'form-control', 'placeholder' => 'R$ 0,00'],
                'required' => false
            ])

            ->add('valorMercado', MoneyType::class, [
                'label' => 'Valor de Mercado',
                'currency' => 'BRL',
                'attr' => ['class' => 'form-control', 'placeholder' => 'R$ 0,00'],
                'required' => false
            ])

            ->add('diaVencimento', IntegerType::class, [
                'label' => 'Dia de Vencimento',
                'attr' => [
                    'class' => 'form-control',
                    'min' => '1',
                    'max' => '31',
                    'placeholder' => '1-31'
                ],
                'required' => false
            ])

            // ========== COMISSÕES ==========
            ->add('taxaAdministracao', NumberType::class, [
                'label' => 'Taxa de Administração (%)',
                'attr' => [
                    'class' => 'form-control',
                    'step' => '0.01',
                    'min' => '0',
                    'max' => '100',
                    'placeholder' => '0.00'
                ],
                'required' => false
            ])

            ->add('comissaoLocacao', NumberType::class, [
                'label' => 'Comissão Locação (%)',
                'attr' => [
                    'class' => 'form-control',
                    'step' => '0.01',
                    'min' => '0',
                    'max' => '100',
                    'placeholder' => '0.00'
                ],
                'required' => false
            ])

            ->add('comissaoVenda', NumberType::class, [
                'label' => 'Comissão Venda (%)',
                'attr' => [
                    'class' => 'form-control',
                    'step' => '0.01',
                    'min' => '0',
                    'max' => '100',
                    'placeholder' => '0.00'
                ],
                'required' => false
            ])

            // ========== DOCUMENTAÇÃO ==========
            ->add('inscricaoImobiliaria', TextType::class, [
                'label' => 'Inscrição Imobiliária',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Número da inscrição'
                ],
                'required' => false
            ])

            ->add('matriculaCartorio', TextType::class, [
                'label' => 'Matrícula do Cartório',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Número da matrícula'
                ],
                'required' => false
            ])

            ->add('nomeCartorio', TextType::class, [
                'label' => 'Nome do Cartório',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Nome completo do cartório'
                ],
                'required' => false
            ])

            ->add('nomeContribuinteIptu', TextType::class, [
                'label' => 'Nome no IPTU',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Nome do contribuinte do IPTU'
                ],
                'required' => false
            ])

            // ========== DESCRIÇÃO ==========
            ->add('descricao', TextareaType::class, [
                'label' => 'Descrição',
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 4,
                    'placeholder' => 'Descrição completa do imóvel...'
                ],
                'required' => false
            ])

            ->add('observacoes', TextareaType::class, [
                'label' => 'Observações',
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'Observações internas...'
                ],
                'required' => false
            ])

            ->add('descricaoImediacoes', TextareaType::class, [
                'label' => 'Descrição das Imediações',
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'Pontos de referência, comércio próximo, etc...'
                ],
                'required' => false
            ])

            // ========== CHAVES ==========
            ->add('temChaves', CheckboxType::class, [
                'label' => 'Possui Chaves',
                'required' => false,
                'attr' => ['class' => 'form-check-input']
            ])

            ->add('qtdChaves', IntegerType::class, [
                'label' => 'Quantidade de Chaves',
                'attr' => ['class' => 'form-control', 'min' => '0'],
                'data' => 0,
                'required' => false
            ])

            ->add('numeroChave', TextType::class, [
                'label' => 'Número da Chave',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: CHV-001'
                ],
                'required' => false
            ])

            ->add('localizacaoChaves', TextType::class, [
                'label' => 'Localização das Chaves',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: Armário 3, Gaveta 2'
                ],
                'required' => false
            ])

            // ========== PUBLICAÇÃO ==========
            ->add('publicarSite', CheckboxType::class, [
                'label' => 'Publicar no Site',
                'required' => false,
                'data' => true,
                'attr' => ['class' => 'form-check-input']
            ])

            ->add('publicarZap', CheckboxType::class, [
                'label' => 'Publicar no ZAP',
                'required' => false,
                'attr' => ['class' => 'form-check-input']
            ])

            ->add('publicarVivareal', CheckboxType::class, [
                'label' => 'Publicar no VivaReal',
                'required' => false,
                'attr' => ['class' => 'form-check-input']
            ])

            ->add('publicarGruposp', CheckboxType::class, [
                'label' => 'Publicar no GrupoSP',
                'required' => false,
                'attr' => ['class' => 'form-check-input']
            ])

            ->add('ocultarValorSite', CheckboxType::class, [
                'label' => 'Ocultar Valor no Site',
                'required' => false,
                'attr' => ['class' => 'form-check-input']
            ])

            ->add('temPlaca', CheckboxType::class, [
                'label' => 'Possui Placa',
                'required' => false,
                'attr' => ['class' => 'form-check-input']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Imoveis::class,
        ]);
    }
}
