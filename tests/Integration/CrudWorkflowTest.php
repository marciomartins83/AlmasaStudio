<?php

namespace App\Tests\Integration;

use App\Entity\Agencias;
use App\Entity\Bancos;
use App\Entity\ContasBancarias;
use PHPUnit\Framework\TestCase;

class CrudWorkflowTest extends TestCase
{
    public function testEntitiesCanBeCreated(): void
    {
        // Test basic entity creation workflow
        $banco = new Bancos();
        $banco->setNome('Banco Teste');
        $banco->setNumero(123);
        
        $this->assertInstanceOf(Bancos::class, $banco);
        $this->assertEquals('Banco Teste', $banco->getNome());
        $this->assertEquals(123, $banco->getNumero());
        
        $agencia = new Agencias();
        $agencia->setCodigo('001');
        $agencia->setNome('Agencia Central');
        
        $this->assertInstanceOf(Agencias::class, $agencia);
        $this->assertEquals('001', $agencia->getCodigo());
        $this->assertEquals('Agencia Central', $agencia->getNome());
        
        $conta = new ContasBancarias();
        $conta->setCodigo('12345-6');
        $conta->setDigitoConta('7');
        
        $this->assertInstanceOf(ContasBancarias::class, $conta);
        $this->assertEquals('12345-6', $conta->getCodigo());
        $this->assertEquals('7', $conta->getDigitoConta());
    }

    public function testEntityRelationshipsStructure(): void
    {
        // Test that entities have the expected structure for relationships
        $banco = new Bancos();
        $agencia = new Agencias();
        $conta = new ContasBancarias();
        
        // Test basic ID methods
        $this->assertNull($banco->getId());
        $this->assertNull($agencia->getId());
        $this->assertNull($conta->getId());
        
        // Test that relationship fields can be set
        $agencia->setIdBanco(1);
        $this->assertEquals(1, $agencia->getIdBanco());
        
        $conta->setIdBanco(1);
        $conta->setIdAgencia(1);
        $this->assertEquals(1, $conta->getIdBanco());
        $this->assertEquals(1, $conta->getIdAgencia());
    }

    public function testComplexWorkflow(): void
    {
        // Simulate a complete workflow without DB
        $workflow = [
            'create_banco' => function() {
                $banco = new Bancos();
                $banco->setNome('Banco Central');
                $banco->setNumero(001);
                return $banco;
            },
            'create_agencia' => function($banco) {
                $agencia = new Agencias();
                $agencia->setCodigo('001');
                $agencia->setNome('Agencia Central');
                $agencia->setIdBanco(1); // Simulating banco ID
                return $agencia;
            },
            'create_conta' => function($agencia) {
                $conta = new ContasBancarias();
                $conta->setCodigo('12345-6');
                $conta->setDigitoConta('7');
                $conta->setIdBanco(1);
                $conta->setIdAgencia(1);
                return $conta;
            }
        ];
        
        $banco = $workflow['create_banco']();
        $agencia = $workflow['create_agencia']($banco);
        $conta = $workflow['create_conta']($agencia);
        
        $this->assertInstanceOf(Bancos::class, $banco);
        $this->assertInstanceOf(Agencias::class, $agencia);
        $this->assertInstanceOf(ContasBancarias::class, $conta);
    }
}
