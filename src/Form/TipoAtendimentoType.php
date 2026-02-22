<?php
namespace App\Form;

use App\Entity\TiposAtendimento;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class TipoAtendimentoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('tipo', TextType::class, [
                'label' => 'Tipo',
                'attr' => ['class' => 'form-control'],
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Campo obrigatório']),
                    new Assert\Length([
                        'min' => 2,
                        'max' => 255,
                        'minMessage' => 'Mínimo 2 caracteres',
                        'maxMessage' => 'Máximo 255 caracteres',
                    ]),
                ],
            ])
            ->add('descricao', TextType::class, [
                'label' => 'Descricao',
                'attr' => ['class' => 'form-control'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TiposAtendimento::class,
        ]);
    }
}
