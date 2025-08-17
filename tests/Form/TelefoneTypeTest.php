<?php

namespace App\Tests\Form;

use App\Entity\Telefones;
use App\Form\TelefoneType;
use Symfony\Component\Form\Test\TypeTestCase;

class TelefoneTypeTest extends TypeTestCase
{
    public function testSubmitValidData(): void
    {
        $formData = [
            'numero' => '11999999999',
        ];

        $objectToCompare = new Telefones();

        // $objectToCompare will retrieve data from the form submission; pass it as the second argument
        $form = $this->factory->create(TelefoneType::class, $objectToCompare);

        $object = new Telefones();

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
        $object = new Telefones();

        $form = $this->factory->create(TelefoneType::class, $object);
        $view = $form->createView();

        $this->assertArrayHasKey('numero', $view->children);
    }
}