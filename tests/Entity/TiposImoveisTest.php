<?php

namespace App\Tests\Entity;

use App\Entity\TiposImoveis;
use PHPUnit\Framework\TestCase;

class TiposImoveisTest extends TestCase
{
    public function testCreateTipoImovel(): void
    {
        $tipoImovel = new TiposImoveis();
        $this->assertInstanceOf(TiposImoveis::class, $tipoImovel);
    }

    public function testTipoImovelGettersAndSetters(): void
    {
        $tipoImovel = new TiposImoveis();
        $tipo = "Apartamento";
        $descricao = "Unidade habitacional em edifício";

        $tipoImovel->setTipo($tipo);
        $tipoImovel->setDescricao($descricao);

        $this->assertEquals($tipo, $tipoImovel->getTipo());
        $this->assertEquals($descricao, $tipoImovel->getDescricao());
    }

    public function testTipoImovelId(): void
    {
        $tipoImovel = new TiposImoveis();
        $this->assertNull($tipoImovel->getId());
    }

    public function testTipoImovelDescricaoNullable(): void
    {
        $tipoImovel = new TiposImoveis();
        $this->assertNull($tipoImovel->getDescricao());
        
        $tipoImovel->setDescricao(null);
        $this->assertNull($tipoImovel->getDescricao());
    }

    public function testTipoImovelVariousTipos(): void
    {
        $tipoImovel = new TiposImoveis();
        
        $tipos = ["Apartamento", "Casa", "Kitnet", "Loja", "Sala Comercial", "Galpão", "Terreno"];
        
        foreach ($tipos as $tipo) {
            $tipoImovel->setTipo($tipo);
            $this->assertEquals($tipo, $tipoImovel->getTipo());
        }
    }

    public function testTipoImovelFluentInterface(): void
    {
        $tipoImovel = new TiposImoveis();
        
        $result = $tipoImovel->setTipo("Apartamento")
                           ->setDescricao("Residência em condomínio");
        
        $this->assertSame($tipoImovel, $result);
        $this->assertEquals("Apartamento", $tipoImovel->getTipo());
        $this->assertEquals("Residência em condomínio", $tipoImovel->getDescricao());
    }
}
