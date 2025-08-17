<?php

namespace App\Tests\Form;

use App\Entity\Emails;
use App\Form\EmailType;
use Symfony\Component\Form\Test\TypeTestCase;

class EmailTypeTest extends TypeTestCase
{
    public function testSubmitValidData(): void
    {
        $formData = [
            'email' => 'test@example.com',
        ];

        $objectToCompare = new Emails();

        // $objectToCompare will retrieve data from the form submission; pass it as the second argument
        $form = $this->factory->create(EmailType::class, $objectToCompare);

        $object = new Emails();

        // submit the data to the form directly
        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());

        $view = $form->createView();
        $children = $view->children;

        foreach (array_keys($formData) as $key) {
            $this->assertArrayHasKey($key, $children);
        }
    }

    public function testCustomFormView(): void
    {
        $object = new Emails();

        $form = $this->factory->create(EmailType::class, $object);
        $view = $form->createView();

        $this->assertArrayHasKey('email', $view->children);
    }
}