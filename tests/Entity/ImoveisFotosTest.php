<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Imoveis;
use App\Entity\ImoveisFotos;
use PHPUnit\Framework\TestCase;

class ImoveisFotosTest extends TestCase
{
    public function testGettersAndSetters(): void
    {
        $entity = new ImoveisFotos();

        // Set values
        $arquivo   = 'foto.jpg';
        $caminho   = '/uploads/foto.jpg';
        $legenda   = 'Legenda da foto';
        $ordem     = 3;
        $capa      = true;
        $createdAt = new \DateTime('2023-01-01 12:00:00');

        // Mock Imovel
        $imovel = $this->createMock(Imoveis::class);

        // Test setters return $this
        $this->assertSame($entity, $entity->setArquivo($arquivo));
        $this->assertSame($entity, $entity->setCaminho($caminho));
        $this->assertSame($entity, $entity->setLegenda($legenda));
        $this->assertSame($entity, $entity->setOrdem($ordem));
        $this->assertSame($entity, $entity->setCapa($capa));
        $this->assertSame($entity, $entity->setImovel($imovel));
        $this->assertSame($entity, $entity->setCreatedAt($createdAt));

        // Test getters
        $this->assertSame($arquivo, $entity->getArquivo());
        $this->assertSame($caminho, $entity->getCaminho());
        $this->assertSame($legenda, $entity->getLegenda());
        $this->assertSame($ordem, $entity->getOrdem());
        $this->assertTrue($entity->isCapa());
        $this->assertSame($imovel, $entity->getImovel());
        $this->assertSame($createdAt, $entity->getCreatedAt());

        // Test isCapa
        $this->assertTrue($entity->isCapa());

        // Test getId returns null initially
        $this->assertNull($entity->getId());
    }

    public function testDefaultValues(): void
    {
        $entity = new ImoveisFotos();

        // Default values
        $this->assertSame(0, $entity->getOrdem());
        $this->assertFalse($entity->isCapa());
        $this->assertNull($entity->getLegenda());

        // createdAt should be set to current time
        $createdAt = $entity->getCreatedAt();
        $this->assertInstanceOf(\DateTimeInterface::class, $createdAt);
        $this->assertNotNull($createdAt);

        // Ensure createdAt is close to now (within 5 seconds)
        $now = new \DateTime();
        $diff = $now->getTimestamp() - $createdAt->getTimestamp();
        $this->assertLessThanOrEqual(5, $diff);
    }

    public function testImovelRelationship(): void
    {
        $entity = new ImoveisFotos();

        $imovel = $this->createMock(Imoveis::class);
        $entity->setImovel($imovel);

        $this->assertSame($imovel, $entity->getImovel());
    }

    public function testCreatedAtSetterAndGetter(): void
    {
        $entity = new ImoveisFotos();

        $newDate = new \DateTime('2024-05-15 08:30:00');
        $this->assertSame($entity, $entity->setCreatedAt($newDate));

        $this->assertSame($newDate, $entity->getCreatedAt());
    }

    public function testIsCapaMethod(): void
    {
        $entity = new ImoveisFotos();

        $entity->setCapa(true);
        $this->assertTrue($entity->isCapa());

        $entity->setCapa(false);
        $this->assertFalse($entity->isCapa());
    }
}
