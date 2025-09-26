<?php

namespace App\Form;

use App\Entity\PessoasFiadores;
use App\Entity\FormasRetirada;
use App\Entity\Pessoas;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

class PessoaFiadorCombinedType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('pessoa', Pessoas::class, [
                'label' => 'Dados da Pessoa'
            ])
            ->add('idConjuge', EntityType::class, [
                'class' => Pessoas::class,
                'choice_label' => 'nome',
                'label' => 'Cônjuge (Opcional)',
                'required' => false,
                'attr' => ['class' => 'form-select']
            ])
            ->add('motivoFianca', TextareaType::class, [
                'label' => 'Motivo da Fiador',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 3]
            ])
            ->add('jaFoiFiador', CheckboxType::class, [
                'label' => 'Já foi fiador?',
                'required' => false,
                'attr' => ['class' => 'form-check-input']
            ])
            ->add('conjugeTrabalha', CheckboxType::class, [
                'label' => 'Cônjuge trabalha?',
                'required' => false,
                'attr' => ['class' => 'form-check-input']
            ])
            ->add('outros', TextareaType::class, [
                'label' => 'Outras informações',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 3]
            ])
            ->add('idFormaRetirada', EntityType::class, [
                'class' => FormasRetirada::class,
                'choice_label' => 'nome',
                'label' => 'Forma de Retirada (Opcional)',
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
