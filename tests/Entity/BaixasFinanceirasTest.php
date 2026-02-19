<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\BaixasFinanceiras;
use App\Entity\LancamentosFinanceiros;
use App\Entity\ContasBancarias;
use DateTime;
use DateTimeInterface;
use PHPUnit\Framework\TestCase;

class BaixasFinanceirasTest extends TestCase
{
    private BaixasFinanceiras $entity;

    protected function setUp(): void
    {
        $this->entity = new BaixasFinanceiras();
    }

    // ---------- Constructor ----------
    public function testConstructorSetsDefaults(): void
    {
        $this->assertInstanceOf(DateTimeInterface::class, $this->entity->getCreatedAt());
        $this->assertInstanceOf(DateTimeInterface::class, $this->entity->getDataPagamento());
        $this->assertNull($this->entity->getDataEstorno());
        $this->assertNull($this->entity->getMotivoEstorno());
        $this->assertFalse($this->entity->isEstornada());
        $this->assertSame('boleto', $this->entity->getFormaPagamento());
        $this->assertSame('normal', $this->entity->getTipoBaixa());
        $this->assertNull($this->entity->getNumeroDocumento());
        $this->assertNull($this->entity->getNumeroAutenticacao());
        $this->assertNull($this->entity->getObservacoes());
        $this->assertNull($this->entity->getLancamento());
        $this->assertNull($this->entity->getContaBancaria());
        $this->assertSame('0.00', $this->entity->getValorPago());
        $this->assertSame('0.00', $this->entity->getValorMultaPaga());
        $this->assertSame('0.00', $this->entity->getValorJurosPago());
        $this->assertSame('0.00', $this->entity->getValorDesconto());
        $this->assertSame('0.00', $this->entity->getValorTotalPago());
        $this->assertNull($this->entity->getCreatedBy());
    }

    // ---------- Getters & Setters ----------
    public function testGettersAndSetters(): void
    {
        // Id
        $this->assertNull($this->entity->getId());

        // Lancamento
        $lancamento = $this->createMock(LancamentosFinanceiros::class);
        $this->assertSame($this->entity, $this->entity->setLancamento($lancamento));
        $this->assertSame($lancamento, $this->entity->getLancamento());

        // Conta Bancária
        $conta = $this->createMock(ContasBancarias::class);
        $this->assertSame($this->entity, $this->entity->setContaBancaria($conta));
        $this->assertSame($conta, $this->entity->getContaBancaria());

        // Data Pagamento
        $data = new DateTime('2025-01-01 10:00:00');
        $this->assertSame($this->entity, $this->entity->setDataPagamento($data));
        $this->assertSame($data, $this->entity->getDataPagamento());

        // Valor Pago
        $this->assertSame($this->entity, $this->entity->setValorPago('150.50'));
        $this->assertSame('150.50', $this->entity->getValorPago());

        // Valor Multa Paga
        $this->assertSame($this->entity, $this->entity->setValorMultaPaga('5.00'));
        $this->assertSame('5.00', $this->entity->getValorMultaPaga());

        // Valor Juros Pago
        $this->assertSame($this->entity, $this->entity->setValorJurosPago('2.00'));
        $this->assertSame('2.00', $this->entity->getValorJurosPago());

        // Valor Desconto
        $this->assertSame($this->entity, $this->entity->setValorDesconto('1.00'));
        $this->assertSame('1.00', $this->entity->getValorDesconto());

        // Valor Total Pago
        $this->assertSame($this->entity, $this->entity->setValorTotalPago('158.50'));
        $this->assertSame('158.50', $this->entity->getValorTotalPago());

        // Forma Pagamento
        $this->assertSame($this->entity, $this->entity->setFormaPagamento('pix'));
        $this->assertSame('pix', $this->entity->getFormaPagamento());

        // Numero Documento
        $this->assertSame($this->entity, $this->entity->setNumeroDocumento('12345'));
        $this->assertSame('12345', $this->entity->getNumeroDocumento());

        // Numero Autenticacao
        $this->assertSame($this->entity, $this->entity->setNumeroAutenticacao('auth123'));
        $this->assertSame('auth123', $this->entity->getNumeroAutenticacao());

        // Tipo Baixa
        $this->assertSame($this->entity, $this->entity->setTipoBaixa('extra'));
        $this->assertSame('extra', $this->entity->getTipoBaixa());

        // Observacoes
        $this->assertSame($this->entity, $this->entity->setObservacoes('Test Observação'));
        $this->assertSame('Test Observação', $this->entity->getObservacoes());

        // Estornada
        $this->assertSame($this->entity, $this->entity->setEstornada(true));
        $this->assertTrue($this->entity->isEstornada());

        // Data Estorno
        $dataEstorno = new DateTime('2025-02-01 12:00:00');
        $this->assertSame($this->entity, $this->entity->setDataEstorno($dataEstorno));
        $this->assertSame($dataEstorno, $this->entity->getDataEstorno());

        // Motivo Estorno
        $this->assertSame($this->entity, $this->entity->setMotivoEstorno('Motivo de teste'));
        $this->assertSame('Motivo de teste', $this->entity->getMotivoEstorno());

        // Created By
        $this->assertSame($this->entity, $this->entity->setCreatedBy(42));
        $this->assertSame(42, $this->entity->getCreatedBy());
    }

