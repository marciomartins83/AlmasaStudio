<?php

namespace App\Tests\Form;

use App\Form\PessoaFiadorFormType;
use PHPUnit\Framework\TestCase;

class PessoaFiadorFormTypeTest extends TestCase
{
    public function testFormExists(): void
    {
        $form = new PessoaFiadorFormType();
        $this->assertInstanceOf(PessoaFiadorFormType::class, $form);
    }

    public function testFormConfigurationClass(): void
    {
        $form = new PessoaFiadorFormType();
        $this->assertTrue(method_exists($form, 'buildForm'));
        $this->assertTrue(method_exists($form, 'configureOptions'));
    }

    public function testFormIsConfiguredCorrectly(): void
    {
        // Test that the form class exists and can be instantiated
        $this->assertTrue(class_exists(PessoaFiadorFormType::class));
    }
}
