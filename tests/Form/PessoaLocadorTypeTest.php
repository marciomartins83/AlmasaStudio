<?php

namespace App\Tests\Form;

use App\Entity\PessoasLocadores;
use App\Form\PessoaLocadorType;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class PessoaLocadorTypeTest extends KernelTestCase
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
        $objectToCompare = new PessoasLocadores();

        $form = $this->formFactory->create(PessoaLocadorType::class, $objectToCompare);

        $this->assertTrue($form->has('dependentes'));
        $this->assertTrue($form->has('formaRetirada'));
    }

    public function testCustomFormView(): void
    {
        $object = new PessoasLocadores();

        $form = $this->formFactory->create(PessoaLocadorType::class, $object);

        $this->assertTrue($form->has('dependentes'));
        $this->assertTrue($form->has('formaRetirada'));
    }
}
