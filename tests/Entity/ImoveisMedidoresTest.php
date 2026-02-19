<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Imoveis;
use App\Entity\ImoveisMedidores;
use DateTime;
use PHPUnit\Framework\TestCase;

final class ImoveisMedidoresTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $medidor = new ImoveisMedidores();

        // id should be null initially
        $this->assertNull($medidor->getId());

        // ativo default is true
        $this->assertTrue($medidor->isAtivo());

        // createdAt should be a DateTimeInterface instance
        $this->assertInstanceOf(\DateTimeInterface::class, $medidor->getCreatedAt());
    }

    public function testGettersAndSetters(): void
    {
        $medidor = new ImoveisMedidores();

        // Mock Imovel entity
        $imovel = $this->createMock(Imoveis::class);

        // Set all properties
        $medidor
            ->setImovel($imovel)
            ->setTipoMedidor('ELECTRIC')
            ->setNumeroMedidor('123456')
            ->setConcessionaria('Concessionária X')
            ->setObservacoes('Observação de teste')
            ->setAtivo(false)
            ->setCreatedAt(new DateTime('2023-01-01 12:00:00'));

        // Assert getters return the set values
        $this->assertSame($imovel, $medidor->getImovel());
        $this->assertSame('ELECTRIC', $medidor->getTipoMedidor());
        $this->assertSame('123456', $medidor->getNumeroMedidor());
        $this->assertSame('Concessionária X', $medidor->getConcessionaria());
        $this->assertSame('Observação de teste', $medidor->getObservacoes());
        $this->assertFalse($medidor->isAtivo());
        $this->assertEquals(new DateTime('2023-01-01 12:00:00'), $medidor->getCreatedAt());

        // Test chaining
        $this->assertSame($medidor, $medidor->setTipoMedidor('WATER'));
        $this->assertSame('WATER', $medidor->getTipoMedidor());
    }

    public function testImovelRelationship(): void
    {
        $medidor = new ImoveisMedidores();

        $imovel = $this->createMock(Imoveis::class);
        $medidor->setImovel($imovel);

        $this->assertSame($imovel, $medidor->getImovel());
    }

    public function testSetCreatedAt(): void
    {
        $medidor = new ImoveisMedidores();

        $date = new DateTime('2024-05-20 08:30:00');
        $medidor->setCreatedAt($date);

        $this->assertSame($date, $medidor->getCreatedAt());
    }

    public function testIsAtivoAndSetAtivo(): void
    {
        $medidor = new ImoveisMedidores();

        // Default should be true
        $this->assertTrue($medidor->isAtivo());

        // Set to false
        $medidor->setAtivo(false);
        $this->assertFalse($medidor->isAtivo());

        // Set back to true
        $medidor->setAtivo(true);
        $this->assertTrue($medidor->isAtivo());
    }

    public function testConcessionariaAndObservacoesNullable(): void
    {
        $medidor = new ImoveisMedidores();

        // Set to null
        $medidor->setConcessionaria(null);
        $medidor->setObservacoes(null);

        $this->assertNull($medidor->getConcessionaria());
        $this->assertNull($medidor->getObservacoes());

        // Set to strings
        $medidor->setConcessionaria('Concessionária Y');
        $medidor->setObservacoes('Outra observação');

        $this->assertSame('Concessionária Y', $medidor->getConcessionaria());
        $this->assertSame('Outra observação', $medidor->getObservacoes());
    }

    public function testTipoMedidorAndNumeroMedidor(): void
    {
        $medidor = new ImoveisMedidores();

        $medidor->setTipoMedidor('GAS');
        $medidor->setNumeroMedidor('987654');

        $this->assertSame('GAS', $medidor->getTipoMedidor());
        $this->assertSame('987654', $medidor->getNumeroMedidor());
    }
}
