<?php
namespace App\Form;

use App\Entity\TiposPessoas;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class TipoPessoaType extends AbstractType
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
                'required' => false,
                'constraints' => [
                    new Assert\Length([
                        'max' => 500,
                        'maxMessage' => 'Máximo 500 caracteres',
                    ]),
                ],
            ])
            ->add('ativo', CheckboxType::class, [
                'label' => 'Ativo',
                'required' => false,
                'attr' => ['class' => 'form-check-input']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TiposPessoas::class,
        ]);
    }
}
