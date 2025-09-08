<?php

namespace App\Form;

use App\Entity\Pessoas;
use App\Entity\EstadoCivil;
use App\Entity\Nacionalidade;
use App\Entity\Naturalidade;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
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
            ->add('pessoaId', HiddenType::class, ['mapped' => false])
            ->add('searchTerm', TextType::class, ['mapped' => false, 'label' => 'CPF/CNPJ'])
            ->add('nome', TextType::class)
            ->add('dataNascimento', DateType::class, ['widget' => 'single_text', 'required' => false])
            ->add('estadoCivil', EntityType::class, [
                'class'        => EstadoCivil::class,
                'choice_label' => 'nome',
                'placeholder'  => 'Selecione...',
                'label'        => 'Estado Civil',
                'required' => false,
            ])
            ->add('nacionalidade', EntityType::class, [
                'class'        => Nacionalidade::class,
                'choice_label' => 'nome',
                'placeholder'  => 'Selecione...',
                'label'        => 'Nacionalidade',
                'required' => false,
            ])
            ->add('naturalidade', EntityType::class, [
                'class'        => Naturalidade::class,
                'choice_label' => 'nome',
                'placeholder'  => 'Selecione...',
                'label'        => 'Naturalidade',
                'required' => false,
            ])
            ->add('nomePai', TextType::class, ['required' => false])
            ->add('nomeMae', TextType::class, ['required' => false])
            ->add('renda', MoneyType::class, ['currency' => 'BRL', 'required' => false])
            ->add('observacoes', TextareaType::class, ['required' => false])

            /* Tipo de pessoa para carregar sub-formulário */
            ->add('tipoPessoa', ChoiceType::class, [
                'choices' => [
                    'Selecione um tipo...' => null,
                    'Fiador'       => 'fiador',
                    'Corretor'     => 'corretor',
                    'Corretora'    => 'corretora',
                    'Locador'      => 'locador',
                    'Pretendente'  => 'pretendente',
                ],
                'mapped' => false,
                'attr' => [
                    'data-url' => $this->urlGenerator->generate('app_pessoa__subform'),
                ]
            ])

            /* Seções de contato */
            ->add('telefones', CollectionType::class, [
                'entry_type'   => TelefoneFormType::class, 'allow_add' => true, 'allow_delete' => true, 'by_reference' => false, 'label' => false,
            ])
            ->add('enderecos', CollectionType::class, [
                'entry_type'   => EnderecoType::class, 'allow_add' => true, 'allow_delete' => true, 'by_reference' => false, 'label' => false,
            ])
            ->add('emails', CollectionType::class, [
                'entry_type'   => EmailFormType::class, 'allow_add' => true, 'allow_delete' => true, 'by_reference' => false, 'label' => false,
            ])
            ->add('chavesPix', CollectionType::class, [
                'entry_type'   => ChavePixType::class, 'allow_add' => true, 'allow_delete' => true, 'by_reference' => false, 'label' => false,
            ])
            ->add('documentos', CollectionType::class, [
                'entry_type'   => DocumentoType::class, 'allow_add' => true, 'allow_delete' => true, 'by_reference' => false, 'label' => false,
            ])
            ->add('conjuge', HiddenType::class, ['required' => false, 'mapped' => false]); // Será tratado no controller

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
                ]);
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class'   => null, // Usaremos um DTO ou array, pois o formulário é complexo
        ]);
    }
}

