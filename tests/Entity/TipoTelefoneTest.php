<?php

namespace App\Tests\Entity;

use App\Entity\TiposTelefones;
use PHPUnit\Framework\TestCase;

class TipoTelefoneTest extends TestCase
{
    public function testCreateTipoTelefone(): void
    {
        $tipo = new TiposTelefones();
        $this->assertInstanceOf(TiposTelefones::class, $tipo);
    }

    public function testTipoTelefoneGettersAndSetters(): void
    {
        $tipo = new TiposTelefones();
        $tipoNome = "Celular";

        $tipo->setTipo($tipoNome);

        $this->assertEquals($tipoNome, $tipo->getTipo());
    }

    public function testTipoTelefoneIdDefault(): void
    {
        $tipo = new TiposTelefones();
        $this->assertNull($tipo->getId());
    }
}
