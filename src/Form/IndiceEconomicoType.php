<?php

namespace App\Form;

use App\Entity\IndiceEconomico;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class IndiceEconomicoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('tipo', ChoiceType::class, [
                'label' => 'Índice',
                'choices' => [
                    'IGPM' => IndiceEconomico::TIPO_IGPM,
                    'Tribunal de Justiça' => IndiceEconomico::TIPO_TJ,
                ],
                'attr' => ['class' => 'form-select'],
                'constraints' => [new Assert\NotBlank(['message' => 'Campo obrigatório'])],
            ])
            ->add('competencia', TextType::class, [
                'label' => 'Competência (AAAA-MM)',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Ex: 2026-04'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Campo obrigatório']),
                    new Assert\Regex(['pattern' => '/^\d{4}-\d{2}$/', 'message' => 'Formato esperado: AAAA-MM']),
                ],
            ])
            ->add('tipoValor', ChoiceType::class, [
                'label' => 'Tipo de Valor',
                'choices' => [
                    'Índice' => IndiceEconomico::TIPO_VALOR_INDICE,
                    'Percentual' => IndiceEconomico::TIPO_VALOR_PERCENTUAL,
                ],
                'expanded' => true,
                'attr' => ['class' => 'form-check-input'],
                'constraints' => [new Assert\NotBlank(['message' => 'Campo obrigatório'])],
            ])
            ->add('valorIndice', NumberType::class, [
                'label' => 'Valor do Índice',
                'required' => false,
                'html5' => true,
                'scale' => 6,
                'attr' => ['class' => 'form-control', 'step' => '0.000001'],
            ])
            ->add('valorPercentual', NumberType::class, [
                'label' => 'Valor Percentual (%)',
                'required' => false,
                'html5' => true,
                'scale' => 4,
                'attr' => ['class' => 'form-control', 'step' => '0.0001'],
            ])
            ->add('observacoes', TextareaType::class, [
                'label' => 'Observações',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 3],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => IndiceEconomico::class]);
    }
}
