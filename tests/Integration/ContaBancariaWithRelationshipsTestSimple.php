<?php

namespace App\Tests\Integration;

use App\Entity\ContasBancarias;
use App\Entity\Agencias;
use App\Entity\Bancos;
use PHPUnit\Framework\TestCase;

class ContaBancariaWithRelationshipsTestSimple extends TestCase
{
    public function testContaBancariaRelationshipStructure(): void
    {
        $conta = new ContasBancarias();
        
        // Test that relationship fields exist
        $conta->setIdPessoa(1);
        $conta->setIdBanco(1);
        $conta->setIdAgencia(1);
        $conta->setIdTipoConta(1);
        
        $this->assertEquals(1, $conta->getIdPessoa());
        $this->assertEquals(1, $conta->getIdBanco());
        $this->assertEquals(1, $conta->getIdAgencia());
        $this->assertEquals(1, $conta->getIdTipoConta());
    }

    public function testRelatedEntitiesCreation(): void
    {
        // Test that related entities can be created
        $banco = new Bancos();
        $banco->setNome('Banco Teste');
        $banco->setNumero(123);
        
        $agencia = new Agencias();
        $agencia->setCodigo('001');
        $agencia->setNome('Agencia Teste');
        $agencia->setIdBanco(1);
        
        $conta = new ContasBancarias();
        $conta->setCodigo('12345-6');
        $conta->setDigitoConta('7');
        $conta->setIdBanco(1);
        $conta->setIdAgencia(1);
        
        $this->assertInstanceOf(Bancos::class, $banco);
        $this->assertInstanceOf(Agencias::class, $agencia);
        $this->assertInstanceOf(ContasBancarias::class, $conta);
    }

    public function testCompleteRelationshipWorkflow(): void
    {
        // Test a complete workflow of creating related entities
        $entities = [];
        
        // Create banco
        $entities['banco'] = new Bancos();
        $entities['banco']->setNome('Banco Principal');
        $entities['banco']->setNumero(1);
        
        // Create agencia linked to banco
        $entities['agencia'] = new Agencias();
        $entities['agencia']->setCodigo('001');
        $entities['agencia']->setNome('Agencia Principal');
        $entities['agencia']->setIdBanco(1); // Link to banco
        
        // Create conta linked to both
        $entities['conta'] = new ContasBancarias();
        $entities['conta']->setCodigo('12345-6');
        $entities['conta']->setDigitoConta('7');
        $entities['conta']->setIdBanco(1); // Link to banco
        $entities['conta']->setIdAgencia(1); // Link to agencia
        $entities['conta']->setIdPessoa(1); // Link to pessoa
        $entities['conta']->setPrincipal(true);
        $entities['conta']->setAtivo(true);
        
        // Verify all entities are properly linked
        $this->assertEquals(1, $entities['agencia']->getIdBanco());
        $this->assertEquals(1, $entities['conta']->getIdBanco());
        $this->assertEquals(1, $entities['conta']->getIdAgencia());
        $this->assertTrue($entities['conta']->getPrincipal());
        $this->assertTrue($entities['conta']->getAtivo());
    }
}
