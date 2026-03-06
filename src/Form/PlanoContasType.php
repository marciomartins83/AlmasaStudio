<?php

namespace App\Form;

use App\Entity\PlanoContas;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class PlanoContasType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('codigo', TextType::class, [
                'label' => 'Código',
                'attr' => ['class' => 'form-control', 'maxlength' => 20],
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Campo obrigatório']),
                    new Assert\Length(['max' => 20, 'maxMessage' => 'Máximo 20 caracteres']),
                ],
            ])
            ->add('descricao', TextType::class, [
                'label' => 'Descrição',
                'attr' => ['class' => 'form-control', 'maxlength' => 100],
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Campo obrigatório']),
                    new Assert\Length(['max' => 100, 'maxMessage' => 'Máximo 100 caracteres']),
                ],
            ])
            ->add('tipo', ChoiceType::class, [
                'label' => 'Tipo',
                'attr' => ['class' => 'form-select'],
                'choices' => [
                    'Receita'     => 0,
                    'Despesa'     => 1,
                    'Transitória' => 2,
                    'Caixa'       => 3,
                ],
                'required' => true,
            ])
            ->add('codigoContabil', TextType::class, [
                'label' => 'Código Contábil',
                'attr' => ['class' => 'form-control', 'maxlength' => 20],
                'required' => false,
            ])
            ->add('incideTaxaAdmin', CheckboxType::class, [
                'label' => 'Incide Taxa de Administração?',
                'required' => false,
                'attr' => ['class' => 'form-check-input'],
                'label_attr' => ['class' => 'form-check-label'],
            ])
            ->add('incideIr', CheckboxType::class, [
                'label' => 'Incide IR?',
                'required' => false,
                'attr' => ['class' => 'form-check-input'],
                'label_attr' => ['class' => 'form-check-label'],
            ])
            ->add('entraInforme', CheckboxType::class, [
                'label' => 'Entra no Informe de Rendimentos?',
                'required' => false,
                'attr' => ['class' => 'form-check-input'],
                'label_attr' => ['class' => 'form-check-label'],
            ])
            ->add('entraDesconto', CheckboxType::class, [
                'label' => 'Entra no Desconto?',
                'required' => false,
                'attr' => ['class' => 'form-check-input'],
                'label_attr' => ['class' => 'form-check-label'],
            ])
            ->add('entraMulta', CheckboxType::class, [
                'label' => 'Entra na Multa?',
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
            'data_class' => PlanoContas::class,
        ]);
    }
}
