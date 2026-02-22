<?php

namespace App\Tests\Form;

use App\Entity\PessoasPretendentes;
use App\Form\PessoaPretendenteType;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class PessoaLocatarioTypeTest extends KernelTestCase
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
            'aluguelMaximo' => '2500',
        ];

        $objectToCompare = new PessoasPretendentes();

        $form = $this->formFactory->create(PessoaPretendenteType::class, $objectToCompare);

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
    }

    public function testCustomFormView(): void
    {
        $form = $this->formFactory->create(PessoaPretendenteType::class, new PessoasPretendentes());

        $this->assertTrue($form->has('aluguelMaximo'));
    }
}
