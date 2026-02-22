<?php

namespace App\Tests\Form;

use App\Entity\PessoasLocadores;
use App\Form\PessoaLocadorType;
use PHPUnit\Framework\TestCase;

class PessoaLocadorCombinedTypeTest extends TestCase
{
    public function testFormExists(): void
    {
        $form = new PessoaLocadorType();
        $this->assertInstanceOf(PessoaLocadorType::class, $form);
    }

    public function testFormConfigurationClass(): void
    {
        $form = new PessoaLocadorType();
        $this->assertTrue(method_exists($form, 'buildForm'));
        $this->assertTrue(method_exists($form, 'configureOptions'));
    }

    public function testFormTargetsCorrectEntity(): void
    {
        $this->assertTrue(class_exists(PessoasLocadores::class));
        $this->assertTrue(class_exists(PessoaLocadorType::class));
    }
}
