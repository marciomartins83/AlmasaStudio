<?php
namespace App\Form;

use App\Entity\Agencias;
use App\Entity\Bancos;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AgenciaType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('codigo', TextType::class, [
                'label' => 'Codigo',
                'attr' => ['class' => 'form-control']
            ])
            ->add('banco', EntityType::class, [
                'class' => Bancos::class,
                'choice_label' => 'nome',
                'label' => 'Banco',
                'attr' => ['class' => 'form-select']
            ])
            ->add('nome', TextType::class, [
                'label' => 'Nome',
                'attr' => ['class' => 'form-control']
            ])
            ->add('endereco', EnderecoType::class, [
                'label' => 'Endereço',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Agencias::class,
        ]);
    }
}
