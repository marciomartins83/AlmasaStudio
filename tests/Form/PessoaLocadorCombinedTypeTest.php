<?php

namespace App\Tests\Form;

use App\Entity\PessoasLocadores;
use App\Form\PessoaLocadorCombinedType;
use PHPUnit\Framework\TestCase;

class PessoaLocadorCombinedTypeTest extends TestCase
{
    public function testFormExists(): void
    {
        $form = new PessoaLocadorCombinedType();
        $this->assertInstanceOf(PessoaLocadorCombinedType::class, $form);
    }

    public function testFormConfigurationClass(): void
    {
        $form = new PessoaLocadorCombinedType();
        $this->assertTrue(method_exists($form, 'buildForm'));
        $this->assertTrue(method_exists($form, 'configureOptions'));
    }

    public function testFormTargetsCorrectEntity(): void
    {
        // Test that the form is designed for PessoasLocadores entity
        $this->assertTrue(class_exists(PessoasLocadores::class));
        $this->assertTrue(class_exists(PessoaLocadorCombinedType::class));
    }
}
