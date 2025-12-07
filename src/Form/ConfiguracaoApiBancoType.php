<?php

namespace App\Form;

use App\Entity\ConfiguracoesApiBanco;
use App\Entity\Bancos;
use App\Entity\ContasBancarias;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class ConfiguracaoApiBancoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('banco', EntityType::class, [
                'class' => Bancos::class,
                'choice_label' => function (Bancos $banco) {
                    return sprintf('%03d - %s', $banco->getNumero(), $banco->getNome());
                },
                'label' => 'Banco',
                'placeholder' => 'Selecione o banco...',
                'attr' => ['class' => 'form-select'],
                'required' => true
            ])
            ->add('contaBancaria', EntityType::class, [
                'class' => ContasBancarias::class,
                'choice_label' => function (ContasBancarias $conta) {
                    $titular = $conta->getTitular() ?? 'Sem titular';
                    return sprintf('%s-%s (%s)', $conta->getCodigo(), $conta->getDigitoConta() ?? '0', $titular);
                },
                'label' => 'Conta Bancária',
                'placeholder' => 'Selecione a conta...',
                'attr' => ['class' => 'form-select'],
                'required' => true
            ])
            ->add('convenio', TextType::class, [
                'label' => 'Convênio',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: 006248357',
                    'maxlength' => 20
                ],
                'required' => true
            ])
            ->add('carteira', ChoiceType::class, [
                'label' => 'Carteira',
                'choices' => [
                    '101 - Cobrança Simples' => '101',
                    '102 - Cobrança Simples - Emitido pelo Cliente' => '102',
                    '201 - Penhor Rápido' => '201'
                ],
                'attr' => ['class' => 'form-select'],
                'required' => true
            ])
            ->add('ambiente', ChoiceType::class, [
                'label' => 'Ambiente',
                'choices' => [
                    'Sandbox (Testes)' => 'sandbox',
                    'Produção' => 'producao'
                ],
                'attr' => ['class' => 'form-select'],
                'required' => true
            ])
            ->add('clientId', TextType::class, [
                'label' => 'Client ID',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'ID fornecido pelo banco'
                ],
                'required' => false
            ])
            ->add('clientSecret', PasswordType::class, [
                'label' => 'Client Secret',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Secret fornecido pelo banco',
                    'autocomplete' => 'new-password'
                ],
                'required' => false,
                'always_empty' => false
            ])
            ->add('workspaceId', TextType::class, [
                'label' => 'Workspace ID',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'ID do workspace (opcional)'
                ],
                'required' => false
            ])
            ->add('certificadoArquivo', FileType::class, [
                'label' => 'Certificado Digital (A1)',
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'accept' => '.pfx,.p12'
                ],
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => [
                            'application/x-pkcs12',
                            'application/octet-stream'
                        ],
                        'mimeTypesMessage' => 'Por favor, envie um certificado válido (.pfx ou .p12)'
                    ])
                ],
                'help' => 'Formatos aceitos: .pfx, .p12 (máximo 5MB)'
            ])
            ->add('certificadoSenha', PasswordType::class, [
                'label' => 'Senha do Certificado',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Senha para abrir o certificado',
                    'autocomplete' => 'new-password'
                ],
                'required' => false,
                'always_empty' => false
            ])
            ->add('ativo', CheckboxType::class, [
                'label' => 'Configuração Ativa',
                'required' => false,
                'attr' => ['class' => 'form-check-input']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ConfiguracoesApiBanco::class,
        ]);
    }
}
