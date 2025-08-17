<?php

namespace App\Tests\Entity;

use App\Entity\TiposRemessa;
use PHPUnit\Framework\TestCase;

class TiposRemessaTest extends TestCase
{
    public function testCreateTipoRemessa(): void
    {
        $tipoRemessa = new TiposRemessa();
        $this->assertInstanceOf(TiposRemessa::class, $tipoRemessa);
    }

    public function testTipoRemessaGettersAndSetters(): void
    {
        $tipoRemessa = new TiposRemessa();
        $tipo = "CNAB 240";
        $descricao = "Arquivo de remessa padrão CNAB 240";

        $tipoRemessa->setTipo($tipo);
        $tipoRemessa->setDescricao($descricao);

        $this->assertEquals($tipo, $tipoRemessa->getTipo());
        $this->assertEquals($descricao, $tipoRemessa->getDescricao());
    }

    public function testTipoRemessaVariousTipos(): void
    {
        $tipoRemessa = new TiposRemessa();
        
        $tipos = ["CNAB 240", "CNAB 400", "EDI", "API", "Manual"];
        
        foreach ($tipos as $tipo) {
            $tipoRemessa->setTipo($tipo);
            $this->assertEquals($tipo, $tipoRemessa->getTipo());
        }
    }

    public function testTipoRemessaFluentInterface(): void
    {
        $tipoRemessa = new TiposRemessa();
        
        $result = $tipoRemessa->setTipo("CNAB 240")
                            ->setDescricao("Padrão bancário moderno");
        
        $this->assertSame($tipoRemessa, $result);
    }
}
