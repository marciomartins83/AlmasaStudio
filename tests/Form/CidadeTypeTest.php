<?php

namespace App\Tests\Form;

use App\Entity\Cidades;
use App\Form\CidadeType;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class CidadeTypeTest extends KernelTestCase
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

        $objectToCompare = new Cidades();

        $form = $this->formFactory->create(CidadeType::class, $objectToCompare);
        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertEquals('Test Value', $form->get('nome')->getData());
    }

    public function testCustomFormView(): void
    {
        $object = new Cidades();

        $form = $this->formFactory->create(CidadeType::class, $object);

        $this->assertTrue($form->has('nome'));
    }
}
