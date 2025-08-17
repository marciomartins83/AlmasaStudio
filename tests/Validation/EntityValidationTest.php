<?php

namespace App\Tests\Validation;

use App\Entity\Agencias;
use App\Entity\ContasBancarias;
use App\Entity\TiposTelefones;
use PHPUnit\Framework\TestCase;

class EntityValidationTest extends TestCase
{
    public function testAgenciaEntityCanBeCreated(): void
    {
        $agencia = new Agencias();
        $this->assertInstanceOf(Agencias::class, $agencia);
        
        $agencia->setCodigo('001');
        $agencia->setNome('Agencia Teste');
        
        $this->assertEquals('001', $agencia->getCodigo());
        $this->assertEquals('Agencia Teste', $agencia->getNome());
    }

    public function testContaBancariaEntityCanBeCreated(): void
    {
        $conta = new ContasBancarias();
        $this->assertInstanceOf(ContasBancarias::class, $conta);
        
        $conta->setCodigo('12345-6');
        $conta->setDigitoConta('7');
        
        $this->assertEquals('12345-6', $conta->getCodigo());
        $this->assertEquals('7', $conta->getDigitoConta());
    }

    public function testTipoTelefoneEntityCanBeCreated(): void
    {
        $tipo = new TiposTelefones();
        $this->assertInstanceOf(TiposTelefones::class, $tipo);
        
        $tipo->setTipo('Celular');
        
        $this->assertEquals('Celular', $tipo->getTipo());
    }

    public function testEntityFieldsAreInitializedCorrectly(): void
    {
        $agencia = new Agencias();
        $this->assertNull($agencia->getId());
        
        $conta = new ContasBancarias();
        $this->assertNull($conta->getId());
        
        $tipo = new TiposTelefones();
        $this->assertNull($tipo->getId());
    }
}
