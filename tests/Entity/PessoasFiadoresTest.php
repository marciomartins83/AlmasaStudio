<?php

namespace App\Tests\Entity;

use App\Entity\PessoasFiadores;
use PHPUnit\Framework\TestCase;

class PessoasFiadoresTest extends TestCase
{
    public function testCreatePessoaFiador(): void
    {
        $pessoaFiador = new PessoasFiadores();
        $this->assertInstanceOf(PessoasFiadores::class, $pessoaFiador);
    }

    public function testPessoaFiadorGettersAndSetters(): void
    {
        $pessoaFiador = new PessoasFiadores();
        $idPessoa = 1;
        $idConjuge = 2;
        $motivoFianca = "Garantia para locação de imóvel residencial";
        $jaFoiFiador = true;
        $conjugeTrabalha = false;
        $outros = "Informações adicionais sobre o fiador";
        $idFormaRetirada = 3;

        $pessoaFiador->setIdPessoa($idPessoa);
        $pessoaFiador->setIdConjuge($idConjuge);
        $pessoaFiador->setMotivoFianca($motivoFianca);
        $pessoaFiador->setJaFoiFiador($jaFoiFiador);
        $pessoaFiador->setConjugeTrabalha($conjugeTrabalha);
        $pessoaFiador->setOutros($outros);
        $pessoaFiador->setIdFormaRetirada($idFormaRetirada);

        $this->assertEquals($idPessoa, $pessoaFiador->getIdPessoa());
        $this->assertEquals($idConjuge, $pessoaFiador->getIdConjuge());
        $this->assertEquals($motivoFianca, $pessoaFiador->getMotivoFianca());
        $this->assertEquals($jaFoiFiador, $pessoaFiador->getJaFoiFiador());
        $this->assertEquals($conjugeTrabalha, $pessoaFiador->getConjugeTrabalha());
        $this->assertEquals($outros, $pessoaFiador->getOutros());
        $this->assertEquals($idFormaRetirada, $pessoaFiador->getIdFormaRetirada());
    }

    public function testPessoaFiadorNullableFields(): void
    {
        $pessoaFiador = new PessoasFiadores();
        
        // Test that nullable fields start as null or default values
        $this->assertNull($pessoaFiador->getIdConjuge());
        $this->assertNull($pessoaFiador->getMotivoFianca());
        $this->assertNull($pessoaFiador->getOutros());
        $this->assertNull($pessoaFiador->getIdFormaRetirada());
        
        // Test boolean default values
        $this->assertFalse($pessoaFiador->getJaFoiFiador());
        $this->assertFalse($pessoaFiador->getConjugeTrabalha());
    }

    public function testPessoaFiadorBooleanFields(): void
    {
        $pessoaFiador = new PessoasFiadores();
        
        // Test setting boolean values
        $pessoaFiador->setJaFoiFiador(true);
        $pessoaFiador->setConjugeTrabalha(true);
        
        $this->assertTrue($pessoaFiador->getJaFoiFiador());
        $this->assertTrue($pessoaFiador->getConjugeTrabalha());
        
        // Test setting false
        $pessoaFiador->setJaFoiFiador(false);
        $pessoaFiador->setConjugeTrabalha(false);
        
        $this->assertFalse($pessoaFiador->getJaFoiFiador());
        $this->assertFalse($pessoaFiador->getConjugeTrabalha());
    }

    public function testPessoaFiadorMethodsExist(): void
    {
        $pessoaFiador = new PessoasFiadores();
        
        // Test that all required methods exist
        $this->assertTrue(method_exists($pessoaFiador, 'getIdPessoa'));
        $this->assertTrue(method_exists($pessoaFiador, 'setIdPessoa'));
        $this->assertTrue(method_exists($pessoaFiador, 'getIdConjuge'));
        $this->assertTrue(method_exists($pessoaFiador, 'setIdConjuge'));
        $this->assertTrue(method_exists($pessoaFiador, 'getMotivoFianca'));
        $this->assertTrue(method_exists($pessoaFiador, 'setMotivoFianca'));
        $this->assertTrue(method_exists($pessoaFiador, 'getJaFoiFiador'));
        $this->assertTrue(method_exists($pessoaFiador, 'setJaFoiFiador'));
        $this->assertTrue(method_exists($pessoaFiador, 'getConjugeTrabalha'));
        $this->assertTrue(method_exists($pessoaFiador, 'setConjugeTrabalha'));
        $this->assertTrue(method_exists($pessoaFiador, 'getOutros'));
        $this->assertTrue(method_exists($pessoaFiador, 'setOutros'));
        $this->assertTrue(method_exists($pessoaFiador, 'getIdFormaRetirada'));
        $this->assertTrue(method_exists($pessoaFiador, 'setIdFormaRetirada'));
    }

