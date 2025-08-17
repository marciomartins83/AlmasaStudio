<?php

namespace App\Tests\Form;

use App\Entity\TiposEnderecos;
use App\Form\TipoEnderecoType;
use Symfony\Component\Form\Test\TypeTestCase;

class TipoEnderecoTypeTest extends TypeTestCase
{
    public function testSubmitValidData(): void
    {
        $formData = array (
  'tipo' => 'Residencial',
);

        $objectToCompare = new TiposEnderecos();
        $objectToCompare->setTipo('Residencial');

        $form = $this->factory->create(TipoEnderecoType::class, $objectToCompare);

        $object = new TiposEnderecos();
        $objectToCompare->setTipo('Residencial');

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());

        $this->assertEquals('Residencial', $objectToCompare->getTipo());

        $view = $form->createView();
        $children = $view->children;

        foreach (['tipo'] as $key) {
            $this->assertArrayHasKey($key, $children);
        }
    }

    public function testCustomFormView(): void
    {
        $object = new TiposEnderecos();

        $form = $this->factory->create(TipoEnderecoType::class, $object);
        $view = $form->createView();

        $this->assertArrayHasKey('tipo', $view->children);
    }
}