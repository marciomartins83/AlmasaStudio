<?php

namespace App\Tests\Form;

use App\Entity\Pessoas;
use App\Form\PessoaFormType;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class PessoaTypeTest extends KernelTestCase
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
            'nome' => 'Joao Silva Santos',
        ];

        $objectToCompare = new Pessoas();

        $form = $this->formFactory->create(PessoaFormType::class, $objectToCompare);

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
    }

    public function testCustomFormView(): void
    {
        $form = $this->formFactory->create(PessoaFormType::class, new Pessoas());

        $this->assertTrue($form->has('nome'));
    }
}
