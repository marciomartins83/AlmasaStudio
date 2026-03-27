<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\AlmasaVinculoBancario;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AlmasaVinculoBancarioType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('contaBancaria', HiddenType::class, [
                'mapped' => false,
                'required' => true,
            ])
            ->add('almasaPlanoConta', HiddenType::class, [
                'mapped' => false,
                'required' => true,
            ])
            ->add('observacao', TextareaType::class, [
                'label' => 'Observação',
                'attr' => ['class' => 'form-control', 'rows' => 3, 'maxlength' => 255],
                'required' => false,
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
            'data_class' => AlmasaVinculoBancario::class,
        ]);
    }
}
