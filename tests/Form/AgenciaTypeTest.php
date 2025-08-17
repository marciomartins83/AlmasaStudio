<?php

namespace App\Tests\Form;

use App\Entity\Agencias;
use App\Form\AgenciaType;
use Symfony\Component\Form\Test\TypeTestCase;

class AgenciaTypeTest extends TypeTestCase
{
    public function testSubmitValidData(): void
    {
        $formData = [
            'codigo' => '001',
            'nome' => 'Agencia Teste',
        ];

        $objectToCompare = new Agencias();
        $objectToCompare->setCodigo('001');
        $objectToCompare->setNome('Agencia Teste');

        // $objectToCompare will retrieve data from the form submission; pass it as the second argument
        $form = $this->factory->create(AgenciaType::class, $objectToCompare);

        $object = new Agencias();
        $object->setCodigo('001');
        $object->setNome('Agencia Teste');

        // submit the data to the form directly
        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());

        // check that $objectToCompare was modified as expected when the form was submitted
        $this->assertEquals($object->getCodigo(), $objectToCompare->getCodigo());
        $this->assertEquals($object->getNome(), $objectToCompare->getNome());

        $view = $form->createView();
        $children = $view->children;

        foreach (array_keys($formData) as $key) {
            $this->assertArrayHasKey($key, $children);
        }
    }

    public function testCustomFormView(): void
    {
        $object = new Agencias();
        $object->setCodigo('002');
        $object->setNome('Agencia Form Test');

        $form = $this->factory->create(AgenciaType::class, $object);
        $view = $form->createView();

        $this->assertArrayHasKey('codigo', $view->children);
        $this->assertArrayHasKey('nome', $view->children);
    }
}