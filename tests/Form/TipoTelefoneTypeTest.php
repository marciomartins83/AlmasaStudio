<?php

namespace App\Tests\Form;

use App\Entity\TiposTelefones;
use App\Form\TipoTelefoneType;
use Symfony\Component\Form\Test\TypeTestCase;

class TipoTelefoneTypeTest extends TypeTestCase
{
    public function testSubmitValidData(): void
    {
        $formData = array (
  'tipo' => 'Celular',
);

        $objectToCompare = new TiposTelefones();
        $objectToCompare->setTipo('Celular');

        $form = $this->factory->create(TipoTelefoneType::class, $objectToCompare);

        $object = new TiposTelefones();
        $objectToCompare->setTipo('Celular');

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());

        $this->assertEquals('Celular', $objectToCompare->getTipo());

        $view = $form->createView();
        $children = $view->children;

        foreach (['tipo'] as $key) {
            $this->assertArrayHasKey($key, $children);
        }
    }

    public function testCustomFormView(): void
    {
        $object = new TiposTelefones();

        $form = $this->factory->create(TipoTelefoneType::class, $object);
        $view = $form->createView();

        $this->assertArrayHasKey('tipo', $view->children);
    }
}