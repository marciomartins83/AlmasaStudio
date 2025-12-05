<?php

namespace App\Form;

use App\Entity\PessoasAdvogados;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PessoaAdvogadoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('numeroOab', TextType::class, [
                'label' => 'Número OAB',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: 123456'
                ]
            ])
            ->add('seccionalOab', ChoiceType::class, [
                'label' => 'Seccional OAB',
                'required' => true,
                'placeholder' => 'UF...',
                'choices' => [
                    'AC' => 'AC', 'AL' => 'AL', 'AP' => 'AP', 'AM' => 'AM',
                    'BA' => 'BA', 'CE' => 'CE', 'DF' => 'DF', 'ES' => 'ES',
                    'GO' => 'GO', 'MA' => 'MA', 'MT' => 'MT', 'MS' => 'MS',
                    'MG' => 'MG', 'PA' => 'PA', 'PB' => 'PB', 'PR' => 'PR',
                    'PE' => 'PE', 'PI' => 'PI', 'RJ' => 'RJ', 'RN' => 'RN',
                    'RS' => 'RS', 'RO' => 'RO', 'RR' => 'RR', 'SC' => 'SC',
                    'SP' => 'SP', 'SE' => 'SE', 'TO' => 'TO',
                ],
                'attr' => ['class' => 'form-select']
            ])
            ->add('especialidade', TextType::class, [
                'label' => 'Especialidade',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: Direito Imobiliário'
                ]
            ])
            ->add('observacoes', TextareaType::class, [
                'label' => 'Observações',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3
                ]
            ])
            ->add('ativo', CheckboxType::class, [
                'label' => 'Ativo',
                'required' => false,
                'attr' => ['class' => 'form-check-input']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PessoasAdvogados::class,
        ]);
    }
}
