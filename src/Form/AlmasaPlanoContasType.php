<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\AlmasaPlanoContas;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class AlmasaPlanoContasType extends AbstractType
{
    /**
     * Transformer para converter valor monetario (string com virgula) para formato DB (string com ponto).
     */
    private function createSaldoTransformer(): DataTransformerInterface
    {
        return new class implements DataTransformerInterface {
            public function transform(?string $value): string
            {
                if ($value === null || $value === '') {
                    return '0,00';
                }

                return number_format($this->normalizeToFloat($value), 2, ',', '');
            }

            public function reverseTransform(?string $value): string
            {
                if ($value === null || trim($value) === '') {
                    return '0.00';
                }

                return sprintf('%.2F', $this->normalizeToFloat($value));
            }

            private function normalizeToFloat(string $value): float
            {
                $normalized = preg_replace('/[^0-9,.\-]/', '', $value) ?? '';

                if ($normalized === '' || $normalized === '-' || $normalized === ',' || $normalized === '.') {
                    return 0.0;
                }

                $hasComma = str_contains($normalized, ',');
                $hasDot = str_contains($normalized, '.');

                if ($hasComma && $hasDot) {
                    $normalized = str_replace('.', '', $normalized);
                }

                if ($hasComma) {
                    $normalized = str_replace(',', '.', $normalized);
                }

                if (substr_count($normalized, '.') > 1) {
                    $lastDotPosition = strrpos($normalized, '.');

                    if ($lastDotPosition !== false) {
                        $integerPart = str_replace('.', '', substr($normalized, 0, $lastDotPosition));
                        $decimalPart = substr($normalized, $lastDotPosition + 1);
                        $normalized = $integerPart . '.' . $decimalPart;
                    }
                }

                return (float) $normalized;
            }
        };
    }

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
            ->add('saldoAnterior', TextType::class, [
                'label' => 'Saldo Anterior (R$)',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '0,00',
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Campo obrigatorio']),
                ],
            ]);

        // Adiciona transformer para converter formato monetario
        $builder->get('saldoAnterior')->addModelTransformer($this->createSaldoTransformer());
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AlmasaPlanoContas::class,
        ]);
    }
}
