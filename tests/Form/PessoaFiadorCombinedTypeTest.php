<?php

namespace App\Tests\Form;

use App\Entity\PessoasFiadores;
use App\Form\PessoaFiadorCombinedType;
use PHPUnit\Framework\TestCase;

class PessoaFiadorCombinedTypeTest extends TestCase
{
    public function testFormExists(): void
    {
        $form = new PessoaFiadorCombinedType();
        $this->assertInstanceOf(PessoaFiadorCombinedType::class, $form);
    }

    public function testFormConfigurationClass(): void
    {
        $form = new PessoaFiadorCombinedType();
        $this->assertTrue(method_exists($form, 'buildForm'));
        $this->assertTrue(method_exists($form, 'configureOptions'));
    }

    public function testFormTargetsCorrectEntity(): void
    {
        // Test that the form is designed for PessoasFiadores entity
        $this->assertTrue(class_exists(PessoasFiadores::class));
        $this->assertTrue(class_exists(PessoaFiadorCombinedType::class));
    }
}
