<?php

namespace App\Tests\Entity;

use App\Entity\PessoasAdvogados;
use PHPUnit\Framework\TestCase;
use DateTimeImmutable;
use DateTimeInterface;

class PessoasAdvogadosTest extends TestCase
{
    private PessoasAdvogados $advogado;

    protected function setUp(): void
    {
        $this->advogado = new PessoasAdvogados();
    }

    public function testGettersAndSetters(): void
    {
        // idPessoa
        $this->advogado->setIdPessoa(123);
        $this->assertSame(123, $this->advogado->getIdPessoa());

        // numeroOab
        $this->advogado->setNumeroOab('AB123456');
        $this->assertSame('AB123456', $this->advogado->getNumeroOab());

        // seccionalOab
        $this->advogado->setSeccionalOab('SP');
        $this->assertSame('SP', $this->advogado->getSeccionalOab());

        // especialidade
        $this->advogado->setEspecialidade('Direito Civil');
        $this->assertSame('Direito Civil', $this->advogado->getEspecialidade());

        // observacoes
        $this->advogado->setObservacoes('Observação de teste');
        $this->assertSame('Observação de teste', $this->advogado->getObservacoes());

        // ativo
        $this->advogado->setAtivo(false);
        $this->assertFalse($this->advogado->isAtivo());
        $this->advogado->setAtivo(true);
        $this->assertTrue($this->advogado->isAtivo());

        // createdAt
        $created = new DateTimeImmutable('2023-01-01 10:00:00');
        $this->advogado->setCreatedAt($created);
        $this->assertSame($created, $this->advogado->getCreatedAt());

        // updatedAt
        $updated = new DateTimeImmutable('2023-01-02 12:00:00');
        $this->advogado->setUpdatedAt($updated);
        $this->assertSame($updated, $this->advogado->getUpdatedAt());
    }

    public function testDefaultValues(): void
    {
        // id is null by default
        $this->assertNull($this->advogado->getId());

        // ativo is true by default
        $this->assertTrue($this->advogado->isAtivo());

        // createdAt and updatedAt are instances of DateTimeInterface
        $this->assertInstanceOf(DateTimeInterface::class, $this->advogado->getCreatedAt());
        $this->assertInstanceOf(DateTimeInterface::class, $this->advogado->getUpdatedAt());
    }

    public function testEspecialidadeAndObservacoesNullable(): void
    {
        // set to null
        $this->advogado->setEspecialidade(null);
        $this->assertNull($this->advogado->getEspecialidade());

        $this->advogado->setObservacoes(null);
        $this->assertNull($this->advogado->getObservacoes());
    }

    public function testPreUpdateUpdatesUpdatedAt(): void
    {
        // Capture current updatedAt
        $originalUpdated = $this->advogado->getUpdatedAt();

        // Simulate a change that triggers preUpdate
        $this->advogado->preUpdate();

        // updatedAt should now be newer
        $newUpdated = $this->advogado->getUpdatedAt();
        $this->assertNotSame($originalUpdated, $newUpdated);
        $this->assertGreaterThan($originalUpdated, $newUpdated);
    }

    public function testCreatedAtRemainsUnchangedAfterPreUpdate(): void
    {
        $originalCreated = $this->advogado->getCreatedAt();
        $this->advogado->preUpdate();
        $this->assertSame($originalCreated, $this->advogado->getCreatedAt());
    }
}
