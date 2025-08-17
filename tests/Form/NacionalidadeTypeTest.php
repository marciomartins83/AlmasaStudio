<?php

namespace App\Tests\Form;

use App\Entity\Nacionalidade;
use App\Form\NacionalidadeType;
use Symfony\Component\Form\Test\TypeTestCase;

class NacionalidadeTypeTest extends TypeTestCase
{
    public function testSubmitValidData(): void
    {
        $formData = [
            'nome' => 'Brasileira',
        ];

        $objectToCompare = new Nacionalidade();

        // $objectToCompare will retrieve data from the form submission; pass it as the second argument
        $form = $this->factory->create(NacionalidadeType::class, $objectToCompare);

        $object = new Nacionalidade();

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
        $object = new Nacionalidade();

        $form = $this->factory->create(NacionalidadeType::class, $object);
        $view = $form->createView();

        $this->assertArrayHasKey('nome', $view->children);
    }
}
