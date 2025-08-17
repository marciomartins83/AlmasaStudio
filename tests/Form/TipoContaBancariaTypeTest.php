<?php

namespace App\Tests\Form;

use App\Entity\TiposContasBancarias;
use App\Form\TipoContaBancariaType;
use Symfony\Component\Form\Test\TypeTestCase;

class TipoContaBancariaTypeTest extends TypeTestCase
{
    public function testSubmitValidData(): void
    {
        $formData = [
            'tipo' => 'Corrente',
        ];

        $objectToCompare = new TiposContasBancarias();

        // $objectToCompare will retrieve data from the form submission; pass it as the second argument
        $form = $this->factory->create(TipoContaBancariaType::class, $objectToCompare);

        $object = new TiposContasBancarias();

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
        $object = new TiposContasBancarias();

        $form = $this->factory->create(TipoContaBancariaType::class, $object);
        $view = $form->createView();

        $this->assertArrayHasKey('tipo', $view->children);
    }
}