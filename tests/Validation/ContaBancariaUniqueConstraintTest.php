<?php

namespace App\Tests\Validation;

use App\Entity\ContasBancarias;
use PHPUnit\Framework\TestCase;

class ContaBancariaUniqueConstraintTest extends TestCase
{
    public function testContaBancariaEntityCreation(): void
    {
        $conta = new ContasBancarias();
        $this->assertInstanceOf(ContasBancarias::class, $conta);
        
        $conta->setCodigo('12345-6');
        $conta->setDigitoConta('7');
        
        $this->assertEquals('12345-6', $conta->getCodigo());
        $this->assertEquals('7', $conta->getDigitoConta());
    }

    public function testContaBancariaDifferentCodes(): void
    {
        $conta1 = new ContasBancarias();
        $conta1->setCodigo('12345-6');
        
        $conta2 = new ContasBancarias();
        $conta2->setCodigo('67890-1');
        
        $this->assertNotEquals($conta1->getCodigo(), $conta2->getCodigo());
    }

    public function testContaBancariaSameCodes(): void
    {
        $conta1 = new ContasBancarias();
        $conta1->setCodigo('12345-6');
        
        $conta2 = new ContasBancarias();
        $conta2->setCodigo('12345-6');
        
        $this->assertEquals($conta1->getCodigo(), $conta2->getCodigo());
    }
}
