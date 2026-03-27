<?php

namespace App\Form;

use App\Entity\PessoasAdvogados;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
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
            ->add('seccionalOab', HiddenType::class, [
                'required' => true,
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
