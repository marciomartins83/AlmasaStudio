<?php

namespace App\Tests\Form;

use App\Entity\Pessoas;
use App\Form\PessoaProprietarioType;
use Symfony\Component\Form\Test\TypeTestCase;

class PessoaProprietarioTypeTest extends TypeTestCase
{
    public function testSubmitValidData(): void
    {
        $formData = [
            'observacoes' => 'Proprietário responsável',
        ];

        $objectToCompare = new Pessoas();

        // $objectToCompare will retrieve data from the form submission; pass it as the second argument
        $form = $this->factory->create(PessoaProprietarioType::class, $objectToCompare);

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

        $form = $this->factory->create(PessoaProprietarioType::class, $object);
        $view = $form->createView();

        $this->assertArrayHasKey('observacoes', $view->children);
    }
}