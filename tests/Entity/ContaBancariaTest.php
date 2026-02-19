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

        $mockPessoa = $this->createMock(\App\Entity\Pessoas::class);
        $mockAgencia = $this->createMock(\App\Entity\Agencias::class);

        $conta->setIdPessoa($mockPessoa);
        $conta->setIdAgencia($mockAgencia);
        $conta->setCodigo('12345-6');
        $conta->setDigitoConta('7');
        $conta->setPrincipal(true);
        $conta->setAtivo(true);

        $this->assertSame($mockPessoa, $conta->getIdPessoa());
        $this->assertSame($mockAgencia, $conta->getIdAgencia());
        $this->assertEquals('12345-6', $conta->getCodigo());
        $this->assertEquals('7', $conta->getDigitoConta());
        $this->assertTrue($conta->getPrincipal());
        $this->assertTrue($conta->getAtivo());
    }

    public function testContaBancariaRelatedness(): void
    {
        $conta = new ContasBancarias();

        // Test with boolean fields
        $this->assertTrue(method_exists($conta, 'setPrincipal'));
        $this->assertTrue(method_exists($conta, 'getPrincipal'));
        $this->assertTrue(method_exists($conta, 'setAtivo'));
        $this->assertTrue(method_exists($conta, 'getAtivo'));
    }

    public function testContaBancariaIdDefault(): void
    {
        $conta = new ContasBancarias();
        $this->assertNull($conta->getId());
    }
}
