<?php

namespace App\Tests\Form;

use App\Entity\Naturalidade;
use App\Form\NaturalidadeType;
use Symfony\Component\Form\Test\TypeTestCase;

class NaturalidadeTypeTest extends TypeTestCase
{
    public function testSubmitValidData(): void
    {
        $formData = [
            'nome' => 'São Paulo',
        ];

        $objectToCompare = new Naturalidade();

        // $objectToCompare will retrieve data from the form submission; pass it as the second argument
        $form = $this->factory->create(NaturalidadeType::class, $objectToCompare);

        $object = new Naturalidade();

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
        $object = new Naturalidade();

        $form = $this->factory->create(NaturalidadeType::class, $object);
        $view = $form->createView();

        $this->assertArrayHasKey('nome', $view->children);
    }
}
