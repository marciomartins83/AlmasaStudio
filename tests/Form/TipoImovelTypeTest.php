<?php

namespace App\Tests\Form;

use App\Entity\TiposImoveis;
use App\Form\TipoImovelType;
use Symfony\Component\Form\Test\TypeTestCase;

class TipoImovelTypeTest extends TypeTestCase
{
    public function testSubmitValidData(): void
    {
        $formData = array (
  'tipo' => 'Casa',
  'descricao' => 'Casa residencial',
);

        $objectToCompare = new TiposImoveis();
        $objectToCompare->setTipo('Casa');
        $objectToCompare->setDescricao('Casa residencial');

        $form = $this->factory->create(TipoImovelType::class, $objectToCompare);

        $object = new TiposImoveis();
        $objectToCompare->setTipo('Casa');
        $objectToCompare->setDescricao('Casa residencial');

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());

        $this->assertEquals('Casa', $objectToCompare->getTipo());
        $this->assertEquals('Casa residencial', $objectToCompare->getDescricao());

        $view = $form->createView();
        $children = $view->children;

        foreach (['tipo', 'descricao'] as $key) {
            $this->assertArrayHasKey($key, $children);
        }
    }

    public function testCustomFormView(): void
    {
        $object = new TiposImoveis();

        $form = $this->factory->create(TipoImovelType::class, $object);
        $view = $form->createView();

        $this->assertArrayHasKey('tipo', $view->children);
    }
}