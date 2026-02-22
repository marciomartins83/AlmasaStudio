<?php

namespace App\Tests\Form;

use App\Entity\PessoasFiadores;
use App\Form\PessoaFiadorType;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class PessoaFiadorTypeTest extends KernelTestCase
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
            'motivoFianca' => 'Test Value 1',
        ];

        $objectToCompare = new PessoasFiadores();

        $form = $this->formFactory->create(PessoaFiadorType::class, $objectToCompare);

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
    }

    public function testCustomFormView(): void
    {
        $object = new PessoasFiadores();

        $form = $this->formFactory->create(PessoaFiadorType::class, $object);

        $this->assertTrue($form->has('motivoFianca'));
    }
}
