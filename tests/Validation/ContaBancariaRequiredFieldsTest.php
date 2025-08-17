<?php

namespace App\Tests\Validation;

use App\Entity\ContasBancarias;
use PHPUnit\Framework\TestCase;

class ContaBancariaRequiredFieldsTest extends TestCase
{
    public function testContaBancariaBasicValidation(): void
    {
        $conta = new ContasBancarias();
        $this->assertInstanceOf(ContasBancarias::class, $conta);
        
        // Test basic setters and getters
        $conta->setCodigo('12345-6');
        $conta->setDigitoConta('7');
        $conta->setPrincipal(true);
        $conta->setAtivo(true);
        
        $this->assertEquals('12345-6', $conta->getCodigo());
        $this->assertEquals('7', $conta->getDigitoConta());
        $this->assertTrue($conta->getPrincipal());
        $this->assertTrue($conta->getAtivo());
    }

    public function testContaBancariaFieldsCanBeEmpty(): void
    {
        $conta = new ContasBancarias();
        
        // Test that nullable fields can be null
        $this->assertNull($conta->getDigitoConta());
        
        // Test that entity can be created without initializing required fields
        $this->assertInstanceOf(ContasBancarias::class, $conta);
    }
}
