<?php

namespace App\Tests\Form;

use App\Entity\EstadoCivil;
use App\Form\EstadoCivilType;
use Symfony\Component\Form\Test\TypeTestCase;

class EstadoCivilTypeTest extends TypeTestCase
{
    public function testSubmitValidData(): void
    {
        $formData = [
            'nome' => 'Solteiro',
        ];

        $objectToCompare = new EstadoCivil();

        // $objectToCompare will retrieve data from the form submission; pass it as the second argument
        $form = $this->factory->create(EstadoCivilType::class, $objectToCompare);

        $object = new EstadoCivil();

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
        $object = new EstadoCivil();

        $form = $this->factory->create(EstadoCivilType::class, $object);
        $view = $form->createView();

        $this->assertArrayHasKey('nome', $view->children);
    }
}