<?php

namespace App\Tests\Form;

use App\Entity\PessoasLocadores;
use App\Form\PessoaLocadorType;
use Symfony\Component\Form\Test\TypeTestCase;

class PessoaLocadorTypeTest extends TypeTestCase
{
    public function testSubmitValidData(): void
    {
        $formData = [
            'dependentes' => '2',
        ];

        $objectToCompare = new PessoasLocadores();

        // $objectToCompare will retrieve data from the form submission; pass it as the second argument
        $form = $this->factory->create(PessoaLocadorType::class, $objectToCompare);

        $object = new PessoasLocadores();

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
        $object = new PessoasLocadores();

        $form = $this->factory->create(PessoaLocadorType::class, $object);
        $view = $form->createView();

        $this->assertArrayHasKey('dependentes', $view->children);
    }
}