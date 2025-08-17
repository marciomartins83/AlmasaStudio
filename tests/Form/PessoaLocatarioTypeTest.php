<?php

namespace App\Tests\Form;

use App\Entity\PessoasPretendentes;
use App\Form\PessoaLocatarioType;
use Symfony\Component\Form\Test\TypeTestCase;

class PessoaLocatarioTypeTest extends TypeTestCase
{
    public function testSubmitValidData(): void
    {
        $formData = [
            'aluguelMaximo' => '2500.00',
        ];

        $objectToCompare = new PessoasPretendentes();

        // $objectToCompare will retrieve data from the form submission; pass it as the second argument
        $form = $this->factory->create(PessoaLocatarioType::class, $objectToCompare);

        $object = new PessoasPretendentes();

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
        $object = new PessoasPretendentes();

        $form = $this->factory->create(PessoaLocatarioType::class, $object);
        $view = $form->createView();

        $this->assertArrayHasKey('aluguelMaximo', $view->children);
    }
}