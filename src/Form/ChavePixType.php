<?php

namespace App\Form;

use App\Entity\ChavesPix;
use App\Entity\TiposChavesPix;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ChavePixType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('chavePix', TextType::class, [
                'label' => 'Chave PIX',
                'attr'  => [
                    'placeholder' => 'Informe a chave PIX',
                    'class'       => 'pix-chave-input',
                ],
            ])
            ->add('idTipoChave', EntityType::class, [
                'class'         => TiposChavesPix::class,
                'choice_label'  => 'tipo',
                'placeholder'   => 'Selecione o tipo',
                'label'         => 'Tipo de Chave',
                'attr'          => [
                    'class' => 'pix-tipo-select',
                ],
            ])
            ->add('principal', ChoiceType::class, [
                'choices'  => [
                    'Sim' => true,
                    'Não' => false,
                ],
                'label'    => 'Principal',
                'expanded' => true,
                'attr'     => [
                    'class' => 'pix-principal-radio',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ChavesPix::class,
        ]);
    }
}