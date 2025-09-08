<?php

namespace App\Form;

use App\Entity\PessoasContratantes;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PessoaContratanteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Este formulário é intencionalmente vazio.
        // Ele serve apenas para identificar o tipo de pessoa no controller
        // e para renderizar o card informativo no template.
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PessoasContratantes::class,
        ]);
    }
}