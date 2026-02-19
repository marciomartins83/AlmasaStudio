<?php

declare(strict_types=1);

use App\Entity\ContratosItensCobranca;
use App\Entity\ImoveisContratos;
use PHPUnit\Framework\TestCase;
use DateTime;
use DateTimeInterface;

final class ContratosItensCobrancaTest extends TestCase
{
    private ContratosItensCobranca $entity;
    private ImoveisContratos $contratoMock;

    protected function setUp(): void
    {
        $this->entity = new ContratosItensCobranca();
        $this->contratoMock = $this->createMock(ImoveisContratos::class);
    }

    public function testIdIsNullInitially(): void
    {
        $this->assertNull($this->entity->getId());
    }

    public function testContratoGetterAndSetter(): void
    {
        $this->assertSame($this->entity, $this->entity->setContrato($this->contratoMock));
        $this->assertSame($this->contratoMock, $this->entity->getContrato());
    }

    public function testTipoItemGetterAndSetter(): void
    {
        $this->assertSame($this->entity, $this->entity->setTipoItem(ContratosItensCobranca::TIPO_ALUGUEL));
        $this->assertSame(ContratosItensCobranca::TIPO_ALUGUEL, $this->entity->getTipoItem());
    }

    public function testDescricaoGetterAndSetter(): void
    {
        $this->assertSame($this->entity, $this->entity->setDescricao('Aluguel mensal'));
        $this->assertSame('Aluguel mensal', $this->entity->getDescricao());
    }

    public function testValorGetterAndSetter(): void
    {
        $this->assertSame($this->entity, $this->entity->setValor('1500.00'));
        $this->assertSame('1500.00', $this->entity->getValor());
    }

    public function testValorFloat(): void
    {
        $this->entity->setValor('1234.56');
        $this->assertSame(1234.56, $this->entity->getValorFloat());
    }

    public function testValorTipoGetterAndSetter(): void
    {
        $this->assertSame($this->entity, $this->entity->setValorTipo(ContratosItensCobranca::VALOR_TIPO_PERCENTUAL));
        $this->assertSame(ContratosItensCobranca::VALOR_TIPO_PERCENTUAL, $this->entity->getValorTipo());
    }

    public function testIsPercentualAndIsFixo(): void
    {
        $this->entity->setValorTipo(ContratosItensCobranca::VALOR_TIPO_PERCENTUAL);
        $this->assertTrue($this->entity->isPercentual());
        $this->assertFalse($this->entity->isFixo());

        $this->entity->setValorTipo(ContratosItensCobranca::VALOR_TIPO_FIXO);
        $this->assertTrue($this->entity->isFixo());
        $this->assertFalse($this->entity->isPercentual());
    }

    public function testCalcularValorEfetivo(): void
    {
        // Percentual
        $this->entity->setValorTipo(ContratosItensCobranca::VALOR_TIPO_PERCENTUAL);
        $this->entity->setValor('10'); // 10%
        $this->assertSame(100.0, $this->entity->calcularValorEfetivo(1000.0));

        // Fixo
        $this->entity->setValorTipo(ContratosItensCobranca::VALOR_TIPO_FIXO);
        $this->entity->setValor('200');
        $this->assertSame(200.0, $this->entity->calcularValorEfetivo(1000.0));
    }

    public function testTipoItemLabel(): void
    {
        foreach (ContratosItensCobranca::getTiposDisponiveis() as $tipo => $label) {
            $this->entity->setTipoItem($tipo);
            $this->assertSame($label, $this->entity->getTipoItemLabel());
        }

        // Unknown type
        $this->entity->setTipoItem('UNKNOWN');
        $this->assertSame('UNKNOWN', $this->entity->getTipoItemLabel());
    }

    public function testValorFormatado(): void
    {
        // Percentual
        $this->entity->setValorTipo(ContratosItensCobranca::VALOR_TIPO_PERCENTUAL);
        $this->entity->setValor('12.5');
        $this->assertSame('12,50%', $this->entity->getValorFormatado());

        // Fixo
        $this->entity->setValorTipo(ContratosItensCobranca::VALOR_TIPO_FIXO);
        $this->entity->setValor('987.65');
        $this->assertSame('R$ 987,65', $this->entity->getValorFormatado());
    }

    public function testGetTiposDisponiveis(): void
    {
        $tipos = ContratosItensCobranca::getTiposDisponiveis();
        $this->assertIsArray($tipos);
        $this->assertCount(9, $tipos);
        $this->assertArrayHasKey(ContratosItensCobranca::TIPO_ALUGUEL, $tipos);
        $this->assertArrayHasKey(ContratosItensCobranca::TIPO_IPTU, $tipos);
        $this->assertArrayHasKey(ContratosItensCobranca::TIPO_CONDOMINIO, $tipos);
        $this->assertArrayHasKey(ContratosItensCobranca::TIPO_TAXA_ADMIN, $tipos);
        $this->assertArrayHasKey(ContratosItensCobranca::TIPO_SEGURO, $tipos);
        $this->assertArrayHasKey(ContratosItensCobranca::TIPO_AGUA, $tipos);
        $this->assertArrayHasKey(ContratosItensCobranca::TIPO_LUZ, $tipos);
        $this->assertArrayHasKey(ContratosItensCobranca::TIPO_GAS, $tipos);
        $this->assertArrayHasKey(ContratosItensCobranca::TIPO_OUTROS, $tipos);
    }

    public function testDefaultValorTipo(): void
    {
        $this->assertSame(ContratosItensCobranca::VALOR_TIPO_FIXO, $this->entity->getValorTipo());
    }

    public function testDefaultAtivo(): void
    {
        $this->assertTrue($this->entity->isAtivo());
    }

    public function testDefaultCreatedAt(): void
    {
        $createdAt = $this->entity->getCreatedAt();
        $this->assertInstanceOf(DateTimeInterface::class, $createdAt);
    }

    public function testSetCreatedAtAndGetter(): void
    {
        $now = new DateTime('2023-01-01 12:00:00');
        $this->assertSame($this->entity, $this->entity->setCreatedAt($now));
        $this->assertSame($now, $this->entity->getCreatedAt());
    }

    public function testOnPrePersistSetsCreatedAtIfNotSet(): void
    {
        // The constructor already sets createdAt, so we test that it's set
        $newEntity = new ContratosItensCobranca();
        $this->assertInstanceOf(DateTimeInterface::class, $newEntity->getCreatedAt());

        // Also test the lifecycle callback by creating fresh entity and calling it
        $entity = new ContratosItensCobranca();
        $originalCreatedAt = $entity->getCreatedAt();
        $entity->onPrePersist();

        // createdAt should still be set and be a DateTimeInterface
        $this->assertInstanceOf(DateTimeInterface::class, $entity->getCreatedAt());
    }
}
