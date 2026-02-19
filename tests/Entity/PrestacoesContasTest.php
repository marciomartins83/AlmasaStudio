<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\PrestacoesContas;
use App\Entity\PrestacoesContasItens;
use App\Entity\Pessoas;
use App\Entity\Imoveis;
use App\Entity\ContasBancarias;
use App\Entity\Users;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;

class PrestacoesContasTest extends TestCase
{
    private function createMockPessoas(): Pessoas
    {
        return $this->createMock(Pessoas::class);
    }

    private function createMockImoveis(): Imoveis
    {
        return $this->createMock(Imoveis::class);
    }

    private function createMockContasBancarias(): ContasBancarias
    {
        return $this->createMock(ContasBancarias::class);
    }

    private function createMockUsers(): Users
    {
        return $this->createMock(Users::class);
    }

    private function createMockItem(
        bool $isReceita,
        bool $isDespesa,
        float $valorBruto,
        float $valorTaxaAdmin = 0.0,
        float $valorRetencaoIr = 0.0
    ): PrestacoesContasItens {
        $item = $this->createMock(PrestacoesContasItens::class);
        $item->method('isReceita')->willReturn($isReceita);
        $item->method('isDespesa')->willReturn($isDespesa);
        $item->method('getValorBrutoFloat')->willReturn($valorBruto);
        $item->method('getValorTaxaAdminFloat')->willReturn($valorTaxaAdmin);
        $item->method('getValorRetencaoIrFloat')->willReturn($valorRetencaoIr);
        // Expectation for setPrestacaoConta when item is added
        $item->expects($this->any())->method('setPrestacaoConta');
        return $item;
    }

    public function testGettersAndSetters(): void
    {
        $entity = new PrestacoesContas();

        // Integer fields
        $entity->setNumero(42);
        $this->assertSame(42, $entity->getNumero());

        $entity->setAno(2024);
        $this->assertSame(2024, $entity->getAno());

        // Date fields
        $inicio = new DateTimeImmutable('2024-01-01');
        $fim = new DateTimeImmutable('2024-01-31');
        $entity->setDataInicio($inicio);
        $entity->setDataFim($fim);
        $this->assertSame($inicio, $entity->getDataInicio());
        $this->assertSame($fim, $entity->getDataFim());

        // String fields
        $entity->setTipoPeriodo(PrestacoesContas::PERIODO_MENSAL);
        $this->assertSame(PrestacoesContas::PERIODO_MENSAL, $entity->getTipoPeriodo());

        $entity->setCompetencia('2024-01');
        $this->assertSame('2024-01', $entity->getCompetencia());

        // Relations
        $pessoa = $this->createMockPessoas();
        $entity->setProprietario($pessoa);
        $this->assertSame($pessoa, $entity->getProprietario());

        $imovel = $this->createMockImoveis();
        $entity->setImovel($imovel);
        $this->assertSame($imovel, $entity->getImovel());

        // Booleans
        $entity->setIncluirFichaFinanceira(false);
        $this->assertFalse($entity->isIncluirFichaFinanceira());

        $entity->setIncluirLancamentos(false);
        $this->assertFalse($entity->isIncluirLancamentos());

        // Totals
        $entity->setTotalReceitas('1234.56');
        $this->assertSame('1234.56', $entity->getTotalReceitas());
        $this->assertSame(1234.56, $entity->getTotalReceitasFloat());

        $entity->setTotalDespesas('200.00');
        $this->assertSame('200.00', $entity->getTotalDespesas());
        $this->assertSame(200.0, $entity->getTotalDespesasFloat());

        $entity->setTotalTaxaAdmin('50.00');
        $this->assertSame('50.00', $entity->getTotalTaxaAdmin());
        $this->assertSame(50.0, $entity->getTotalTaxaAdminFloat());

        $entity->setTotalRetencaoIr('30.00');
        $this->assertSame('30.00', $entity->getTotalRetencaoIr());
        $this->assertSame(30.0, $entity->getTotalRetencaoIrFloat());

        $entity->setValorRepasse('1000.00');
        $this->assertSame('1000.00', $entity->getValorRepasse());
        $this->assertSame(1000.0, $entity->getValorRepasseFloat());

        // Status
        $entity->setStatus(PrestacoesContas::STATUS_APROVADO);
        $this->assertSame(PrestacoesContas::STATUS_APROVADO, $entity->getStatus());

        // Dates
        $repasseDate = new DateTimeImmutable('2024-02-01');
        $entity->setDataRepasse($repasseDate);
        $this->assertSame($repasseDate, $entity->getDataRepasse());

        // Forma
        $entity->setFormaRepasse(PrestacoesContas::FORMA_PIX);
        $this->assertSame(PrestacoesContas::FORMA_PIX, $entity->getFormaRepasse());

        // Conta bancária
        $conta = $this->createMockContasBancarias();
        $entity->setContaBancaria($conta);
        $this->assertSame($conta, $entity->getContaBancaria());

        // Comprovante
        $entity->setComprovanteRepasse('comprovante.pdf');
        $this->assertSame('comprovante.pdf', $entity->getComprovanteRepasse());

        // Observações
        $entity->setObservacoes('Observação de teste');
        $this->assertSame('Observação de teste', $entity->getObservacoes());

        // Created/Updated
        $created = new DateTimeImmutable('2024-01-01 10:00:00');
        $updated = new DateTimeImmutable('2024-01-02 12:00:00');
        $entity->setCreatedAt($created);
        $entity->setUpdatedAt($updated);
        $this->assertSame($created, $entity->getCreatedAt());
        $this->assertSame($updated, $entity->getUpdatedAt());

        // CreatedBy
        $user = $this->createMockUsers();
        $entity->setCreatedBy($user);
        $this->assertSame($user, $entity->getCreatedBy());
    }

