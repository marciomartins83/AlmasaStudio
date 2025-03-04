<?php

namespace App\Form;

use App\Entity\Pessoa;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;

class PessoaType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nome', TextType::class, [
                'label' => 'Nome',
                'attr' => ['class' => 'form-control']
            ])
            ->add('dtCadastro', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Data de Cadastro',
                'attr' => ['class' => 'form-control']
            ])
            ->add('tipoPessoa', ChoiceType::class, [
                'label' => 'Tipo de Pessoa',
                'choices' => [
                    'Física' => 1,
                    'Jurídica' => 2
                ],
                'attr' => ['class' => 'form-select']
            ])
            ->add('status', CheckboxType::class, [
                'label' => 'Ativo',
                'required' => false,
                'attr' => ['class' => 'form-check-input']
            ])
            ->add('criarUsuario', CheckboxType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'Criar Usuário?',
                'attr' => [
                    'class' => 'form-check-input',
                    'onchange' => 'toggleUserFields()'
                ]
            ])
            ->add('email', EmailType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'E-mail do usuário',
                'attr' => ['class' => 'form-control', 'id' => 'userEmailField']
            ])
            ->add('password', PasswordType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'Senha do usuário',
                'attr' => ['class' => 'form-control', 'id' => 'userPasswordField']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Pessoa::class,
        ]);
    }
}
