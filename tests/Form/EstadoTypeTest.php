<?php

namespace App\Tests\Form;

use App\Entity\Estados;
use App\Form\EstadoType;
use Symfony\Component\Form\Test\TypeTestCase;

class EstadoTypeTest extends TypeTestCase
{
    public function testSubmitValidData(): void
    {
        $formData = [
            'nome' => 'Test Value',
        ];

        $objectToCompare = new Estados();

        // $objectToCompare will retrieve data from the form submission; pass it as the second argument
        $form = $this->factory->create(EstadoType::class, $objectToCompare);

        $object = new Estados();

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
        $object = new Estados();

        $form = $this->factory->create(EstadoType::class, $object);
        $view = $form->createView();

        $this->assertArrayHasKey('nome', $view->children);
    }
}