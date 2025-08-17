<?php

namespace App\Tests\Form;

use App\Entity\TiposChavesPix;
use App\Form\TipoChavePixType;
use Symfony\Component\Form\Test\TypeTestCase;

class TipoChavePixTypeTest extends TypeTestCase
{
    public function testSubmitValidData(): void
    {
        $formData = [
            'tipo' => 'CPF',
        ];

        $objectToCompare = new TiposChavesPix();

        // $objectToCompare will retrieve data from the form submission; pass it as the second argument
        $form = $this->factory->create(TipoChavePixType::class, $objectToCompare);

        $object = new TiposChavesPix();

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
        $object = new TiposChavesPix();

        $form = $this->factory->create(TipoChavePixType::class, $object);
        $view = $form->createView();

        $this->assertArrayHasKey('tipo', $view->children);
    }
}