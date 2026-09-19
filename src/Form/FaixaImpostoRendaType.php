<?php

namespace App\Form;

use App\Entity\FaixaImpostoRenda;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class FaixaImpostoRendaType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('dataVigencia', DateType::class, [
                'label' => 'Data de Vigência',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
                'constraints' => [new Assert\NotBlank(['message' => 'Campo obrigatório'])],
            ])
            ->add('valorInicial', NumberType::class, [
                'label' => 'Valor Inicial (R$)',
                'html5' => true,
                'scale' => 2,
                'attr' => ['class' => 'form-control', 'step' => '0.01'],
                'constraints' => [new Assert\NotBlank(['message' => 'Campo obrigatório'])],
            ])
            ->add('valorFinal', NumberType::class, [
                'label' => 'Valor Final (R$) — deixe vazio se for a última faixa',
                'required' => false,
                'html5' => true,
                'scale' => 2,
                'attr' => ['class' => 'form-control', 'step' => '0.01'],
            ])
            ->add('aliquota', NumberType::class, [
                'label' => 'Alíquota (%)',
                'html5' => true,
                'scale' => 2,
                'attr' => ['class' => 'form-control', 'step' => '0.01'],
                'constraints' => [new Assert\NotBlank(['message' => 'Campo obrigatório'])],
            ])
            ->add('parcelaDeduzir', NumberType::class, [
                'label' => 'Parcela a Deduzir (R$)',
                'html5' => true,
                'scale' => 2,
                'attr' => ['class' => 'form-control', 'step' => '0.01'],
            ])
            ->add('ativo', CheckboxType::class, [
                'label' => 'Ativo',
                'required' => false,
                'attr' => ['class' => 'form-check-input'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => FaixaImpostoRenda::class]);
    }
}
