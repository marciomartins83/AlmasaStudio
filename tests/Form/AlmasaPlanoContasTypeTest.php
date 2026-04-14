<?php

declare(strict_types=1);

namespace App\Tests\Form;

use App\Entity\AlmasaPlanoContas;
use App\Form\AlmasaPlanoContasType;
use Symfony\Component\Form\Test\TypeTestCase;

class AlmasaPlanoContasTypeTest extends TypeTestCase
{
    public function testSubmitValidDataNormalizesBrazilianThousandsSeparator(): void
    {
        $formData = [
            'nivel' => (string) AlmasaPlanoContas::NIVEL_CONTA,
            'tipo' => AlmasaPlanoContas::TIPO_ATIVO,
            'codigo' => '1.01.001',
            'descricao' => 'Conta Caixa',
            'aceitaLancamentos' => true,
            'ativo' => true,
            'saldoAnterior' => '1.234,56',
        ];

        $object = new AlmasaPlanoContas();
        $form = $this->factory->create(AlmasaPlanoContasType::class, $object);

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertSame('1234.56', $object->getSaldoAnterior());
    }

    public function testTransformFormatsDatabaseValueWithTwoDecimalPlaces(): void
    {
        $object = new AlmasaPlanoContas();
        $object->setSaldoAnterior('1000');

        $form = $this->factory->create(AlmasaPlanoContasType::class, $object);

        $this->assertSame('1000,00', $form->get('saldoAnterior')->getViewData());
    }
}
