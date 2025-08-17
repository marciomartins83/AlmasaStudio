<?php

namespace App\Tests\Entity;

use App\Entity\ContasBancarias;
use PHPUnit\Framework\TestCase;

class ContaBancariaTestSimple extends TestCase
{
    public function testCreateContaBancaria(): void
    {
        $conta = new ContasBancarias();
        $this->assertInstanceOf(ContasBancarias::class, $conta);
    }

    public function testContaBancariaBasicFields(): void
    {
        $conta = new ContasBancarias();
        
        if (method_exists($conta, 'setIdPessoa')) {
            $conta->setIdPessoa(1);
            $this->assertEquals(1, $conta->getIdPessoa());
        }
        
        if (method_exists($conta, 'setIdAgencia')) {
            $conta->setIdAgencia(1);
            $this->assertEquals(1, $conta->getIdAgencia());
        }
        
        if (method_exists($conta, 'setNumero')) {
            $conta->setNumero('12345-6');
            $this->assertEquals('12345-6', $conta->getNumero());
        }
    }

    public function testContaBancariaIdDefault(): void
    {
        $conta = new ContasBancarias();
        $this->assertNull($conta->getId());
    }
}
