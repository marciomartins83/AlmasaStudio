<?php

namespace App\Tests\Form;

use App\Entity\Logradouros;
use App\Form\LogradouroType;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class LogradouroTypeTest extends KernelTestCase
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
            'logradouro' => 'Rua das Flores',
        ];

        $objectToCompare = new Logradouros();

        $form = $this->formFactory->create(LogradouroType::class, $objectToCompare);
        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertEquals('Rua das Flores', $form->get('logradouro')->getData());
    }

    public function testCustomFormView(): void
    {
        $object = new Logradouros();

        $form = $this->formFactory->create(LogradouroType::class, $object);

        $this->assertTrue($form->has('logradouro'));
    }
}
