<?php

namespace App\Tests\Entity;

use App\Entity\Agencias;
use PHPUnit\Framework\TestCase;

class AgenciaTestSimple extends TestCase
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
        $idBanco = 1;
        $idEndereco = 10;

        $agencia->setCodigo($codigo);
        $agencia->setNome($nome);
        $agencia->setIdBanco($idBanco);
        $agencia->setIdEndereco($idEndereco);

        $this->assertEquals($codigo, $agencia->getCodigo());
        $this->assertEquals($nome, $agencia->getNome());
        $this->assertEquals($idBanco, $agencia->getIdBanco());
        $this->assertEquals($idEndereco, $agencia->getIdEndereco());
    }

    public function testAgenciaIdDefault(): void
    {
        $agencia = new Agencias();
        $this->assertNull($agencia->getId());
    }
}
