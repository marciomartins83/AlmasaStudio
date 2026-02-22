<?php

namespace App\Tests\Form;

use App\Entity\Agencias;
use App\Form\AgenciaType;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class AgenciaTypeTest extends KernelTestCase
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
        $object = new Agencias();

        $form = $this->formFactory->create(AgenciaType::class, $object);

        $this->assertTrue($form->has('codigo'));
        $this->assertTrue($form->has('nome'));
        $this->assertTrue($form->has('banco'));
        $this->assertTrue($form->has('endereco'));
    }

    public function testCustomFormView(): void
    {
        $object = new Agencias();

        $form = $this->formFactory->create(AgenciaType::class, $object);

        $this->assertTrue($form->has('codigo'));
        $this->assertTrue($form->has('nome'));
        $this->assertTrue($form->has('banco'));
        $this->assertTrue($form->has('endereco'));
    }
}
