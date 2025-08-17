<?php

namespace App\Tests\Form;

use App\Entity\TiposEmails;
use App\Form\TipoEmailType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Test\TypeTestCase;

class TipoEmailTypeTest extends TypeTestCase
{
    public function testSubmitValidData(): void
    {
        $formData = [
            'tipo' => 'Pessoal',
        ];

        $objectToCompare = new TiposEmails();
        $objectToCompare->setTipo('Pessoal');

        // $objectToCompare will retrieve data from the form submission; pass it as the second argument
        $form = $this->factory->create(TipoEmailType::class, $objectToCompare);

        $object = new TiposEmails();
        $object->setTipo('Pessoal');

        // submit the data to the form directly
        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());

        // check that $objectToCompare was modified as expected when the form was submitted
        $this->assertEquals($object->getTipo(), $objectToCompare->getTipo());

        $view = $form->createView();
        $children = $view->children;

        foreach (array_keys($formData) as $key) {
            $this->assertArrayHasKey($key, $children);
        }
    }

    public function testCustomFormView(): void
    {
        $object = new TiposEmails();
        $object->setTipo('Comercial');

        $form = $this->factory->create(TipoEmailType::class, $object);
        $view = $form->createView();

        $this->assertArrayHasKey('tipo', $view->children);
    }
}
