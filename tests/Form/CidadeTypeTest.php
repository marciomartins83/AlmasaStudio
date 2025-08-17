<?php

namespace App\Tests\Form;

use App\Entity\Cidades;
use App\Form\CidadeType;
use Symfony\Component\Form\Test\TypeTestCase;

class CidadeTypeTest extends TypeTestCase
{
    public function testSubmitValidData(): void
    {
        $formData = [
            'nome' => 'Test Value',
        ];

        $objectToCompare = new Cidades();

        // $objectToCompare will retrieve data from the form submission; pass it as the second argument
        $form = $this->factory->create(CidadeType::class, $objectToCompare);

        $object = new Cidades();

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
        $object = new Cidades();

        $form = $this->factory->create(CidadeType::class, $object);
        $view = $form->createView();

        $this->assertArrayHasKey('nome', $view->children);
    }
}