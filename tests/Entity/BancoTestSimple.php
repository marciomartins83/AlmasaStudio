<?php

namespace App\Tests\Entity;

use App\Entity\Bancos;
use PHPUnit\Framework\TestCase;

class BancoTestSimple extends TestCase
{
    public function testCreateBanco(): void
    {
        $banco = new Bancos();
        $this->assertInstanceOf(Bancos::class, $banco);
    }

    public function testBancoGettersAndSetters(): void
    {
        $banco = new Bancos();
        $nome = "Banco do Brasil";
        $numero = 1;

        $banco->setNome($nome);
        $banco->setNumero($numero);

        $this->assertEquals($nome, $banco->getNome());
        $this->assertEquals($numero, $banco->getNumero());
    }

    public function testBancoIdDefault(): void
    {
        $banco = new Bancos();
        $this->assertNull($banco->getId());
    }
}