    public function testBusinessLogicMethods(): void
    {
        $entity = new PrestacoesContas();

        // Status label and badge
        $entity->setStatus(PrestacoesContas::STATUS_GERADO);
        $this->assertSame('Gerado', $entity->getStatusLabel());
        $this->assertSame('info', $entity->getStatusBadgeClass());

        $entity->setStatus(PrestacoesContas::STATUS_APROVADO);
        $this->assertSame('Aprovado', $entity->getStatusLabel());
        $this->assertSame('warning', $entity->getStatusBadgeClass());

        $entity->setStatus(PrestacoesContas::STATUS_PAGO);
        $this->assertSame('Pago', $entity->getStatusLabel());
        $this->assertSame('success', $entity->getStatusBadgeClass());

        $entity->setStatus(PrestacoesContas::STATUS_CANCELADO);
        $this->assertSame('Cancelado', $entity->getStatusLabel());
        $this->assertSame('danger', $entity->getStatusBadgeClass());

        // Unknown status
        $entity->setStatus('unknown');
        $this->assertSame('unknown', $entity->getStatusLabel());
        $this->assertSame('secondary', $entity->getStatusBadgeClass());

        // Tipo período label
        $entity->setTipoPeriodo(PrestacoesContas::PERIODO_SEMANAL);
        $this->assertSame('Semanal', $entity->getTipoPeriodoLabel());

        // Unknown tipo período
        $entity->setTipoPeriodo('unknown');
        $this->assertSame('unknown', $entity->getTipoPeriodoLabel());

        // Forma repasse label
        $entity->setFormaRepasse(PrestacoesContas::FORMA_TED);
        $this->assertSame('TED', $entity->getFormaRepasseLabel());

        // Unknown forma repasse
        $entity->setFormaRepasse('unknown');
        $this->assertSame('unknown', $entity->getFormaRepasseLabel());

        // Periodo formatado
        $inicio = new DateTimeImmutable('2024-01-01');
        $fim = new DateTimeImmutable('2024-01-31');
        $entity->setDataInicio($inicio);
        $entity->setDataFim($fim);
        $this->assertSame('01/01/2024 a 31/01/2024', $entity->getPeriodoFormatado());

        // Numero formatado
        $entity->setNumero(5);
        $entity->setAno(2024);
        $this->assertSame('005/2024', $entity->getNumeroFormatado());

        // Boolean defaults
        $entity->setIncluirFichaFinanceira(null);
        $this->assertTrue($entity->isIncluirFichaFinanceira());

        $entity->setIncluirLancamentos(null);
        $this->assertTrue($entity->isIncluirLancamentos());

        // Lifecycle callbacks
        $entity->onPrePersist();
        $this->assertInstanceOf(DateTimeInterface::class, $entity->getCreatedAt());
        $this->assertInstanceOf(DateTimeInterface::class, $entity->getUpdatedAt());

        $entity->onPreUpdate();
        $this->assertInstanceOf(DateTimeInterface::class, $entity->getUpdatedAt());
    }

