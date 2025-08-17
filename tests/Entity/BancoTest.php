<?php

namespace App\Tests\Entity;

use App\Entity\Bancos;
use PHPUnit\Framework\TestCase;

class BancoTest extends TestCase
{
    public function testCreateBanco(): void
    {
        $banco = new Bancos();
        $this->assertInstanceOf(Bancos::class, $banco);
    }

    public function testBancoGettersAndSetters(): void
    {
        $banco = new Bancos();
        $nomeBanco = "Banco Teste";
        $numero = 1;

        $banco->setNome($nomeBanco);
        $banco->setNumero($numero);

        $this->assertEquals($nomeBanco, $banco->getNome());
        $this->assertEquals($numero, $banco->getNumero());
    }

    public function testBancoIdDefault(): void
    {
        $banco = new Bancos();
        $this->assertNull($banco->getId());
    }
}
