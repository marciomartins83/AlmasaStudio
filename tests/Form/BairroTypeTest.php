<?php

namespace App\Tests\Form;

use App\Entity\Bairros;
use App\Form\BairroType;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class BairroTypeTest extends KernelTestCase
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
            'nome' => 'Test Value',
        ];

        $objectToCompare = new Bairros();

        $form = $this->formFactory->create(BairroType::class, $objectToCompare);
        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertEquals('Test Value', $form->get('nome')->getData());
    }

    public function testCustomFormView(): void
    {
        $object = new Bairros();

        $form = $this->formFactory->create(BairroType::class, $object);

        $this->assertTrue($form->has('nome'));
    }
}