    public function testPessoaFiadorMotivoFiancaVariations(): void
    {
        $pessoaFiador = new PessoasFiadores();
        
        // Test with different motivo fianca texts
        $motivos = [
            "Garantia para locação residencial",
            "Fiança comercial para loja",
            "Garantia de contrato de aluguel",
            "Aval para locação de apartamento",
            "Garantia solidária"
        ];
        
        foreach ($motivos as $motivo) {
            $pessoaFiador->setMotivoFianca($motivo);
            $this->assertEquals($motivo, $pessoaFiador->getMotivoFianca());
        }
    }

    public function testPessoaFiadorFluentInterface(): void
    {
        $pessoaFiador = new PessoasFiadores();
        
        // Test that setters return self for fluent interface
        $result = $pessoaFiador->setIdPessoa(1)
                              ->setIdConjuge(2)
                              ->setMotivoFianca("Garantia para locação")
                              ->setJaFoiFiador(true)
                              ->setConjugeTrabalha(false);
        
        $this->assertSame($pessoaFiador, $result);
        $this->assertEquals(1, $pessoaFiador->getIdPessoa());
        $this->assertEquals(2, $pessoaFiador->getIdConjuge());
        $this->assertEquals("Garantia para locação", $pessoaFiador->getMotivoFianca());
        $this->assertTrue($pessoaFiador->getJaFoiFiador());
        $this->assertFalse($pessoaFiador->getConjugeTrabalha());
    }

    public function testPessoaFiadorNullableFieldsToNull(): void
    {
        $pessoaFiador = new PessoasFiadores();
        
        // Set some values first
        $pessoaFiador->setIdConjuge(1);
        $pessoaFiador->setMotivoFianca("Test");
        $pessoaFiador->setOutros("Test");
        $pessoaFiador->setIdFormaRetirada(1);
        
        // Then set them to null
        $pessoaFiador->setIdConjuge(null);
        $pessoaFiador->setMotivoFianca(null);
        $pessoaFiador->setOutros(null);
        $pessoaFiador->setIdFormaRetirada(null);
        
        $this->assertNull($pessoaFiador->getIdConjuge());
        $this->assertNull($pessoaFiador->getMotivoFianca());
        $this->assertNull($pessoaFiador->getOutros());
        $this->assertNull($pessoaFiador->getIdFormaRetirada());
    }

    public function testPessoaFiadorLongTexts(): void
    {
        $pessoaFiador = new PessoasFiadores();
        
        // Test with long texts for text fields
        $longMotivo = str_repeat("Este é um motivo muito longo para fiança. ", 50);
        $longOutros = str_repeat("Estas são informações adicionais extensas. ", 50);
        
        $pessoaFiador->setMotivoFianca($longMotivo);
        $pessoaFiador->setOutros($longOutros);
        
        $this->assertEquals($longMotivo, $pessoaFiador->getMotivoFianca());
        $this->assertEquals($longOutros, $pessoaFiador->getOutros());
    }

    public function testPessoaFiadorDifferentIdValues(): void
    {
        $pessoaFiador = new PessoasFiadores();
        
        // Test with different ID values
        $ids = [1, 100, 999, 12345];
        
        foreach ($ids as $id) {
            $pessoaFiador->setIdPessoa($id);
            $this->assertEquals($id, $pessoaFiador->getIdPessoa());
            
            $pessoaFiador->setIdConjuge($id);
            $this->assertEquals($id, $pessoaFiador->getIdConjuge());
            
            $pessoaFiador->setIdFormaRetirada($id);
            $this->assertEquals($id, $pessoaFiador->getIdFormaRetirada());
        }
    }
}
