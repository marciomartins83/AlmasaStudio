<?php

namespace App\Tests\Form;

use App\Entity\Pessoas;
use App\Form\PessoaType;
use Symfony\Component\Form\Test\TypeTestCase;

class PessoaTypeTest extends TypeTestCase
{
    public function testSubmitValidData(): void
    {
        $formData = [
            'nome' => 'João Silva Santos',
        ];

        $objectToCompare = new Pessoas();

        // $objectToCompare will retrieve data from the form submission; pass it as the second argument
        $form = $this->factory->create(PessoaType::class, $objectToCompare);

        $object = new Pessoas();

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
        $object = new Pessoas();

        $form = $this->factory->create(PessoaType::class, $object);
        $view = $form->createView();

        $this->assertArrayHasKey('nome', $view->children);
    }
}
