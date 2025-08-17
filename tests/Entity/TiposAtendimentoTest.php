<?php

namespace App\Tests\Entity;

use App\Entity\TiposAtendimento;
use PHPUnit\Framework\TestCase;

class TiposAtendimentoTest extends TestCase
{
    public function testCreateTipoAtendimento(): void
    {
        $tipoAtendimento = new TiposAtendimento();
        $this->assertInstanceOf(TiposAtendimento::class, $tipoAtendimento);
    }

    public function testTipoAtendimentoGettersAndSetters(): void
    {
        $tipoAtendimento = new TiposAtendimento();
        $tipo = "Presencial";
        $descricao = "Atendimento realizado presencialmente";

        $tipoAtendimento->setTipo($tipo);
        $tipoAtendimento->setDescricao($descricao);

        $this->assertEquals($tipo, $tipoAtendimento->getTipo());
        $this->assertEquals($descricao, $tipoAtendimento->getDescricao());
    }

    public function testTipoAtendimentoId(): void
    {
        $tipoAtendimento = new TiposAtendimento();
        $this->assertNull($tipoAtendimento->getId());
    }

    public function testTipoAtendimentoDescricaoNullable(): void
    {
        $tipoAtendimento = new TiposAtendimento();
        $this->assertNull($tipoAtendimento->getDescricao());
        
        $tipoAtendimento->setDescricao(null);
        $this->assertNull($tipoAtendimento->getDescricao());
    }

    public function testTipoAtendimentoVariousTipos(): void
    {
        $tipoAtendimento = new TiposAtendimento();
        
        $tipos = ["Presencial", "Online", "Telefônico", "Chat", "Email"];
        
        foreach ($tipos as $tipo) {
            $tipoAtendimento->setTipo($tipo);
            $this->assertEquals($tipo, $tipoAtendimento->getTipo());
        }
    }

    public function testTipoAtendimentoFluentInterface(): void
    {
        $tipoAtendimento = new TiposAtendimento();
        
        $result = $tipoAtendimento->setTipo("Presencial")
                                ->setDescricao("Atendimento face a face");
        
        $this->assertSame($tipoAtendimento, $result);
        $this->assertEquals("Presencial", $tipoAtendimento->getTipo());
        $this->assertEquals("Atendimento face a face", $tipoAtendimento->getDescricao());
    }
}