    // ---------- Business Logic ----------
    public function testCalcularTotalWithAllValues(): void
    {
        $this->entity->setValorPago('100.00')
            ->setValorMultaPaga('10.00')
            ->setValorJurosPago('5.00')
            ->setValorDesconto('2.00');

        $this->assertSame($this->entity, $this->entity->calcularTotal());
        $this->assertSame('113.00', $this->entity->getValorTotalPago());
    }

    public function testCalcularTotalWithNullValues(): void
    {
        $this->entity->setValorPago('100.00')
            ->setValorMultaPaga(null)
            ->setValorJurosPago(null)
            ->setValorDesconto(null);

        $this->assertSame($this->entity, $this->entity->calcularTotal());
        $this->assertSame('100.00', $this->entity->getValorTotalPago());
    }

    public function testCalcularTotalWithNegativeValues(): void
    {
        $this->entity->setValorPago('-50.00')
            ->setValorMultaPaga('-5.00')
            ->setValorJurosPago('-2.00')
            ->setValorDesconto('-3.00');

        $this->assertSame($this->entity, $this->entity->calcularTotal());
        $this->assertSame('-54.00', $this->entity->getValorTotalPago());
    }

    // ---------- Relationship Tests ----------
    public function testLancamentoRelationship(): void
    {
        $lancamento = $this->createMock(LancamentosFinanceiros::class);
        $this->entity->setLancamento($lancamento);
        $this->assertSame($lancamento, $this->entity->getLancamento());
    }

    public function testContaBancariaRelationship(): void
    {
        $conta = $this->createMock(ContasBancarias::class);
        $this->entity->setContaBancaria($conta);
        $this->assertSame($conta, $this->entity->getContaBancaria());
    }

    // ---------- Miscellaneous ----------
    public function testCreatedAtAndDataPagamentoInstances(): void
    {
        $this->assertInstanceOf(DateTimeInterface::class, $this->entity->getCreatedAt());
        $this->assertInstanceOf(DateTimeInterface::class, $this->entity->getDataPagamento());
    }

    public function testDataEstornoAndMotivoEstornoDefaults(): void
    {
        $this->assertNull($this->entity->getDataEstorno());
        $this->assertNull($this->entity->getMotivoEstorno());
    }

    public function testEstornadaDefaultAndSetter(): void
    {
        $this->assertFalse($this->entity->isEstornada());
        $this->entity->setEstornada(true);
        $this->assertTrue($this->entity->isEstornada());
    }

    public function testFormaPagamentoDefaultAndSetter(): void
    {
        $this->assertSame('boleto', $this->entity->getFormaPagamento());
        $this->entity->setFormaPagamento('pix');
        $this->assertSame('pix', $this->entity->getFormaPagamento());
    }

    public function testTipoBaixaDefaultAndSetter(): void
    {
        $this->assertSame('normal', $this->entity->getTipoBaixa());
        $this->entity->setTipoBaixa('extra');
        $this->assertSame('extra', $this->entity->getTipoBaixa());
    }

    public function testNumeroDocumentoDefaultAndSetter(): void
    {
        $this->assertNull($this->entity->getNumeroDocumento());
        $this->entity->setNumeroDocumento('12345');
        $this->assertSame('12345', $this->entity->getNumeroDocumento());
    }

    public function testNumeroAutenticacaoDefaultAndSetter(): void
    {
        $this->assertNull($this->entity->getNumeroAutenticacao());
        $this->entity->setNumeroAutenticacao('auth123');
        $this->assertSame('auth123', $this->entity->getNumeroAutenticacao());
    }

    public function testObservacoesDefaultAndSetter(): void
    {
        $this->assertNull($this->entity->getObservacoes());
        $this->entity->setObservacoes('Test Observação');
        $this->assertSame('Test Observação', $this->entity->getObservacoes());
    }

    public function testCreatedByDefaultAndSetter(): void
    {
        $this->assertNull($this->entity->getCreatedBy());
        $this->entity->setCreatedBy(42);
        $this->assertSame(42, $this->entity->getCreatedBy());
    }

    public function testDataEstornoDefaultAndSetter(): void
    {
        $this->assertNull($this->entity->getDataEstorno());
        $dataEstorno = new DateTime('2025-02-01 12:00:00');
        $this->entity->setDataEstorno($dataEstorno);
        $this->assertSame($dataEstorno, $this->entity->getDataEstorno());
    }

    public function testMotivoEstornoDefaultAndSetter(): void
    {
        $this->assertNull($this->entity->getMotivoEstorno());
        $this->entity->setMotivoEstorno('Motivo de teste');
        $this->assertSame('Motivo de teste', $this->entity->getMotivoEstorno());
    }
}
