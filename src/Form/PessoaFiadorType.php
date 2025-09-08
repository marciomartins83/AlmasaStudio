<?php

namespace App\Form;

use App\Entity\PessoasFiadores;
use App\Entity\FormasRetirada;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PessoaFiadorType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('motivoFianca', TextareaType::class, [
                'label' => 'Motivo da Fiança',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3
                ]
            ])
            ->add('jaFoiFiador', CheckboxType::class, [
                'label' => 'Já foi fiador anteriormente?',
                'required' => false,
                'attr' => ['class' => 'form-check-input'],
            ])
            ->add('conjugeTrabalha', CheckboxType::class, [
                'label' => 'Cônjuge trabalha?',
                'required' => false,
                'attr' => ['class' => 'form-check-input'],
            ])
            ->add('outros', TextareaType::class, [
                'label' => 'Outras Informações',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3
                ]
            ])
            ->add('idFormaRetirada', EntityType::class, [
                'class' => FormasRetirada::class,
                'choice_label' => 'forma',
                'label' => 'Forma de Retirada',
                'placeholder' => 'Selecione a forma de retirada',
                'required' => false,
                'attr' => ['class' => 'form-select']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PessoasFiadores::class,
        ]);
    }
}