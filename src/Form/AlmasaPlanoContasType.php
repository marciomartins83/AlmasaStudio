<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\AlmasaPlanoContas;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class AlmasaPlanoContasType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nivel', ChoiceType::class, [
                'label' => 'O que deseja criar?',
                'attr' => ['class' => 'form-select form-select-lg', 'id' => 'almasa_pc_nivel'],
                'choices' => [
                    'Classe' => AlmasaPlanoContas::NIVEL_CLASSE,
                    'Grupo' => AlmasaPlanoContas::NIVEL_GRUPO,
                    'Subgrupo' => AlmasaPlanoContas::NIVEL_SUBGRUPO,
                    'Conta' => AlmasaPlanoContas::NIVEL_CONTA,
                ],
                'placeholder' => 'Selecione o nivel...',
                'required' => true,
            ])
            ->add('pai', EntityType::class, [
                'class' => AlmasaPlanoContas::class,
                'label' => 'Pertence a',
                'choice_label' => function (AlmasaPlanoContas $conta) {
                    return $conta->getCodigo() . ' - ' . $conta->getDescricao();
                },
                'choice_attr' => function (AlmasaPlanoContas $conta) {
                    return [
                        'data-nivel' => $conta->getNivel(),
                        'data-tipo' => $conta->getTipo(),
                    ];
                },
                'query_builder' => function ($repo) {
                    return $repo->createQueryBuilder('a')
                        ->where('a.nivel < :nivelMax')
                        ->andWhere('a.ativo = true')
                        ->setParameter('nivelMax', AlmasaPlanoContas::NIVEL_CONTA)
                        ->orderBy('a.codigo', 'ASC');
                },
                'placeholder' => 'Selecione...',
                'attr' => ['class' => 'form-select', 'id' => 'almasa_pc_pai'],
                'required' => false,
            ])
            ->add('tipo', ChoiceType::class, [
                'label' => 'Tipo Contabil',
                'attr' => ['class' => 'form-select', 'id' => 'almasa_pc_tipo'],
                'choices' => [
                    'Ativo' => AlmasaPlanoContas::TIPO_ATIVO,
                    'Passivo' => AlmasaPlanoContas::TIPO_PASSIVO,
                    'Patrimonio Liquido' => AlmasaPlanoContas::TIPO_PATRIMONIO_LIQUIDO,
                    'Receita' => AlmasaPlanoContas::TIPO_RECEITA,
                    'Despesa' => AlmasaPlanoContas::TIPO_DESPESA,
                ],
                'required' => true,
            ])
            ->add('codigo', TextType::class, [
                'label' => 'Codigo',
                'attr' => ['class' => 'form-control', 'maxlength' => 20, 'id' => 'almasa_pc_codigo'],
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Campo obrigatorio']),
                    new Assert\Length(['max' => 20]),
                ],
            ])
            ->add('descricao', TextType::class, [
                'label' => 'Descricao',
                'attr' => ['class' => 'form-control', 'maxlength' => 255],
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Campo obrigatorio']),
                    new Assert\Length(['max' => 255]),
                ],
            ])
            ->add('aceitaLancamentos', CheckboxType::class, [
                'label' => 'Aceita Lancamentos?',
                'required' => false,
                'attr' => ['class' => 'form-check-input'],
                'label_attr' => ['class' => 'form-check-label'],
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
            'data_class' => AlmasaPlanoContas::class,
        ]);
    }
}
