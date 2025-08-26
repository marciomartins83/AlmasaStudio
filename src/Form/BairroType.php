<?php
namespace App\Form;

use App\Entity\Bairros;
use App\Entity\Cidades;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BairroType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nome', TextType::class, [
                'label' => 'Nome',
                'attr' => ['class' => 'form-control']
            ])
            ->add('cidade', EntityType::class, [
                'class' => Cidades::class,
                'choice_label' => 'nome',
                'label' => 'Cidade',
                'attr' => ['class' => 'form-control'],
                'choices' => $options['cidades'] ?? []
            ])
            ->add('codigo', TextType::class, [
                'label' => 'Código',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Bairros::class,
            'cidades' => null
        ]);
        
        $resolver->setAllowedTypes('cidades', ['array', 'null']);
    }
}
