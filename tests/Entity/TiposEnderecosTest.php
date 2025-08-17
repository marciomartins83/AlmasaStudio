<?php

namespace App\Tests\Entity;

use App\Entity\TiposEnderecos;
use PHPUnit\Framework\TestCase;

class TiposEnderecosTest extends TestCase
{
    public function testCreateTipoEndereco(): void
    {
        $tipoEndereco = new TiposEnderecos();
        $this->assertInstanceOf(TiposEnderecos::class, $tipoEndereco);
    }

    public function testTipoEnderecoGettersAndSetters(): void
    {
        $tipoEndereco = new TiposEnderecos();
        $tipo = "Residencial";

        $tipoEndereco->setTipo($tipo);

        $this->assertEquals($tipo, $tipoEndereco->getTipo());
    }

    public function testTipoEnderecoId(): void
    {
        $tipoEndereco = new TiposEnderecos();
        $this->assertNull($tipoEndereco->getId());
    }

    public function testTipoEnderecoVariousTipos(): void
    {
        $tipoEndereco = new TiposEnderecos();
        
        $tipos = ["Residencial", "Comercial", "Correspondência", "Cobrança", "Entrega"];
        
        foreach ($tipos as $tipo) {
            $tipoEndereco->setTipo($tipo);
            $this->assertEquals($tipo, $tipoEndereco->getTipo());
        }
    }

    public function testTipoEnderecoFluentInterface(): void
    {
        $tipoEndereco = new TiposEnderecos();
        
        $result = $tipoEndereco->setTipo("Residencial");
        
        $this->assertSame($tipoEndereco, $result);
        $this->assertEquals("Residencial", $tipoEndereco->getTipo());
    }

    public function testTipoEnderecoMethodsExist(): void
    {
        $tipoEndereco = new TiposEnderecos();
        
        $this->assertTrue(method_exists($tipoEndereco, 'getId'));
        $this->assertTrue(method_exists($tipoEndereco, 'getTipo'));
        $this->assertTrue(method_exists($tipoEndereco, 'setTipo'));
    }
}
