<?php

namespace App\Tests\Entity;

use App\Entity\Emails;
use App\Entity\Telefones;
use App\Entity\TiposDocumentos;
use App\Entity\Enderecos;
use App\Entity\Estados;
use App\Entity\Cidades;
use App\Entity\Bairros;
use App\Entity\Logradouros;
use PHPUnit\Framework\TestCase;

class EntityTest extends TestCase
{
    public function testEstadoCanBeCreated(): void
    {
        $estado = new Estados();
        $this->assertInstanceOf(Estados::class, $estado);
    }

    public function testCidadeCanBeCreated(): void
    {
        $cidade = new Cidades();
        $this->assertInstanceOf(Cidades::class, $cidade);
    }

    public function testTipoDocumentoCanBeCreated(): void
    {
        $tipoDocumento = new TiposDocumentos();
        $this->assertInstanceOf(TiposDocumentos::class, $tipoDocumento);
    }

    public function testEnderecoCanBeCreated(): void
    {
        $endereco = new Enderecos();
        $this->assertInstanceOf(Enderecos::class, $endereco);
    }

    public function testBairroCanBeCreated(): void
    {
        $bairro = new Bairros();
        $this->assertInstanceOf(Bairros::class, $bairro);
    }

    public function testLogradouroCanBeCreated(): void
    {
        $logradouro = new Logradouros();
        $this->assertInstanceOf(Logradouros::class, $logradouro);
    }

    public function testEmailCanBeCreated(): void
    {
        $email = new Emails();
        $this->assertInstanceOf(Emails::class, $email);
    }

    public function testTelefoneCanBeCreated(): void
    {
        $telefone = new Telefones();
        $this->assertInstanceOf(Telefones::class, $telefone);
    }
}