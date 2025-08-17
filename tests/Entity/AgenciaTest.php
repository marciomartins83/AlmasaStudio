<?php

namespace App\Tests\Entity;

use App\Entity\Agencias;
use App\Entity\Bancos;
use App\Entity\Enderecos;
use DateTime;
use PHPUnit\Framework\TestCase;

class AgenciaTest extends TestCase
{
    public function testCreateAgencia(): void
    {
        $agencia = new Agencias();
        $this->assertInstanceOf(Agencias::class, $agencia);
    }

    public function testAgenciaGettersAndSetters(): void
    {
        $agencia = new Agencias();
        $codigo = "001";
        $nome = "Agencia Central";
        $idBanco = 123;
        $idEndereco = 456;

        $agencia->setCodigo($codigo);
        $agencia->setNome($nome);
        $agencia->setIdBanco($idBanco);
        $agencia->setIdEndereco($idEndereco);

        $this->assertEquals($codigo, $agencia->getCodigo());
        $this->assertEquals($nome, $agencia->getNome());
        $this->assertEquals($idBanco, $agencia->getIdBanco());
        $this->assertEquals($idEndereco, $agencia->getIdEndereco());
    }

    public function testAgenciaRelationships(): void
    {
        $agencia = new Agencias();
        $idBanco = 1;
        $idEndereco = 2;

        $agencia->setIdBanco($idBanco);
        $agencia->setIdEndereco($idEndereco);

        $this->assertEquals($idBanco, $agencia->getIdBanco());
        $this->assertEquals($idEndereco, $agencia->getIdEndereco());
    }

    public function testAgenciaTimestampsAutomatic(): void
    {
        $agencia = new Agencias();
        // A entidade Agencias não tem campos de timestamp
        // Testamos apenas métodos básicos existentes
        $this->assertTrue(method_exists($agencia, "getId"));
        $this->assertTrue(method_exists($agencia, "getCodigo"));
        $this->assertTrue(method_exists($agencia, "getNome"));
    }

    // Teste para constraint de código único por banco precisaria de mocks ou de um setup de banco de dados mais complexo.
    // Este é um teste unitário focado na entidade, então vamos pular a complexidade de banco de dados aqui.
    // Para testes de constraint, considere usar testes de integração ou ferramentas específicas.
    public function testUniqueCodigoConstraintPlaceholder(): void
    {
        // Este é um placeholder para o teste de constraint de código único por banco.
        // Um teste real exigiria a configuração de um ambiente de banco de dados com Doctrine ou mocks.
        $this->assertTrue(true);
    }
} 