<?php

namespace App\Form;

use App\Entity\Pessoas;
use App\Entity\EstadoCivil;
use App\Entity\Nacionalidade;
use App\Entity\Naturalidade;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PessoaFormType extends AbstractType
{
    private UrlGeneratorInterface $urlGenerator;

    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // CAMPOS PARA BUSCA/EDIÇÃO (NÃO MAPEADOS)
            ->add('pessoaId', HiddenType::class, [
                'mapped' => false,
                'attr' => ['class' => 'form-control']
            ])
            
            ->add('searchTerm', TextType::class, [
                'mapped' => false, 
                'label' => 'CPF/CNPJ',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Digite CPF ou CNPJ para buscar'
                ],
                'required' => false
            ])
            
            // CAMPOS QUE EXISTEM NA TABELA PESSOAS (MAPEADOS)
            ->add('nome', TextType::class, [
                'label' => 'Nome Completo',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Digite o nome completo'
                ]
            ])
            
            ->add('dataNascimento', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Data de Nascimento',
                'attr' => ['class' => 'form-control'],
                'required' => false
            ])
            
            ->add('estadoCivil', EntityType::class, [
                'class' => EstadoCivil::class,
                'choice_label' => 'nome',
                'placeholder' => 'Selecione...',
                'label' => 'Estado Civil',
                'attr' => ['class' => 'form-select'],
                'required' => false,
            ])
            
            ->add('nacionalidade', EntityType::class, [
                'class' => Nacionalidade::class,
                'choice_label' => 'nome',
                'placeholder' => 'Selecione...',
                'label' => 'Nacionalidade',
                'attr' => ['class' => 'form-select'],
                'required' => false,
            ])
            
            ->add('naturalidade', EntityType::class, [
                'class' => Naturalidade::class,
                'choice_label' => 'nome',
                'placeholder' => 'Selecione...',
                'label' => 'Naturalidade',
                'attr' => ['class' => 'form-select'],
                'required' => false,
            ])
            
            ->add('nomePai', TextType::class, [
                'label' => 'Nome do Pai',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Digite o nome do pai'
                ],
                'required' => false
            ])
            
            ->add('nomeMae', TextType::class, [
                'label' => 'Nome da Mãe',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Digite o nome da mãe'
                ],
                'required' => false
            ])
            
            ->add('renda', MoneyType::class, [
                'currency' => 'BRL',
                'label' => 'Renda',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '0,00'
                ],
                'required' => false
            ])
            
            ->add('observacoes', TextareaType::class, [
                'label' => 'Observações',
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'Digite observações sobre a pessoa'
                ],
                'required' => false
            ])

            // TIPO DE PESSOA (NÃO MAPEADO - PROCESSADO PELO CONTROLLER)
            ->add('tipoPessoa', ChoiceType::class, [
                'choices' => [
                    'Selecione um tipo...' => '',
                    'Fiador' => 'fiador',
                    'Corretor' => 'corretor',
                    'Corretora' => 'corretora',
                    'Locador' => 'locador',
                    'Pretendente' => 'pretendente',
                    'Contratante' => 'contratante',
                ],
                'mapped' => false,
                'label' => 'Tipo de Pessoa',
                'attr' => [
                    'class' => 'form-select',
                    'data-url' => $this->urlGenerator->generate('app_pessoa__subform'),
                ]
            ])

            // CAMPOS PARA CÔNJUGE (NÃO MAPEADOS - PROCESSADOS VIA JAVASCRIPT/CONTROLLER)
            ->add('conjuge', HiddenType::class, [
                'required' => false,
                'mapped' => false,
                'attr' => ['class' => 'form-control']
            ]);

        // LISTENER PARA SUB-FORMULÁRIOS
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $data = $event->getData();
            $form = $event->getForm();
            $tipo = $data['tipoPessoa'] ?? null;

            if (!$tipo) {
                return;
            }

            $subFormType = match ($tipo) {
                'fiador' => PessoaFiadorType::class,
                'corretor' => PessoaCorretorType::class,
                'corretora' => PessoaCorretoraType::class,
                'locador' => PessoaLocadorType::class,
                'pretendente' => PessoaPretendenteType::class,
                default => null,
            };

            if ($subFormType) {
                $form->add($tipo, $subFormType, [
                    'label' => false,
                    'mapped' => false,
                ]);
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Pessoas::class,
        ]);
    }
}