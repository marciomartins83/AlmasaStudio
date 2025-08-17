<?php

namespace App\Tests\Entity;

use App\Entity\ContasBancarias;
use PHPUnit\Framework\TestCase;

class ContaBancariaTest extends TestCase
{
    public function testCreateContaBancaria(): void
    {
        $conta = new ContasBancarias();
        $this->assertInstanceOf(ContasBancarias::class, $conta);
    }

    public function testContaBancariaBasicFields(): void
    {
        $conta = new ContasBancarias();
        
        $conta->setIdPessoa(1);
        $conta->setIdAgencia(1);
        $conta->setCodigo('12345-6');
        $conta->setDigitoConta('7');
        $conta->setPrincipal(true);
        $conta->setAtivo(true);

        $this->assertEquals(1, $conta->getIdPessoa());
        $this->assertEquals(1, $conta->getIdAgencia());
        $this->assertEquals('12345-6', $conta->getCodigo());
        $this->assertEquals('7', $conta->getDigitoConta());
        $this->assertTrue($conta->getPrincipal());
        $this->assertTrue($conta->getAtivo());
    }

    public function testContaBancariaIdDefault(): void
    {
        $conta = new ContasBancarias();
        $this->assertNull($conta->getId());
    }
}
