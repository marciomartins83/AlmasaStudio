<?php

namespace App\Tests\Form;

use App\Entity\TiposCarteiras;
use App\Form\TipoCarteiraType;
use Symfony\Component\Form\Test\TypeTestCase;

class TipoCarteiraTypeTest extends TypeTestCase
{
    public function testSubmitValidData(): void
    {
        $formData = array (
  'tipo' => 'Simples',
  'descricao' => 'Carteira simples',
);

        $objectToCompare = new TiposCarteiras();
        $objectToCompare->setTipo('Simples');
        $objectToCompare->setDescricao('Carteira simples');

        $form = $this->factory->create(TipoCarteiraType::class, $objectToCompare);

        $object = new TiposCarteiras();
        $objectToCompare->setTipo('Simples');
        $objectToCompare->setDescricao('Carteira simples');

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());

        $this->assertEquals('Simples', $objectToCompare->getTipo());
        $this->assertEquals('Carteira simples', $objectToCompare->getDescricao());

        $view = $form->createView();
        $children = $view->children;

        foreach (['tipo', 'descricao'] as $key) {
            $this->assertArrayHasKey($key, $children);
        }
    }

    public function testCustomFormView(): void
    {
        $object = new TiposCarteiras();

        $form = $this->factory->create(TipoCarteiraType::class, $object);
        $view = $form->createView();

        $this->assertArrayHasKey('tipo', $view->children);
    }
}