<?php

namespace App\Tests\Form;

use App\Entity\TiposRemessa;
use App\Form\TipoRemessaType;
use Symfony\Component\Form\Test\TypeTestCase;

class TipoRemessaTypeTest extends TypeTestCase
{
    public function testSubmitValidData(): void
    {
        $formData = array (
  'tipo' => 'CNAB240',
  'descricao' => 'Remessa CNAB 240',
);

        $objectToCompare = new TiposRemessa();
        $objectToCompare->setTipo('CNAB240');
        $objectToCompare->setDescricao('Remessa CNAB 240');

        $form = $this->factory->create(TipoRemessaType::class, $objectToCompare);

        $object = new TiposRemessa();
        $objectToCompare->setTipo('CNAB240');
        $objectToCompare->setDescricao('Remessa CNAB 240');

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());

        $this->assertEquals('CNAB240', $objectToCompare->getTipo());
        $this->assertEquals('Remessa CNAB 240', $objectToCompare->getDescricao());

        $view = $form->createView();
        $children = $view->children;

        foreach (['tipo', 'descricao'] as $key) {
            $this->assertArrayHasKey($key, $children);
        }
    }

    public function testCustomFormView(): void
    {
        $object = new TiposRemessa();

        $form = $this->factory->create(TipoRemessaType::class, $object);
        $view = $form->createView();

        $this->assertArrayHasKey('tipo', $view->children);
    }
}