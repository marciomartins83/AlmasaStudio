<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\AlmasaLancamento;
use App\Entity\AlmasaPlanoContas;
use App\Entity\ContasBancarias;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class AlmasaLancamentoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('tipo', ChoiceType::class, [
                'label' => 'Tipo',
                'attr' => ['class' => 'form-select'],
                'choices' => [
                    'Receita' => AlmasaLancamento::TIPO_RECEITA,
                    'Despesa' => AlmasaLancamento::TIPO_DESPESA,
                ],
                'required' => true,
            ])
            ->add('almasaPlanoConta', EntityType::class, [
                'class' => AlmasaPlanoContas::class,
                'label' => 'Plano de Contas Almasa',
                'choice_label' => function (AlmasaPlanoContas $conta) {
                    return $conta->getCodigo() . ' - ' . $conta->getDescricao();
                },
                'query_builder' => function ($repo) {
                    return $repo->createQueryBuilder('a')
                        ->where('a.aceitaLancamentos = true')
                        ->andWhere('a.ativo = true')
                        ->orderBy('a.codigo', 'ASC');
                },
                'placeholder' => 'Selecione...',
                'attr' => ['class' => 'form-select'],
                'required' => true,
            ])
            ->add('descricao', TextType::class, [
                'label' => 'Descrição',
                'attr' => ['class' => 'form-control', 'maxlength' => 255],
                'required' => false,
            ])
            ->add('valor', NumberType::class, [
                'label' => 'Valor (R$)',
                'scale' => 2,
                'html5' => true,
                'attr' => ['class' => 'form-control', 'step' => '0.01', 'min' => '0'],
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Campo obrigatório']),
                    new Assert\Positive(['message' => 'Valor deve ser positivo']),
                ],
            ])
            ->add('dataCompetencia', DateType::class, [
                'label' => 'Data Competência',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
                'required' => true,
            ])
            ->add('dataVencimento', DateType::class, [
                'label' => 'Data Vencimento',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
                'required' => false,
            ])
            ->add('dataPagamento', DateType::class, [
                'label' => 'Data Pagamento',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
                'required' => false,
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Status',
                'attr' => ['class' => 'form-select'],
                'choices' => [
                    'Aberto' => AlmasaLancamento::STATUS_ABERTO,
                    'Pago' => AlmasaLancamento::STATUS_PAGO,
                    'Cancelado' => AlmasaLancamento::STATUS_CANCELADO,
                ],
                'required' => true,
            ])
            ->add('contaBancaria', EntityType::class, [
                'class' => ContasBancarias::class,
                'label' => 'Conta Bancária',
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
            ->add('observacao', TextareaType::class, [
                'label' => 'Observação',
                'attr' => ['class' => 'form-control', 'rows' => 3],
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AlmasaLancamento::class,
        ]);
    }
}
