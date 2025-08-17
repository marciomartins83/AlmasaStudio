<?php

namespace App\Tests\Form;

use App\Entity\PessoasFiadores;
use App\Form\PessoaFiadorType;
use Symfony\Component\Form\Test\TypeTestCase;

class PessoaFiadorTypeTest extends TypeTestCase
{
    public function testSubmitValidData(): void
    {
        $formData = [
            'motivoFianca' => 'Test Value 1',
        ];

        $objectToCompare = new PessoasFiadores();

        // $objectToCompare will retrieve data from the form submission; pass it as the second argument
        $form = $this->factory->create(PessoaFiadorType::class, $objectToCompare);

        $object = new PessoasFiadores();

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
        $object = new PessoasFiadores();

        $form = $this->factory->create(PessoaFiadorType::class, $object);
        $view = $form->createView();

        $this->assertArrayHasKey('motivoFianca', $view->children);
    }
}