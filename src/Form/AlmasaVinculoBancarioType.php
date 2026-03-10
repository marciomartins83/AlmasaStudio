<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\AlmasaPlanoContas;
use App\Entity\AlmasaVinculoBancario;
use App\Entity\ContasBancarias;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AlmasaVinculoBancarioType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('contaBancaria', EntityType::class, [
                'class' => ContasBancarias::class,
                'label' => 'Conta Bancaria',
                'choice_label' => function (ContasBancarias $cb) {
                    $banco = $cb->getIdBanco()?->getNome() ?? '';
                    $agencia = $cb->getIdAgencia()?->getCodigo() ?? '';
                    $conta = $cb->getCodigo();
                    $digito = $cb->getDigitoConta() ? '-' . $cb->getDigitoConta() : '';
                    $pessoa = $cb->getIdPessoa()?->getNome() ?? 'Almasa';
                    return "{$banco} Ag:{$agencia} Cc:{$conta}{$digito} ({$pessoa})";
                },
                'query_builder' => function ($repo) {
                    return $repo->createQueryBuilder('cb')
                        ->leftJoin('cb.idBanco', 'b')
                        ->leftJoin('cb.idPessoa', 'p')
                        ->where('cb.ativo = true')
                        ->orderBy('b.nome', 'ASC')
                        ->addOrderBy('cb.codigo', 'ASC');
                },
                'placeholder' => 'Selecione uma conta bancaria...',
                'attr' => ['class' => 'form-select'],
                'required' => true,
            ])
            ->add('almasaPlanoConta', EntityType::class, [
                'class' => AlmasaPlanoContas::class,
                'label' => 'Conta do Plano de Contas',
                'choice_label' => function (AlmasaPlanoContas $pc) {
                    return $pc->getCodigo() . ' - ' . $pc->getDescricao() . ' (' . $pc->getTipoLabel() . ')';
                },
                'query_builder' => function ($repo) {
                    return $repo->createQueryBuilder('pc')
                        ->where('pc.ativo = true')
                        ->andWhere('pc.aceitaLancamentos = true')
                        ->orderBy('pc.codigo', 'ASC');
                },
                'placeholder' => 'Selecione uma conta do plano...',
                'attr' => ['class' => 'form-select'],
                'required' => true,
            ])
            ->add('observacao', TextareaType::class, [
                'label' => 'Observacao',
                'attr' => ['class' => 'form-control', 'rows' => 3, 'maxlength' => 255],
                'required' => false,
            ])
            ->add('ativo', CheckboxType::class, [
                'label' => 'Ativo?',
                'required' => false,
                'attr' => ['class' => 'form-check-input'],
                'label_attr' => ['class' => 'form-check-label'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AlmasaVinculoBancario::class,
        ]);
    }
}
