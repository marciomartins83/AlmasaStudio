<?php

namespace App\Tests\Form;

use App\Entity\TiposDocumentos;
use App\Form\TipoDocumentoType;
use Symfony\Component\Form\Test\TypeTestCase;

class TipoDocumentoTypeTest extends TypeTestCase
{
    public function testSubmitValidData(): void
    {
        $formData = [
            'tipo' => 'RG',
        ];

        $objectToCompare = new TiposDocumentos();

        // $objectToCompare will retrieve data from the form submission; pass it as the second argument
        $form = $this->factory->create(TipoDocumentoType::class, $objectToCompare);

        $object = new TiposDocumentos();

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
        $object = new TiposDocumentos();

        $form = $this->factory->create(TipoDocumentoType::class, $object);
        $view = $form->createView();

        $this->assertArrayHasKey('tipo', $view->children);
    }
}