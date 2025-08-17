<?php

namespace App\Tests\Form;

use App\Entity\ContasBancarias;
use App\Form\ContaBancariaType;
use Symfony\Component\Form\Test\TypeTestCase;

class ContaBancariaTypeTest extends TypeTestCase
{
    public function testSubmitValidData(): void
    {
        $formData = [
            'codigo' => 'Test Value 1',
            'digitoConta' => 'Test Value 2',
        ];

        $objectToCompare = new ContasBancarias();

        // $objectToCompare will retrieve data from the form submission; pass it as the second argument
        $form = $this->factory->create(ContaBancariaType::class, $objectToCompare);

        $object = new ContasBancarias();

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
        $object = new ContasBancarias();

        $form = $this->factory->create(ContaBancariaType::class, $object);
        $view = $form->createView();

        $this->assertArrayHasKey('codigo', $view->children);
        $this->assertArrayHasKey('digitoConta', $view->children);
    }
}