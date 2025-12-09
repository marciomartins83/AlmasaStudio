<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\PrestacoesContas;
use App\Entity\ContasBancarias;
use App\Repository\ContasBancariasRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

/**
 * Formulário para registro de repasse ao proprietário
 */
class PrestacaoContasRepasseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('dataRepasse', DateType::class, [
                'label' => 'Data do Repasse',
                'widget' => 'single_text',
                'data' => new \DateTime(),
                'attr' => ['class' => 'form-control'],
                'required' => true,
            ])
            ->add('formaRepasse', ChoiceType::class, [
                'label' => 'Forma de Repasse',
                'choices' => [
                    'Selecione...' => '',
                    'PIX' => PrestacoesContas::FORMA_PIX,
                    'TED' => PrestacoesContas::FORMA_TED,
                    'Depósito' => PrestacoesContas::FORMA_DEPOSITO,
                    'Cheque' => PrestacoesContas::FORMA_CHEQUE,
                    'Dinheiro' => PrestacoesContas::FORMA_DINHEIRO,
                ],
                'attr' => ['class' => 'form-select'],
                'required' => true,
            ])
            ->add('contaBancaria', EntityType::class, [
                'class' => ContasBancarias::class,
                'label' => 'Conta Bancária',
                'choice_label' => function (ContasBancarias $conta) {
                    $banco = $conta->getIdBanco();
                    $bancoNome = $banco ? $banco->getNome() : 'Sem banco';
                    return $bancoNome . ' - ' . $conta->getCodigo();
                },
                'query_builder' => function (ContasBancariasRepository $repo) {
                    return $repo->createQueryBuilder('c')
                        ->where('c.ativo = true')
                        ->orderBy('c.id', 'ASC');
                },
                'placeholder' => 'Selecione a conta...',
                'attr' => ['class' => 'form-select'],
                'required' => false,
            ])
            ->add('comprovante', FileType::class, [
                'label' => 'Comprovante (PDF/Imagem)',
                'mapped' => false,
                'required' => false,
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => [
                            'application/pdf',
                            'image/jpeg',
                            'image/png',
                        ],
                        'mimeTypesMessage' => 'Envie um arquivo PDF, JPEG ou PNG válido.',
                    ]),
                ],
            ])
            ->add('observacoes', TextareaType::class, [
                'label' => 'Observações',
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                ],
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'ajax_global',
        ]);
    }
}
