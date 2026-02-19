<?php

namespace App\Tests\Entity;

use App\Entity\PessoasSocios;
use DateTime;
use DateTimeInterface;
use PHPUnit\Framework\TestCase;

class PessoasSociosTest extends TestCase
{
    public function testConstructorInitializesTimestamps(): void
    {
        $entity = new PessoasSocios();

        $createdAt = $entity->getCreatedAt();
        $updatedAt = $entity->getUpdatedAt();

        $this->assertInstanceOf(DateTimeInterface::class, $createdAt);
        $this->assertInstanceOf(DateTimeInterface::class, $updatedAt);
        $this->assertNotNull($createdAt);
        $this->assertNotNull($updatedAt);
        // They should be equal or very close at construction
        $this->assertEquals($createdAt->format('Y-m-d H:i:s'), $updatedAt->format('Y-m-d H:i:s'));
    }

    public function testGettersAndSetters(): void
    {
        $entity = new PessoasSocios();

        // idPessoa
        $entity->setIdPessoa(42);
        $this->assertSame(42, $entity->getIdPessoa());

        // percentualParticipacao
        $this->assertNull($entity->getPercentualParticipacao());
        $entity->setPercentualParticipacao('12.34');
        $this->assertSame('12.34', $entity->getPercentualParticipacao());
        $entity->setPercentualParticipacao(null);
        $this->assertNull($entity->getPercentualParticipacao());

        // dataEntrada
        $this->assertNull($entity->getDataEntrada());
        $date = new DateTime('2023-01-01');
        $entity->setDataEntrada($date);
        $this->assertSame($date, $entity->getDataEntrada());

        // tipoSocio
        $this->assertNull($entity->getTipoSocio());
        $entity->setTipoSocio('Sócio');
        $this->assertSame('Sócio', $entity->getTipoSocio());

        // observacoes
        $this->assertNull($entity->getObservacoes());
        $entity->setObservacoes('Observação de teste');
        $this->assertSame('Observação de teste', $entity->getObservacoes());

        // ativo
        $this->assertTrue($entity->isAtivo());
        $entity->setAtivo(false);
        $this->assertFalse($entity->isAtivo());

        // createdAt
        $newCreated = new DateTime('2022-12-31');
        $entity->setCreatedAt($newCreated);
        $this->assertSame($newCreated, $entity->getCreatedAt());

        // updatedAt
        $newUpdated = new DateTime('2022-12-30');
        $entity->setUpdatedAt($newUpdated);
        $this->assertSame($newUpdated, $entity->getUpdatedAt());

        // id (read‑only)
        $this->assertNull($entity->getId());
    }

    public function testPreUpdateUpdatesUpdatedAt(): void
    {
        $entity = new PessoasSocios();

        // Store original timestamps
        $originalCreated = $entity->getCreatedAt();
        $originalUpdated = $entity->getUpdatedAt();

        // Wait a bit to ensure a different timestamp
        sleep(1);

        // Invoke preUpdate
        $entity->preUpdate();

        $newUpdated = $entity->getUpdatedAt();

        // createdAt should remain unchanged
        $this->assertSame($originalCreated, $entity->getCreatedAt());

        // updatedAt should be newer
        $this->assertNotEquals($originalUpdated, $newUpdated);
        $this->assertGreaterThan($originalUpdated, $newUpdated);
    }

    public function testIsAtivoDefaultAndSetter(): void
    {
        $entity = new PessoasSocios();
        $this->assertTrue($entity->isAtivo());

        $entity->setAtivo(false);
        $this->assertFalse($entity->isAtivo());

        $entity->setAtivo(true);
        $this->assertTrue($entity->isAtivo());
    }

    public function testDateTimeInterfaceCompliance(): void
    {
        $entity = new PessoasSocios();

        $this->assertInstanceOf(DateTimeInterface::class, $entity->getCreatedAt());
        $this->assertInstanceOf(DateTimeInterface::class, $entity->getUpdatedAt());
    }
}
