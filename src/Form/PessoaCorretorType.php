<?php

namespace App\Form;

use App\Entity\PessoasCorretores;
use App\Entity\Pessoas;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PessoaCorretorType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('pessoa', EntityType::class, [
                'class' => Pessoas::class,
                'choice_label' => 'nome',
                'label' => 'Pessoa',
                'required' => true,
                'placeholder' => 'Selecione a pessoa...',
                'attr' => ['class' => 'form-select']
            ])
            ->add('creci', TextType::class, [
                'label' => 'CRECI',
                'required' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => 'Ex: 123456-F']
            ])
            ->add('usuario', TextType::class, [
                'label' => 'Usuário',
                'required' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => 'Nome de usuário no sistema']
            ])
            ->add('status', TextType::class, [
                'label' => 'Status',
                'required' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => 'Status do corretor']
            ])
            ->add('dataCadastro', DateType::class, [
                'label' => 'Data de Cadastro',
                'widget' => 'single_text',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('ativo', CheckboxType::class, [
                'label' => 'Ativo',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PessoasCorretores::class,
        ]);
    }
}
