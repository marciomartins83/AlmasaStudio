<?php

namespace App\Form;

use App\Entity\PessoasLocadores;
use App\Entity\FormasRetirada;
use App\Entity\Pessoas;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;

class PessoaLocadorCombinedType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('pessoa', PessoaType::class, [
                'label' => 'Dados da Pessoa'
            ])
            ->add('idFormaRetirada', EntityType::class, [
                'class' => FormasRetirada::class,
                'choice_label' => 'nome',
                'label' => 'Forma de Retirada',
                'required' => false,
                'attr' => ['class' => 'form-select']
            ])
            ->add('dependentes', IntegerType::class, [
                'label' => 'Número de Dependentes',
                'attr' => ['class' => 'form-control']
            ])
            ->add('diaRetirada', IntegerType::class, [
                'label' => 'Dia de Retirada',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('cobrarCpmf', CheckboxType::class, [
                'label' => 'Cobrar CPMF',
                'required' => false,
                'attr' => ['class' => 'form-check-input']
            ])
            ->add('etiqueta', CheckboxType::class, [
                'label' => 'Gerar Etiqueta',
                'required' => false,
                'attr' => ['class' => 'form-check-input']
            ])
            ->add('cobrarTarifaRec', CheckboxType::class, [
                'label' => 'Cobrar Tarifa de Recuperação',
                'required' => false,
                'attr' => ['class' => 'form-check-input']
            ])
            ->add('dataFechamento', DateType::class, [
                'label' => 'Data de Fechamento',
                'widget' => 'single_text',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('carencia', IntegerType::class, [
                'label' => 'Carência (dias)',
                'attr' => ['class' => 'form-control']
            ])
            ->add('multaItau', CheckboxType::class, [
                'label' => 'Multa Itaú',
                'required' => false,
                'attr' => ['class' => 'form-check-input']
            ])
            ->add('moraDiaria', CheckboxType::class, [
                'label' => 'Mora Diária',
                'required' => false,
                'attr' => ['class' => 'form-check-input']
            ])
            ->add('protesto', IntegerType::class, [
                'label' => 'Protesto',
                'attr' => ['class' => 'form-control']
            ])
            ->add('diasProtesto', IntegerType::class, [
                'label' => 'Dias para Protesto',
                'attr' => ['class' => 'form-control']
            ])
            ->add('naoGerarJudicial', CheckboxType::class, [
                'label' => 'Não Gerar Judicial',
                'required' => false,
                'attr' => ['class' => 'form-check-input']
            ])
            ->add('enderecoCobranca', CheckboxType::class, [
                'label' => 'Endereço de Cobrança',
                'required' => false,
                'attr' => ['class' => 'form-check-input']
            ])
            ->add('condominioConta', CheckboxType::class, [
                'label' => 'Condomínio em Conta',
                'required' => false,
                'attr' => ['class' => 'form-check-input']
            ])
            ->add('extEmail', CheckboxType::class, [
                'label' => 'Extrato por Email',
                'required' => false,
                'attr' => ['class' => 'form-check-input']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PessoasLocadores::class,
        ]);
    }
}
