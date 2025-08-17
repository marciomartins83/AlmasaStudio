<?php

namespace App\Tests\Form;

use App\Entity\TiposPessoas;
use App\Form\TipoPessoaType;
use Symfony\Component\Form\Test\TypeTestCase;

class TipoPessoaTypeTest extends TypeTestCase
{
    public function testSubmitValidData(): void
    {
        $formData = array (
  'tipo' => 'Física',
  'descricao' => 'Pessoa física',
  'ativo' => true,
);

        $objectToCompare = new TiposPessoas();
        $objectToCompare->setTipo('Física');
        $objectToCompare->setDescricao('Pessoa física');
        $objectToCompare->setAtivo(true);

        $form = $this->factory->create(TipoPessoaType::class, $objectToCompare);

        $object = new TiposPessoas();
        $objectToCompare->setTipo('Física');
        $objectToCompare->setDescricao('Pessoa física');
        $objectToCompare->setAtivo(true);

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());

        $this->assertEquals('Física', $objectToCompare->getTipo());
        $this->assertEquals('Pessoa física', $objectToCompare->getDescricao());
        $this->assertEquals(true, $objectToCompare->getAtivo());

        $view = $form->createView();
        $children = $view->children;

        foreach (['tipo', 'descricao', 'ativo'] as $key) {
            $this->assertArrayHasKey($key, $children);
        }
    }

    public function testCustomFormView(): void
    {
        $object = new TiposPessoas();

        $form = $this->factory->create(TipoPessoaType::class, $object);
        $view = $form->createView();

        $this->assertArrayHasKey('tipo', $view->children);
    }
}