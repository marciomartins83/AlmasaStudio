<?php

namespace App\Tests\Form;

use App\Entity\Telefones;
use App\Form\TelefoneFormType;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class TelefoneTypeTest extends KernelTestCase
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
            'numero' => '11999999999',
        ];

        $objectToCompare = new Telefones();

        $form = $this->formFactory->create(TelefoneFormType::class, $objectToCompare);

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
    }

    public function testCustomFormView(): void
    {
        $object = new Telefones();

        $form = $this->formFactory->create(TelefoneFormType::class, $object);

        $this->assertTrue($form->has('numero'));
    }
}
