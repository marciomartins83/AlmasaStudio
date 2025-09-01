<?php

namespace App\Form;

use App\Entity\Telefones;
use App\Entity\TiposTelefones;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TelefoneFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('tipo', EntityType::class, [
                'class'        => TiposTelefones::class,
                'choice_label' => 'tipo',
                'label'        => 'Tipo de Telefone',
                'attr'         => [
                    'class' => 'form-select telefone-tipo-select',
                ],
            ])
            ->add('numero', TextType::class, [
                'label' => 'Número',
                'attr'  => [
                    'class'       => 'form-control telefone-numero-input',
                    'placeholder' => '(11) 99999-9999',
                    'data-mask'   => '(00) 00000-0000',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Telefones::class,
        ]);
    }
}