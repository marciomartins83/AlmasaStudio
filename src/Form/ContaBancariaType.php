<?php
namespace App\Form;

use App\Entity\ContasBancarias;
use App\Entity\Agencias;
use App\Entity\Bancos;
use App\Entity\Pessoas;
use App\Entity\TiposContasBancarias;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContaBancariaType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('codigo', TextType::class, [
                'label' => 'Código da Conta',
                'attr' => ['class' => 'form-control']
            ])
            ->add('digitoConta', TextType::class, [
                'label' => 'Dígito da Conta',
                'attr' => ['class' => 'form-control']
            ])
            ->add('titular', TextType::class, [
                'label' => 'Nome do Titular',
                'attr' => ['class' => 'form-control']
            ])
            ->add('idPessoa', EntityType::class, [
                'class' => Pessoas::class,
                'choice_label' => 'nome',
                'label' => 'Pessoa',
                'attr' => ['class' => 'form-control']
            ])
            ->add('idBanco', EntityType::class, [
                'class' => Bancos::class,
                'choice_label' => 'nome',
                'label' => 'Banco',
                'attr' => ['class' => 'form-control']
            ])
            ->add('idAgencia', EntityType::class, [
                'class' => Agencias::class,
                'choice_label' => 'codigo',
                'label' => 'Agência',
                'attr' => ['class' => 'form-control']
            ])
            ->add('idTipoConta', EntityType::class, [
                'class' => TiposContasBancarias::class,
                'choice_label' => 'tipo',
                'label' => 'Tipo de Conta',
                'attr' => ['class' => 'form-control']
            ])
            ->add('principal', CheckboxType::class, [
                'label' => 'Conta Principal',
                'required' => false,
                'attr' => ['class' => 'form-check-input'],
            ])
            ->add('ativo', CheckboxType::class, [
                'label' => 'Ativo',
                'required' => false,
                'attr' => ['class' => 'form-check-input'],
            ])
            ->add('registrada', CheckboxType::class, [
                'label' => 'Registrada',
                'required' => false,
                'attr' => ['class' => 'form-check-input'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ContasBancarias::class,
        ]);
    }
}
