<?php

namespace App\Tests\Form;

use App\Entity\Bairros;
use App\Form\BairroType;
use Symfony\Component\Form\Test\TypeTestCase;

class BairroTypeTest extends TypeTestCase
{
    public function testSubmitValidData(): void
    {
        $formData = [
            'nome' => 'Test Value',
        ];

        $objectToCompare = new Bairros();

        // $objectToCompare will retrieve data from the form submission; pass it as the second argument
        $form = $this->factory->create(BairroType::class, $objectToCompare);

        $object = new Bairros();

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
        $object = new Bairros();

        $form = $this->factory->create(BairroType::class, $object);
        $view = $form->createView();

        $this->assertArrayHasKey('nome', $view->children);
    }
}