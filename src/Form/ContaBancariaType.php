<?php
namespace App\Form;

use App\Entity\ContasBancarias;
use App\Entity\TiposContasBancarias;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContaBancariaType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('idPessoa', HiddenType::class, [
                'mapped' => false,
                'required' => false,
            ])
            ->add('codigo', TextType::class, [
                'label' => 'Número da Conta',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Digite o número da conta']
            ])
            ->add('digitoConta', TextType::class, [
                'label' => 'Dígito',
                'required' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => 'Dígito verificador', 'maxlength' => '2']
            ])
            ->add('idBanco', HiddenType::class, [
                'mapped' => false,
                'required' => true,
            ])
            ->add('idAgencia', HiddenType::class, [
                'mapped' => false,
                'required' => true,
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
