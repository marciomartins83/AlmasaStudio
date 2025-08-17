<?php

namespace App\Tests\Form;

use App\Entity\Logradouros;
use App\Form\LogradouroType;
use Symfony\Component\Form\Test\TypeTestCase;

class LogradouroTypeTest extends TypeTestCase
{
    public function testSubmitValidData(): void
    {
        $formData = [
            'logradouro' => 'Rua das Flores',
        ];

        $objectToCompare = new Logradouros();

        // $objectToCompare will retrieve data from the form submission; pass it as the second argument
        $form = $this->factory->create(LogradouroType::class, $objectToCompare);

        $object = new Logradouros();

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
        $object = new Logradouros();

        $form = $this->factory->create(LogradouroType::class, $object);
        $view = $form->createView();

        $this->assertArrayHasKey('logradouro', $view->children);
    }
}