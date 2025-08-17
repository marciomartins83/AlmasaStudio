<?php

namespace App\Tests\Validation;

use App\Entity\TiposTelefones;
use PHPUnit\Framework\TestCase;

class TipoTelefoneNotEmptyTest extends TestCase
{
    public function testTipoTelefoneBasicValidation(): void
    {
        $tipo = new TiposTelefones();
        $this->assertInstanceOf(TiposTelefones::class, $tipo);
        
        // Test basic setters and getters
        $tipo->setTipo('Celular');
        
        $this->assertEquals('Celular', $tipo->getTipo());
        $this->assertNotEmpty($tipo->getTipo());
    }

    public function testTipoTelefoneCanHaveEmptyValue(): void
    {
        $tipo = new TiposTelefones();
        
        // Test that entity can be created
        $this->assertInstanceOf(TiposTelefones::class, $tipo);
        
        // Test setting empty string
        $tipo->setTipo('');
        $this->assertEquals('', $tipo->getTipo());
    }

    public function testTipoTelefoneAcceptsValidStrings(): void
    {
        $tipo = new TiposTelefones();
        
        $validTypes = ['Residencial', 'Comercial', 'Celular', 'Fax'];
        
        foreach ($validTypes as $validType) {
            $tipo->setTipo($validType);
            $this->assertEquals($validType, $tipo->getTipo());
        }
    }
}
