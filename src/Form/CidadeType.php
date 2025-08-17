<?php
namespace App\Form;

use App\Entity\Cidades;
use App\Entity\Estados;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CidadeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nome', TextType::class, [
                'label' => 'Nome',
                'attr' => ['class' => 'form-control']
            ])
            ->add('codigo', TextType::class, [
                'label' => 'Código IBGE',
                'attr' => ['class' => 'form-control']
            ])
            ->add('estado', EntityType::class, [
                'class' => Estados::class,
                'choice_label' => 'nome',
                'required' => true,
                'attr' => ['class' => 'form-control']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Cidades::class,
        ]);
    }
}
