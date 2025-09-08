<?php

namespace App\Form;

use App\Entity\TiposImoveis;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class TipoImovelType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('tipo', TextType::class, [
                'label' => 'Tipo de Imóvel',
                'attr' => ['class' => 'form-control'],
                'required' => true
            ])
            ->add('descricao', TextareaType::class, [
                'label' => 'Descrição',
                'attr' => ['class' => 'form-control', 'rows' => 3],
                'required' => false
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TiposImoveis::class,
        ]);
    }
}
