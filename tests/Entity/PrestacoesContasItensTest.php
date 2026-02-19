<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\PrestacoesContasItens;
use App\Entity\PrestacoesContas;
use App\Entity\LancamentosFinanceiros;
use App\Entity\Lancamentos;
use App\Entity\PlanoContas;
use App\Entity\Imoveis;
use PHPUnit\Framework\TestCase;
use DateTime;

class PrestacoesContasItensTest extends TestCase
{
    private PrestacoesContasItens $item;

    protected function setUp(): void
    {
        $this->item = new PrestacoesContasItens();
    }

    public function testGettersAndSetters(): void
    {
        // Test basic properties
        $this->item->setOrigem(PrestacoesContasItens::ORIGEM_FICHA_FINANCEIRA);
        $this->assertSame(PrestacoesContasItens::ORIGEM_FICHA_FINANCEIRA, $this->item->getOrigem());

        $this->item->setTipo(PrestacoesContasItens::TIPO_RECEITA);
        $this->assertSame(PrestacoesContasItens::TIPO_RECEITA, $this->item->getTipo());

        $this->item->setHistorico('Test historico');
        $this->assertSame('Test historico', $this->item->getHistorico());

        $date = new DateTime('2023-01-01');
        $this->item->setDataMovimento($date);
        $this->assertSame($date, $this->item->getDataMovimento());

        $dateVenc = new DateTime('2023-02-01');
        $this->item->setDataVencimento($dateVenc);
        $this->assertSame($dateVenc, $this->item->getDataVencimento());

        $datePag = new DateTime('2023-03-01');
        $this->item->setDataPagamento($datePag);
        $this->assertSame($datePag, $this->item->getDataPagamento());

        $this->item->setValorBruto('1000.00');
        $this->assertSame('1000.00', $this->item->getValorBruto());

        $this->item->setValorTaxaAdmin('10.00');
        $this->assertSame('10.00', $this->item->getValorTaxaAdmin());

        $this->item->setValorRetencaoIr('5.00');
        $this->assertSame('5.00', $this->item->getValorRetencaoIr());

        $this->item->setValorLiquido('985.00');
        $this->assertSame('985.00', $this->item->getValorLiquido());

        // Test relationships
        $prestacaoMock = $this->createMock(PrestacoesContas::class);
        $this->item->setPrestacaoConta($prestacaoMock);
        $this->assertSame($prestacaoMock, $this->item->getPrestacaoConta());

        $lancFinMock = $this->createMock(LancamentosFinanceiros::class);
        $this->item->setLancamentoFinanceiro($lancFinMock);
        $this->assertSame($lancFinMock, $this->item->getLancamentoFinanceiro());

        $lancMock = $this->createMock(Lancamentos::class);
        $this->item->setLancamento($lancMock);
        $this->assertSame($lancMock, $this->item->getLancamento());

        $planoMock = $this->createMock(PlanoContas::class);
        $this->item->setPlanoConta($planoMock);
        $this->assertSame($planoMock, $this->item->getPlanoConta());

        $imovelMock = $this->createMock(Imoveis::class);
        $this->item->setImovel($imovelMock);
        $this->assertSame($imovelMock, $this->item->getImovel());

        // Test createdAt
        $created = $this->item->getCreatedAt();
        $this->assertInstanceOf(\DateTimeInterface::class, $created);
    }

    public function testBusinessLogicMethods(): void
    {
        // Test constants
        $this->assertSame('receita', PrestacoesContasItens::TIPO_RECEITA);
        $this->assertSame('despesa', PrestacoesContasItens::TIPO_DESPESA);
        $this->assertSame('ficha_financeira', PrestacoesContasItens::ORIGEM_FICHA_FINANCEIRA);
        $this->assertSame('lancamento_pagar', PrestacoesContasItens::ORIGEM_LANCAMENTO_PAGAR);
        $this->assertSame('lancamento_receber', PrestacoesContasItens::ORIGEM_LANCAMENTO_RECEBER);

        // Test type label and badge
        $this->item->setTipo(PrestacoesContasItens::TIPO_RECEITA);
        $this->assertSame('Receita', $this->item->getTipoLabel());
        $this->assertSame('success', $this->item->getTipoBadgeClass());

        $this->item->setTipo(PrestacoesContasItens::TIPO_DESPESA);
        $this->assertSame('Despesa', $this->item->getTipoLabel());
        $this->assertSame('danger', $this->item->getTipoBadgeClass());

        // Test origem label
        $this->item->setOrigem(PrestacoesContasItens::ORIGEM_FICHA_FINANCEIRA);
        $this->assertSame('Ficha Financeira', $this->item->getOrigemLabel());

        $this->item->setOrigem(PrestacoesContasItens::ORIGEM_LANCAMENTO_PAGAR);
        $this->assertSame('Conta a Pagar', $this->item->getOrigemLabel());

        $this->item->setOrigem(PrestacoesContasItens::ORIGEM_LANCAMENTO_RECEBER);
        $this->assertSame('Conta a Receber', $this->item->getOrigemLabel());

        // Test isReceita / isDespesa
        $this->item->setTipo(PrestacoesContasItens::TIPO_RECEITA);
        $this->assertTrue($this->item->isReceita());
        $this->assertFalse($this->item->isDespesa());

        $this->item->setTipo(PrestacoesContasItens::TIPO_DESPESA);
        $this->assertTrue($this->item->isDespesa());
        $this->assertFalse($this->item->isReceita());

        // Test float getters
        $this->item->setValorBruto('1234.56');
        $this->assertSame(1234.56, $this->item->getValorBrutoFloat());

        $this->item->setValorTaxaAdmin('12.34');
        $this->assertSame(12.34, $this->item->getValorTaxaAdminFloat());

        $this->item->setValorRetencaoIr('3.21');
        $this->assertSame(3.21, $this->item->getValorRetencaoIrFloat());

        $this->item->setValorLiquido('1111.11');
        $this->assertSame(1111.11, $this->item->getValorLiquidoFloat());

        // Test default values for null
        $this->item->setValorTaxaAdmin(null);
        $this->assertSame('0.00', $this->item->getValorTaxaAdmin());
        $this->assertSame(0.0, $this->item->getValorTaxaAdminFloat());

        $this->item->setValorRetencaoIr(null);
        $this->assertSame('0.00', $this->item->getValorRetencaoIr());
        $this->assertSame(0.0, $this->item->getValorRetencaoIrFloat());

        // Test calcularValorLiquido
        // Receita: Bruto - TaxaAdmin - RetencaoIr
        $this->item->setTipo(PrestacoesContasItens::TIPO_RECEITA);
        $this->item->setValorBruto('1000.00');
        $this->item->setValorTaxaAdmin('10.00');
        $this->item->setValorRetencaoIr('5.00');
        $this->item->calcularValorLiquido();
        $this->assertSame('985.00', $this->item->getValorLiquido());

        // Despesa: Bruto
        $this->item->setTipo(PrestacoesContasItens::TIPO_DESPESA);
        $this->item->setValorBruto('500.00');
        $this->item->calcularValorLiquido();
        $this->assertSame('500.00', $this->item->getValorLiquido());
    }

    public function testCreatedAtLifecycle(): void
    {
        $initial = $this->item->getCreatedAt();
        $this->assertInstanceOf(\DateTimeInterface::class, $initial);

        // Simulate persist
        sleep(1); // ensure time difference
        $this->item->onPrePersist();
        $after = $this->item->getCreatedAt();
        $this->assertInstanceOf(\DateTimeInterface::class, $after);
        $this->assertNotEquals($initial, $after);
    }
}
