<?php

namespace App\Tests\Form;

use App\Entity\ContasBancarias;
use App\Form\ContaBancariaType;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ContaBancariaTypeTest extends KernelTestCase
{
    private $formFactory;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->formFactory = self::getContainer()->get('form.factory');
    }

    protected function tearDown(): void
    {
        self::ensureKernelShutdown();
        parent::tearDown();
    }

    public function testSubmitValidData(): void
    {
        $formData = [
            'codigo'      => 'Test Value 1',
            'digitoConta' => 'Test Value 2',
        ];

        $objectToCompare = new ContasBancarias();

        $form = $this->formFactory->create(ContaBancariaType::class, $objectToCompare);
        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertEquals('Test Value 1', $form->get('codigo')->getData());
        $this->assertEquals('Test Value 2', $form->get('digitoConta')->getData());
    }

    public function testCustomFormView(): void
    {
        $object = new ContasBancarias();

        $form = $this->formFactory->create(ContaBancariaType::class, $object);

        $this->assertTrue($form->has('codigo'));
        $this->assertTrue($form->has('digitoConta'));
    }
}
