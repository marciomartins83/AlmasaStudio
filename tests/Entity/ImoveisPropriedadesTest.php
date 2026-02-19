<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Imoveis;
use App\Entity\ImoveisPropriedades;
use App\Entity\PropriedadesCatalogo;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use PHPUnit\Framework\TestCase;

final class ImoveisPropriedadesTest extends TestCase
{
    public function testGettersAndSetters(): void
    {
        $entity = new ImoveisPropriedades();

        // Mocks for related entities
        $imovelMock = $this->createMock(Imoveis::class);
        $propriedadeMock = $this->createMock(PropriedadesCatalogo::class);

        // Test setters return $this (fluent interface)
        $this->assertSame($entity, $entity->setImovel($imovelMock));
        $this->assertSame($entity, $entity->setPropriedade($propriedadeMock));

        // Test getters return the same instances
        $this->assertSame($imovelMock, $entity->getImovel());
        $this->assertSame($propriedadeMock, $entity->getPropriedade());

        // Test createdAt setter and getter
        $createdAt = new DateTimeImmutable('2023-01-01 12:00:00');
        $this->assertSame($entity, $entity->setCreatedAt($createdAt));
        $this->assertSame($createdAt, $entity->getCreatedAt());

        // ID should remain null (no setter)
        $this->assertNull($entity->getId());
    }

    public function testCreatedAtDefaultValue(): void
    {
        $entity = new ImoveisPropriedades();

        $createdAt = $entity->getCreatedAt();
        $this->assertInstanceOf(DateTimeInterface::class, $createdAt);
        $this->assertNotNull($createdAt);

        // The default createdAt should be close to the current time
        $now = new DateTime();
        $this->assertLessThanOrEqual($now->getTimestamp() + 1, $createdAt->getTimestamp());
        $this->assertGreaterThanOrEqual($now->getTimestamp() - 5, $createdAt->getTimestamp());
    }

    public function testSetCreatedAtWithSpecificDate(): void
    {
        $entity = new ImoveisPropriedades();

        $customDate = new DateTime('2022-12-31 23:59:59');
        $entity->setCreatedAt($customDate);

        $this->assertSame($customDate, $entity->getCreatedAt());
        $this->assertInstanceOf(DateTimeInterface::class, $entity->getCreatedAt());
        $this->assertNotNull($entity->getCreatedAt());
    }

    public function testRelationships(): void
    {
        $entity = new ImoveisPropriedades();

        $imovelMock = $this->createMock(Imoveis::class);
        $propriedadeMock = $this->createMock(PropriedadesCatalogo::class);

        $entity->setImovel($imovelMock);
        $entity->setPropriedade($propriedadeMock);

        $this->assertSame($imovelMock, $entity->getImovel());
        $this->assertSame($propriedadeMock, $entity->getPropriedade());
    }

    public function testIdRemainsNullAfterSettingOtherProperties(): void
    {
        $entity = new ImoveisPropriedades();

        $imovelMock = $this->createMock(Imoveis::class);
        $propriedadeMock = $this->createMock(PropriedadesCatalogo::class);
        $createdAt = new DateTimeImmutable('2023-01-01 12:00:00');

        $entity->setImovel($imovelMock);
        $entity->setPropriedade($propriedadeMock);
        $entity->setCreatedAt($createdAt);

        $this->assertNull($entity->getId());
    }
}