    public function testRelationshipMethods(): void
    {
        $entity = new PrestacoesContas();

        $item1 = $this->createMockItem(true, false, 1000.0, 50.0, 100.0);
        $item2 = $this->createMockItem(false, true, 200.0);

        // Add items
        $entity->addItem($item1);
        $entity->addItem($item2);

        $itens = $entity->getItens();
        $this->assertCount(2, $itens);
        $this->assertContains($item1, $itens);
        $this->assertContains($item2, $itens);

        // Remove item
        $entity->removeItem($item1);
        $itens = $entity->getItens();
        $this->assertCount(1, $itens);
        $this->assertNotContains($item1, $itens);
        $this->assertContains($item2, $itens);
    }

    public function testRecalcularTotais(): void
    {
        $entity = new PrestacoesContas();

        // Receita item
        $receita = $this->createMockItem(
            true,
            false,
            1000.0,
            50.0,
            100.0
        );

        // Despesa item
        $despesa = $this->createMockItem(
            false,
            true,
            200.0
        );

        $entity->addItem($receita);
        $entity->addItem($despesa);

        $entity->recalcularTotais();

        $this->assertSame('1000.00', $entity->getTotalReceitas());
        $this->assertSame('200.00', $entity->getTotalDespesas());
        $this->assertSame('50.00', $entity->getTotalTaxaAdmin());
        $this->assertSame('100.00', $entity->getTotalRetencaoIr());
        $this->assertSame('650.00', $entity->getValorRepasse());
    }

    public function testPermissionMethods(): void
    {
        $entity = new PrestacoesContas();

        // Gerado
        $entity->setStatus(PrestacoesContas::STATUS_GERADO);
        $this->assertTrue($entity->podeEditar());
        $this->assertTrue($entity->podeExcluir());
        $this->assertTrue($entity->podeAprovar());
        $this->assertFalse($entity->podeRegistrarRepasse());
        $this->assertTrue($entity->podeCancelar());

        // Aprovado
        $entity->setStatus(PrestacoesContas::STATUS_APROVADO);
        $this->assertFalse($entity->podeEditar());
        $this->assertFalse($entity->podeExcluir());
        $this->assertFalse($entity->podeAprovar());
        $this->assertTrue($entity->podeRegistrarRepasse());
        $this->assertTrue($entity->podeCancelar());

        // Pago
        $entity->setStatus(PrestacoesContas::STATUS_PAGO);
        $this->assertFalse($entity->podeEditar());
        $this->assertFalse($entity->podeExcluir());
        $this->assertFalse($entity->podeAprovar());
        $this->assertFalse($entity->podeRegistrarRepasse());
        $this->assertFalse($entity->podeCancelar());
    }

    public function testGetReceitasAndDespesas(): void
    {
        $entity = new PrestacoesContas();

        $receita = $this->createMockItem(true, false, 500.0);
        $despesa = $this->createMockItem(false, true, 300.0);

        $entity->addItem($receita);
        $entity->addItem($despesa);

        $receitas = $entity->getReceitas();
        $despesas = $entity->getDespesas();

        $this->assertCount(1, $receitas);
        $this->assertCount(1, $despesas);
        $this->assertContains($receita, $receitas);
        $this->assertContains($despesa, $despesas);
    }
}
