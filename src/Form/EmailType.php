<?php
namespace App\Form;

use App\Entity\Emails;
use App\Entity\TiposEmails;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EmailType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', TextType::class, [
                'label' => 'Email',
                'attr' => ['class' => 'form-control']
            ])
            ->add('tipo', EntityType::class, [
                'class' => TiposEmails::class,
                'choice_label' => 'tipo',
                'label' => 'Tipo de Email',
                'attr' => ['class' => 'form-select'],
            ])
            ->add('descricao', TextType::class, [
                'label' => 'Descrição',
                'attr' => ['class' => 'form-control'],
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Emails::class,
        ]);
    }
}
