<?php

namespace App\Form;

use App\Entity\Emails;
use App\Entity\TiposEmails;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type as FormTypes;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EmailFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', FormTypes\EmailType::class, [
                'label' => 'Email',
                'attr'  => [
                    'class'       => 'form-control email-input',
                    'placeholder' => 'exemplo@email.com',
                ],
            ])
            ->add('tipo', EntityType::class, [
                'class'        => TiposEmails::class,
                'choice_label' => 'tipo',
                'placeholder'  => 'Selecione o tipo',
                'label'        => 'Tipo de Email',
                'attr'         => [
                    'class' => 'form-select email-tipo-select',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Emails::class,
        ]);
    }
}