<?php

namespace App\Tests\Entity;

use App\Entity\Pessoas;
use App\Entity\TiposDocumentos;
use App\Entity\Estados;
use PHPUnit\Framework\TestCase;

class EntityTestSimple extends TestCase
{
    public function testPessoaCanBeCreated(): void
    {
        $pessoa = new Pessoas();
        $pessoa->setNome('João Silva Teste');
        $pessoa->setFisicaJuridica('fisica');
        $pessoa->setTipoPessoa(1);
        $pessoa->setStatus(true);
        $pessoa->setDtCadastro(new \DateTime());

        $this->assertEquals('João Silva Teste', $pessoa->getNome());
        $this->assertEquals('fisica', $pessoa->getFisicaJuridica());
        $this->assertEquals(1, $pessoa->getTipoPessoa());
        $this->assertTrue($pessoa->getStatus());
        $this->assertInstanceOf(\DateTime::class, $pessoa->getDtCadastro());
    }

    public function testEstadoCanBeCreated(): void
    {
        $estado = new Estados();
        $estado->setNome('São Paulo');
        $estado->setUf('SP');

        $this->assertEquals('São Paulo', $estado->getNome());
        $this->assertEquals('SP', $estado->getUf());
    }

    public function testTipoDocumentoCanBeCreated(): void
    {
        $tipo = new TiposDocumentos();
        $tipo->setTipo('CPF');

        $this->assertEquals('CPF', $tipo->getTipo());
    }
}
