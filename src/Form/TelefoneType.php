<?php
namespace App\Form;

use App\Entity\Telefones;
use App\Entity\TiposTelefones;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TelefoneType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('tipo', EntityType::class, [
                'class' => TiposTelefones::class,
                'choice_label' => 'tipo',
                'label' => 'Tipo de Telefone',
                'attr' => ['class' => 'form-control']
            ])
            ->add('numero', TextType::class, [
                'label' => 'Número',
                'attr' => ['class' => 'form-control']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Telefones::class,
        ]);
    }
}
