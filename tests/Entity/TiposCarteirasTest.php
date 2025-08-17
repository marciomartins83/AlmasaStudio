<?php

namespace App\Tests\Entity;

use App\Entity\TiposCarteiras;
use PHPUnit\Framework\TestCase;

class TiposCarteirasTest extends TestCase
{
    public function testCreateTipoCarteira(): void
    {
        $tipoCarteira = new TiposCarteiras();
        $this->assertInstanceOf(TiposCarteiras::class, $tipoCarteira);
    }

    public function testTipoCarteiraGettersAndSetters(): void
    {
        $tipoCarteira = new TiposCarteiras();
        $tipo = "Cobrança Simples";
        $descricao = "Carteira para cobrança bancária simples";

        $tipoCarteira->setTipo($tipo);
        $tipoCarteira->setDescricao($descricao);

        $this->assertEquals($tipo, $tipoCarteira->getTipo());
        $this->assertEquals($descricao, $tipoCarteira->getDescricao());
    }

    public function testTipoCarteiraId(): void
    {
        $tipoCarteira = new TiposCarteiras();
        $this->assertNull($tipoCarteira->getId());
    }

    public function testTipoCarteiraDescricaoNullable(): void
    {
        $tipoCarteira = new TiposCarteiras();
        $this->assertNull($tipoCarteira->getDescricao());
        
        $tipoCarteira->setDescricao(null);
        $this->assertNull($tipoCarteira->getDescricao());
    }

    public function testTipoCarteiraVariousTipos(): void
    {
        $tipoCarteira = new TiposCarteiras();
        
        $tipos = ["Cobrança Simples", "Cobrança Registrada", "Desconto de Duplicatas", "Vendor", "Caução"];
        
        foreach ($tipos as $tipo) {
            $tipoCarteira->setTipo($tipo);
            $this->assertEquals($tipo, $tipoCarteira->getTipo());
        }
    }

    public function testTipoCarteiraFluentInterface(): void
    {
        $tipoCarteira = new TiposCarteiras();
        
        $result = $tipoCarteira->setTipo("Cobrança Simples")
                              ->setDescricao("Carteira básica de cobrança");
        
        $this->assertSame($tipoCarteira, $result);
        $this->assertEquals("Cobrança Simples", $tipoCarteira->getTipo());
        $this->assertEquals("Carteira básica de cobrança", $tipoCarteira->getDescricao());
    }
}
