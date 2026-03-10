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
            ->add('codigo', TextType::class, [
                'label' => 'Código',
                'attr' => ['class' => 'form-control', 'maxlength' => 20, 'placeholder' => 'Ex: 1.1.01'],
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Campo obrigatório']),
                    new Assert\Length(['max' => 20]),
                ],
            ])
            ->add('descricao', TextType::class, [
                'label' => 'Descrição',
                'attr' => ['class' => 'form-control', 'maxlength' => 255],
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Campo obrigatório']),
                    new Assert\Length(['max' => 255]),
                ],
            ])
            ->add('tipo', ChoiceType::class, [
                'label' => 'Tipo',
                'attr' => ['class' => 'form-select'],
                'choices' => [
                    'Ativo' => AlmasaPlanoContas::TIPO_ATIVO,
                    'Passivo' => AlmasaPlanoContas::TIPO_PASSIVO,
                    'Patrimônio Líquido' => AlmasaPlanoContas::TIPO_PATRIMONIO_LIQUIDO,
                    'Receita' => AlmasaPlanoContas::TIPO_RECEITA,
                    'Despesa' => AlmasaPlanoContas::TIPO_DESPESA,
                ],
                'required' => true,
            ])
            ->add('nivel', ChoiceType::class, [
                'label' => 'Nível',
                'attr' => ['class' => 'form-select'],
                'choices' => [
                    '1 - Classe' => AlmasaPlanoContas::NIVEL_CLASSE,
                    '2 - Grupo' => AlmasaPlanoContas::NIVEL_GRUPO,
                    '3 - Subgrupo' => AlmasaPlanoContas::NIVEL_SUBGRUPO,
                    '4 - Conta' => AlmasaPlanoContas::NIVEL_CONTA,
                    '5 - Subconta' => AlmasaPlanoContas::NIVEL_SUBCONTA,
                ],
                'required' => true,
            ])
            ->add('pai', EntityType::class, [
                'class' => AlmasaPlanoContas::class,
                'label' => 'Conta Pai',
                'choice_label' => function (AlmasaPlanoContas $conta) {
                    return $conta->getCodigo() . ' - ' . $conta->getDescricao();
                },
                'query_builder' => function ($repo) {
                    return $repo->createQueryBuilder('a')
                        ->where('a.nivel < :nivelMax')
                        ->andWhere('a.ativo = true')
                        ->setParameter('nivelMax', AlmasaPlanoContas::NIVEL_SUBCONTA)
                        ->orderBy('a.codigo', 'ASC');
                },
                'placeholder' => '(Nenhum - Conta Raiz)',
                'attr' => ['class' => 'form-select'],
                'required' => false,
            ])
            ->add('aceitaLancamentos', CheckboxType::class, [
                'label' => 'Aceita Lançamentos?',
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
