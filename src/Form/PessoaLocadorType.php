<?php

namespace App\Form;

use App\Entity\FormasRetirada;
use App\Entity\PessoasLocadores;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PessoaLocadorType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('formaRetirada', EntityType::class, [
                'class' => FormasRetirada::class,
                'choice_label' => 'forma',
                'label' => 'Forma de Retirada',
                'required' => false,
                'placeholder' => 'Selecione...',
                'attr' => ['class' => 'form-select']
            ])
            ->add('dependentes', IntegerType::class, [
                'label' => 'Nº de Dependentes',
                'attr' => ['class' => 'form-control', 'min' => 0]
            ])
            ->add('diaRetirada', IntegerType::class, [
                'label' => 'Dia de Retirada',
                'required' => false,
                'attr' => ['class' => 'form-control', 'min' => 1, 'max' => 31]
            ])
            ->add('dataFechamento', DateType::class, [
                'label' => 'Data de Fechamento',
                'widget' => 'single_text',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('carencia', IntegerType::class, [
                'label' => 'Carência (dias)',
                'attr' => ['class' => 'form-control', 'min' => 0]
            ])
             ->add('situacao', IntegerType::class, [
                'label' => 'Situação',
                'attr' => ['class' => 'form-control']
            ])
            ->add('codigoContabil', IntegerType::class, [
                'label' => 'Código Contábil',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('protesto', IntegerType::class, [
                'label' => 'Protesto (dias)',
                'attr' => ['class' => 'form-control', 'min' => 0]
            ])
            ->add('diasProtesto', IntegerType::class, [
                'label' => 'Dias para Protesto',
                'attr' => ['class' => 'form-control', 'min' => 0]
            ])
            ->add('cobrarCpmf', CheckboxType::class, [
                'label' => 'Cobrar CPMF', 'required' => false
            ])
            ->add('etiqueta', CheckboxType::class, [
                'label' => 'Gerar Etiqueta', 'required' => false
            ])
            ->add('cobrarTarifaRec', CheckboxType::class, [
                'label' => 'Cobrar Tarifa de Rec.', 'required' => false
            ])
            ->add('multaItau', CheckboxType::class, [
                'label' => 'Multa Itaú', 'required' => false
            ])
            ->add('moraDiaria', CheckboxType::class, [
                'label' => 'Mora Diária', 'required' => false
            ])
            ->add('naoGerarJudicial', CheckboxType::class, [
                'label' => 'Não Gerar Judicial', 'required' => false
            ])
            ->add('enderecoCobranca', CheckboxType::class, [
                'label' => 'Usar Endereço de Cobrança', 'required' => false
            ])
            ->add('condominioConta', CheckboxType::class, [
                'label' => 'Condomínio em Conta', 'required' => false
            ])
            ->add('extEmail', CheckboxType::class, [
                'label' => 'Extrato por Email', 'required' => false
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PessoasLocadores::class,
        ]);
    }
}
