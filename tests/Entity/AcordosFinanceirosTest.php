<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\AcordosFinanceiros;
use App\Entity\Pessoas;
use DateTime;
use DateTimeInterface;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class AcordosFinanceirosTest extends TestCase
{
    private AcordosFinanceiros $entity;
    private Pessoas $inquilinoMock;

    protected function setUp(): void
    {
        $this->entity = new AcordosFinanceiros();
        $this->inquilinoMock = $this->createMock(Pessoas::class);
    }

    // ---------- Testes de propriedades padrão ----------
    public function testDefaultValues(): void
    {
        // id deve ser null
        $this->assertNull($this->entity->getId());

        // createdAt e updatedAt devem ser instâncias de DateTimeInterface
        $this->assertInstanceOf(DateTimeInterface::class, $this->entity->getCreatedAt());
        $this->assertInstanceOf(DateTimeInterface::class, $this->entity->getUpdatedAt());

        // createdBy deve ser null
        $this->assertNull($this->entity->getCreatedBy());

        // observacoes deve ser null
        $this->assertNull($this->entity->getObservacoes());

        // diaVencimento padrão 10
        $this->assertSame(10, $this->entity->getDiaVencimento());

        // quantidadeParcelas padrão 1
        $this->assertSame(1, $this->entity->getQuantidadeParcelas());

        // valorDesconto padrão '0.00'
        $this->assertSame('0.00', $this->entity->getValorDesconto());

        // valorJuros padrão '0.00'
        $this->assertSame('0.00', $this->entity->getValorJuros());

        // situacao padrão 'ativo'
        $this->assertSame('ativo', $this->entity->getSituacao());

        // dataAcordo e dataPrimeiraParcela devem ser instâncias de DateTimeInterface
        $this->assertInstanceOf(DateTimeInterface::class, $this->entity->getDataAcordo());
        $this->assertInstanceOf(DateTimeInterface::class, $this->entity->getDataPrimeiraParcela());
    }

    // ---------- Testes de getters e setters ----------
    public function testSettersAndGetters(): void
    {
        $this->entity->setNumeroAcordo(123);
        $this->assertSame(123, $this->entity->getNumeroAcordo());

        $this->entity->setInquilino($this->inquilinoMock);
        $this->assertSame($this->inquilinoMock, $this->entity->getInquilino());

        $dataAcordo = new DateTime('2023-01-01');
        $this->entity->setDataAcordo($dataAcordo);
        $this->assertSame($dataAcordo, $this->entity->getDataAcordo());

        $dataPrimeiraParcela = new DateTime('2023-02-01');
        $this->entity->setDataPrimeiraParcela($dataPrimeiraParcela);
        $this->assertSame($dataPrimeiraParcela, $this->entity->getDataPrimeiraParcela());

        $this->entity->setValorDividaOriginal('2000.00');
        $this->assertSame('2000.00', $this->entity->getValorDividaOriginal());

        $this->entity->setValorDesconto('100.00');
        $this->assertSame('100.00', $this->entity->getValorDesconto());

        $this->entity->setValorJuros('50.00');
        $this->assertSame('50.00', $this->entity->getValorJuros());

        $this->entity->setValorTotalAcordo('1850.00');
        $this->assertSame('1850.00', $this->entity->getValorTotalAcordo());

        $this->entity->setQuantidadeParcelas(5);
        $this->assertSame(5, $this->entity->getQuantidadeParcelas());

        $this->entity->setValorParcela('370.00');
        $this->assertSame('370.00', $this->entity->getValorParcela());

        $this->entity->setDiaVencimento(15);
        $this->assertSame(15, $this->entity->getDiaVencimento());

        $this->entity->setSituacao('quitado');
        $this->assertSame('quitado', $this->entity->getSituacao());

        $this->entity->setObservacoes('Teste observação');
        $this->assertSame('Teste observação', $this->entity->getObservacoes());

        $this->entity->setCreatedBy(42);
        $this->assertSame(42, $this->entity->getCreatedBy());
    }

    // ---------- Testes de lógica de negócio ----------
    public function testCalcularTotal(): void
    {
        $this->entity->setValorDividaOriginal('1000.00');
        $this->entity->setValorDesconto('100.00');
        $this->entity->setValorJuros('50.00');

        $this->entity->calcularTotal();

        $this->assertSame('950.00', $this->entity->getValorTotalAcordo());
    }

    public function testCalcularParcela(): void
    {
        $this->entity->setValorTotalAcordo('950.00');
        $this->entity->setQuantidadeParcelas(5);

        $this->entity->calcularParcela();

        $this->assertSame('190.00', $this->entity->getValorParcela());
    }

    public function testIsAtivoAndIsQuitado(): void
    {
        $this->entity->setSituacao('ativo');
        $this->assertTrue($this->entity->isAtivo());
        $this->assertFalse($this->entity->isQuitado());

        $this->entity->setSituacao('quitado');
        $this->assertFalse($this->entity->isAtivo());
        $this->assertTrue($this->entity->isQuitado());

        $this->entity->setSituacao('inativo');
        $this->assertFalse($this->entity->isAtivo());
        $this->assertFalse($this->entity->isQuitado());
    }

    // ---------- Testes de callbacks ----------
    public function testPreUpdateUpdatesUpdatedAt(): void
    {
        // Definir updatedAt para um valor antigo usando reflexão
        $reflection = new ReflectionClass($this->entity);
        $updatedAtProperty = $reflection->getProperty('updatedAt');
        $updatedAtProperty->setAccessible(true);
        $oldDate = new DateTime('2020-01-01 00:00:00');
        $updatedAtProperty->setValue($this->entity, $oldDate);

        // Chamar preUpdate
        $this->entity->preUpdate();

        // Verificar que updatedAt foi alterado
        $newUpdatedAt = $this->entity->getUpdatedAt();
        $this->assertNotSame($oldDate, $newUpdatedAt);
        $this->assertInstanceOf(DateTimeInterface::class, $newUpdatedAt);
        $this->assertGreaterThan($oldDate, $newUpdatedAt);
    }

    // ---------- Testes de relacionamento ----------
    public function testInquilinoRelationship(): void
    {
        $this->entity->setInquilino($this->inquilinoMock);
        $this->assertSame($this->inquilinoMock, $this->entity->getInquilino());
    }
}
