<?php

namespace App\Tests\Form;

use App\Entity\Emails;
use App\Form\EmailFormType;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class EmailTypeTest extends KernelTestCase
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
            'email' => 'test@example.com',
        ];

        $objectToCompare = new Emails();

        $form = $this->formFactory->create(EmailFormType::class, $objectToCompare);

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
    }

    public function testCustomFormView(): void
    {
        $form = $this->formFactory->create(EmailFormType::class, new Emails());

        $this->assertTrue($form->has('email'));
    }
}
