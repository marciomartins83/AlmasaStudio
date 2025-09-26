<?php
namespace App\Form;

use App\Entity\Logradouros;
use App\Entity\Bairros;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LogradouroType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('logradouro', TextType::class, [
                'label' => 'Logradouro',
                'attr' => ['class' => 'form-control']
            ])
            ->add('cep', TextType::class, [
                'label' => 'CEP',
                'attr' => ['class' => 'form-control']
            ])
            ->add('bairro', EntityType::class, [
                'class' => Bairros::class,
                'choice_label' => 'nome',
                'label' => 'Bairro',
                'attr' => ['class' => 'form-control'],
                'placeholder' => 'Selecione um bairro'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Logradouros::class,
        ]);
    }
}
