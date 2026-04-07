<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\AlmasaPlanoContas;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
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
                'attr' => ['class' => 'form-select form-select-lg'],
                'choices' => [
                    'Classe' => AlmasaPlanoContas::NIVEL_CLASSE,
                    'Grupo' => AlmasaPlanoContas::NIVEL_GRUPO,
                    'Subgrupo' => AlmasaPlanoContas::NIVEL_SUBGRUPO,
                    'Conta' => AlmasaPlanoContas::NIVEL_CONTA,
                ],
                'placeholder' => 'Selecione o nível...',
                'required' => true,
            ])
            ->add('pai', HiddenType::class, [
                'mapped' => false,
                'required' => false,
            ])
            ->add('tipo', ChoiceType::class, [
                'label' => 'Tipo Contábil',
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
            ->add('codigo', TextType::class, [
                'label' => 'Código',
                'attr' => ['class' => 'form-control', 'maxlength' => 20],
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Campo obrigatorio']),
                    new Assert\Length(['max' => 20]),
                ],
            ])
            ->add('descricao', TextType::class, [
                'label' => 'Descrição',
                'attr' => ['class' => 'form-control', 'maxlength' => 255],
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Campo obrigatorio']),
                    new Assert\Length(['max' => 255]),
                ],
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
            ])
            ->add('saldoAnterior', NumberType::class, [
                'label' => 'Saldo Anterior (R$)',
                'scale' => 2,
                'html5' => true,
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'step' => '0.01',
                    'placeholder' => '0,00',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AlmasaPlanoContas::class,
        ]);
    }
}
