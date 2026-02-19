<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Imoveis;
use App\Entity\ImoveisGarantias;
use PHPUnit\Framework\TestCase;
use DateTimeInterface;
use DateTime;

class ImoveisGarantiasTest extends TestCase
{
    public function testConstructorSetsTimestamps(): void
    {
        $entity = new ImoveisGarantias();

        $createdAt = $entity->getCreatedAt();
        $updatedAt = $entity->getUpdatedAt();

        $this->assertInstanceOf(DateTimeInterface::class, $createdAt);
        $this->assertInstanceOf(DateTimeInterface::class, $updatedAt);
        $this->assertNotNull($createdAt);
        $this->assertNotNull($updatedAt);
    }

    public function testGettersAndSetters(): void
    {
        $entity = new ImoveisGarantias();

        // Imovel relationship
        $imovelMock = $this->createMock(Imoveis::class);
        $this->assertSame($entity, $entity->setImovel($imovelMock));
        $this->assertSame($imovelMock, $entity->getImovel());

        // Boolean fields
        $this->assertFalse($entity->isAceitaCaucao());
        $this->assertFalse($entity->isAceitaFiador());
        $this->assertFalse($entity->isAceitaSeguroFianca());
        $this->assertFalse($entity->isAceitaOutras());

        $this->assertSame($entity, $entity->setAceitaCaucao(true));
        $this->assertSame($entity, $entity->setAceitaFiador(true));
        $this->assertSame($entity, $entity->setAceitaSeguroFianca(true));
        $this->assertSame($entity, $entity->setAceitaOutras(true));

        $this->assertTrue($entity->isAceitaCaucao());
        $this->assertTrue($entity->isAceitaFiador());
        $this->assertTrue($entity->isAceitaSeguroFianca());
        $this->assertTrue($entity->isAceitaOutras());

        // String fields
        $this->assertNull($entity->getValorCaucao());
        $this->assertNull($entity->getSeguradora());
        $this->assertNull($entity->getNumeroApolice());
        $this->assertNull($entity->getValorSeguro());
        $this->assertNull($entity->getObservacoes());

        $this->assertSame($entity, $entity->setValorCaucao('150.00'));
        $this->assertSame($entity, $entity->setSeguradora('Seguradora XYZ'));
        $this->assertSame($entity, $entity->setNumeroApolice('ABC123456'));
        $this->assertSame($entity, $entity->setValorSeguro('200.00'));
        $this->assertSame($entity, $entity->setObservacoes('Observação de teste'));

        $this->assertSame('150.00', $entity->getValorCaucao());
        $this->assertSame('Seguradora XYZ', $entity->getSeguradora());
        $this->assertSame('ABC123456', $entity->getNumeroApolice());
        $this->assertSame('200.00', $entity->getValorSeguro());
        $this->assertSame('Observação de teste', $entity->getObservacoes());

        // Integer fields
        $this->assertNull($entity->getQtdMesesCaucao());
        $this->assertSame($entity, $entity->setQtdMesesCaucao(12));
        $this->assertSame(12, $entity->getQtdMesesCaucao());

        // Date fields
        $this->assertNull($entity->getVencimentoSeguro());
        $date = new DateTime('2025-12-31');
        $this->assertSame($entity, $entity->setVencimentoSeguro($date));
        $this->assertSame($date, $entity->getVencimentoSeguro());

        // Timestamps
        $this->assertInstanceOf(DateTimeInterface::class, $entity->getCreatedAt());
        $this->assertInstanceOf(DateTimeInterface::class, $entity->getUpdatedAt());

        $newCreated = new DateTime('2024-01-01');
        $newUpdated = new DateTime('2024-01-02');
        $this->assertSame($entity, $entity->setCreatedAt($newCreated));
        $this->assertSame($entity, $entity->setUpdatedAt($newUpdated));

        $this->assertSame($newCreated, $entity->getCreatedAt());
        $this->assertSame($newUpdated, $entity->getUpdatedAt());

        // ID
        $this->assertNull($entity->getId());
    }

    public function testChainingAndReturnValues(): void
    {
        $entity = new ImoveisGarantias();

        $imovelMock = $this->createMock(Imoveis::class);

        $this->assertSame($entity, $entity->setImovel($imovelMock));
        $this->assertSame($entity, $entity->setAceitaCaucao(true));
        $this->assertSame($entity, $entity->setAceitaFiador(true));
        $this->assertSame($entity, $entity->setAceitaSeguroFianca(true));
        $this->assertSame($entity, $entity->setAceitaOutras(true));
        $this->assertSame($entity, $entity->setValorCaucao('100.00'));
        $this->assertSame($entity, $entity->setQtdMesesCaucao(6));
        $this->assertSame($entity, $entity->setSeguradora('Seguradora ABC'));
        $this->assertSame($entity, $entity->setNumeroApolice('XYZ987'));
        $this->assertSame($entity, $entity->setVencimentoSeguro(new DateTime('2025-01-01')));
        $this->assertSame($entity, $entity->setValorSeguro('250.00'));
        $this->assertSame($entity, $entity->setObservacoes('Teste'));
        $this->assertSame($entity, $entity->setCreatedAt(new DateTime('2023-01-01')));
        $this->assertSame($entity, $entity->setUpdatedAt(new DateTime('2023-01-02')));
    }
}
