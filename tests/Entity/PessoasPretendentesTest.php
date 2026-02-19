<?php

namespace App\Tests\Entity;

use App\Entity\PessoasPretendentes;
use PHPUnit\Framework\TestCase;

class PessoasPretendentesTest extends TestCase
{
    public function testCreateEntity(): void
    {
        $entity = new PessoasPretendentes();
        $this->assertInstanceOf(PessoasPretendentes::class, $entity);
    }

    public function testBasicMethods(): void
    {
        $entity = new PessoasPretendentes();

        // Test that basic methods exist
        $this->assertTrue(method_exists($entity, 'getId'));
        $this->assertTrue(method_exists($entity, 'getPessoa'));
        $this->assertTrue(method_exists($entity, 'setPessoa'));
        $this->assertTrue(method_exists($entity, 'getTipoImovel'));
        $this->assertTrue(method_exists($entity, 'setTipoImovel'));
        $this->assertTrue(method_exists($entity, 'getQuartosDesejados'));
        $this->assertTrue(method_exists($entity, 'setQuartosDesejados'));
        $this->assertTrue(method_exists($entity, 'getAluguelMaximo'));
        $this->assertTrue(method_exists($entity, 'setAluguelMaximo'));
        $this->assertTrue(method_exists($entity, 'getLogradouroDesejado'));
        $this->assertTrue(method_exists($entity, 'setLogradouroDesejado'));
        $this->assertTrue(method_exists($entity, 'isDisponivel'));
        $this->assertTrue(method_exists($entity, 'setDisponivel'));
        $this->assertTrue(method_exists($entity, 'isProcuraAluguel'));
        $this->assertTrue(method_exists($entity, 'setProcuraAluguel'));
        $this->assertTrue(method_exists($entity, 'isProcuraCompra'));
        $this->assertTrue(method_exists($entity, 'setProcuraCompra'));
        $this->assertTrue(method_exists($entity, 'getAtendente'));
        $this->assertTrue(method_exists($entity, 'setAtendente'));
        $this->assertTrue(method_exists($entity, 'getTipoAtendimento'));
        $this->assertTrue(method_exists($entity, 'setTipoAtendimento'));
        $this->assertTrue(method_exists($entity, 'getDataCadastro'));
        $this->assertTrue(method_exists($entity, 'setDataCadastro'));
        $this->assertTrue(method_exists($entity, 'getObservacoes'));
        $this->assertTrue(method_exists($entity, 'setObservacoes'));
    }

    public function testEntityStructure(): void
    {
        $entity = new PessoasPretendentes();
        $this->assertInstanceOf(PessoasPretendentes::class, $entity);
    }
}