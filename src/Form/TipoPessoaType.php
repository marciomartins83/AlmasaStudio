<?php
namespace App\Form;

use App\Entity\TiposPessoas;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TipoPessoaType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('tipo', TextType::class, [
                'label' => 'Tipo',
                'attr' => ['class' => 'form-control']
            ])
            ->add('descricao', TextType::class, [
                'label' => 'Descricao',
                'attr' => ['class' => 'form-control']
            ])
            ->add('ativo', CheckboxType::class, [
                'label' => 'Ativo',
                'required' => false,
                'attr' => ['class' => 'form-check-input']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TiposPessoas::class,
        ]);
    }
}