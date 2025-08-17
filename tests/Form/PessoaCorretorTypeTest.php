<?php

namespace App\Tests\Form;

use App\Entity\PessoasCorretores;
use App\Form\PessoaCorretorType;
use Symfony\Component\Form\Test\TypeTestCase;

class PessoaCorretorTypeTest extends TypeTestCase
{
    public function testSubmitValidData(): void
    {
        $formData = [
            'creci' => 'Test Value 1',
        ];

        $objectToCompare = new PessoasCorretores();

        // $objectToCompare will retrieve data from the form submission; pass it as the second argument
        $form = $this->factory->create(PessoaCorretorType::class, $objectToCompare);

        $object = new PessoasCorretores();

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
        $object = new PessoasCorretores();

        $form = $this->factory->create(PessoaCorretorType::class, $object);
        $view = $form->createView();

        $this->assertArrayHasKey('creci', $view->children);
    }
}