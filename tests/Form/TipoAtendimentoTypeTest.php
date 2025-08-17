<?php

namespace App\Tests\Form;

use App\Entity\TiposAtendimento;
use App\Form\TipoAtendimentoType;
use Symfony\Component\Form\Test\TypeTestCase;

class TipoAtendimentoTypeTest extends TypeTestCase
{
    public function testSubmitValidData(): void
    {
        $formData = array (
  'tipo' => 'Presencial',
  'descricao' => 'Atendimento presencial',
);

        $objectToCompare = new TiposAtendimento();
        $objectToCompare->setTipo('Presencial');
        $objectToCompare->setDescricao('Atendimento presencial');

        $form = $this->factory->create(TipoAtendimentoType::class, $objectToCompare);

        $object = new TiposAtendimento();
        $objectToCompare->setTipo('Presencial');
        $objectToCompare->setDescricao('Atendimento presencial');

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());

        $this->assertEquals('Presencial', $objectToCompare->getTipo());
        $this->assertEquals('Atendimento presencial', $objectToCompare->getDescricao());

        $view = $form->createView();
        $children = $view->children;

        foreach (['tipo', 'descricao'] as $key) {
            $this->assertArrayHasKey($key, $children);
        }
    }

    public function testCustomFormView(): void
    {
        $object = new TiposAtendimento();

        $form = $this->factory->create(TipoAtendimentoType::class, $object);
        $view = $form->createView();

        $this->assertArrayHasKey('tipo', $view->children);
    }
}