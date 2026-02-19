<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\BaixasFinanceiras;
use App\Entity\ContasBancarias;
use App\Entity\Imoveis;
use App\Entity\ImoveisContratos;
use App\Entity\PlanoContas;
use App\Entity\Pessoas;
use App\Entity\LancamentosFinanceiros;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

final class LancamentosFinanceirosTest extends TestCase
{
    private LancamentosFinanceiros $entity;

    protected function setUp(): void
    {
        $this->entity = new LancamentosFinanceiros();
    }

    // --------------------------------------------------------------------
    //  Testes de construtor e valores padrão
    // --------------------------------------------------------------------
    public function testConstructorSetsDefaultValues(): void
    {
        $this->assertInstanceOf(\DateTimeInterface::class, $this->entity->getCreatedAt());
        $this->assertInstanceOf(\DateTimeInterface::class, $this->entity->getUpdatedAt());
        $this->assertInstanceOf(\DateTimeInterface::class, $this->entity->getCompetencia());
        $this->assertInstanceOf(\DateTimeInterface::class, $this->entity->getDataLancamento());
        $this->assertInstanceOf(\DateTimeInterface::class, $this->entity->getDataVencimento());

        $this->assertSame('0.00', $this->entity->getValorPrincipal());
        $this->assertSame('0.00', $this->entity->getValorCondominio());
        $this->assertSame('0.00', $this->entity->getValorIptu());
        $this->assertSame('0.00', $this->entity->getValorAgua());
        $this->assertSame('0.00', $this->entity->getValorLuz());
        $this->assertSame('0.00', $this->entity->getValorGas());
        $this->assertSame('0.00', $this->entity->getValorOutros());
        $this->assertSame('0.00', $this->entity->getValorMulta());
        $this->assertSame('0.00', $this->entity->getValorJuros());
        $this->assertSame('0.00', $this->entity->getValorHonorarios());
        $this->assertSame('0.00', $this->entity->getValorDesconto());
        $this->assertSame('0.00', $this->entity->getValorBonificacao());
        $this->assertSame('0.00', $this->entity->getValorTotal());
        $this->assertSame('0.00', $this->entity->getValorPago());
        $this->assertSame('0.00', $this->entity->getValorSaldo());

        $this->assertSame('aberto', $this->entity->getSituacao());
        $this->assertSame('aluguel', $this->entity->getTipoLancamento());
        $this->assertSame('contrato', $this->entity->getOrigem());

        $this->assertFalse($this->entity->isGeradoAutomaticamente());
        $this->assertTrue($this->entity->isAtivo());
        $this->assertFalse($this->entity->isEnviadoEmail());
        $this->assertFalse($this->entity->isImpresso());
    }

    // --------------------------------------------------------------------
    //  Testes de getters e setters
    // --------------------------------------------------------------------
    public function testGettersAndSetters(): void
    {
        // Relacionamentos
        $contrato = $this->createMock(ImoveisContratos::class);
        $imovel = $this->createMock(Imoveis::class);
        $inquilino = $this->createMock(Pessoas::class);
        $proprietario = $this->createMock(Pessoas::class);
        $conta = $this->createMock(PlanoContas::class);
        $contaBancaria = $this->createMock(ContasBancarias::class);

        $this->entity->setContrato($contrato);
        $this->entity->setImovel($imovel);
        $this->entity->setInquilino($inquilino);
        $this->entity->setProprietario($proprietario);
        $this->entity->setConta($conta);
        $this->entity->setContaBancaria($contaBancaria);

        $this->assertSame($contrato, $this->entity->getContrato());
        $this->assertSame($imovel, $this->entity->getImovel());
        $this->assertSame($inquilino, $this->entity->getInquilino());
        $this->assertSame($proprietario, $this->entity->getProprietario());
        $this->assertSame($conta, $this->entity->getConta());
        $this->assertSame($contaBancaria, $this->entity->getContaBancaria());

        // Identificação
        $this->entity->setNumeroAcordo(123);
        $this->entity->setNumeroParcela(2);
        $this->entity->setNumeroRecibo('REC-001');
        $this->entity->setNumeroBoleto('BOLETO-001');

        $this->assertSame(123, $this->entity->getNumeroAcordo());
        $this->assertSame(2, $this->entity->getNumeroParcela());
        $this->assertSame('REC-001', $this->entity->getNumeroRecibo());
        $this->assertSame('BOLETO-001', $this->entity->getNumeroBoleto());

        // Datas
        $competencia = new \DateTime('2024-01-01');
        $dataLancamento = new \DateTime('2024-01-05');
        $dataVencimento = new \DateTime('2024-01-10');
        $dataLimite = new \DateTime('2024-01-15');

        $this->entity->setCompetencia($competencia);
        $this->entity->setDataLancamento($dataLancamento);
        $this->entity->setDataVencimento($dataVencimento);
        $this->entity->setDataLimite($dataLimite);

        $this->assertSame($competencia, $this->entity->getCompetencia());
        $this->assertSame($dataLancamento, $this->entity->getDataLancamento());
        $this->assertSame($dataVencimento, $this->entity->getDataVencimento());
        $this->assertSame($dataLimite, $this->entity->getDataLimite());

        // Valores
        $this->entity->setValorPrincipal('100.00');
        $this->entity->setValorCondominio('10.00');
        $this->entity->setValorIptu('5.00');
        $this->entity->setValorAgua('2.00');
        $this->entity->setValorLuz('3.00');
        $this->entity->setValorGas('1.00');
        $this->entity->setValorOutros('0.50');
        $this->entity->setValorMulta('0.20');
        $this->entity->setValorJuros('0.10');
        $this->entity->setValorHonorarios('0.30');
        $this->entity->setValorDesconto('0.15');
        $this->entity->setValorBonificacao('0.05');

        $this->assertSame('100.00', $this->entity->getValorPrincipal());
        $this->assertSame('10.00', $this->entity->getValorCondominio());
        $this->assertSame('5.00', $this->entity->getValorIptu());
        $this->assertSame('2.00', $this->entity->getValorAgua());
        $this->assertSame('3.00', $this->entity->getValorLuz());
        $this->assertSame('1.00', $this->entity->getValorGas());
        $this->assertSame('0.50', $this->entity->getValorOutros());
        $this->assertSame('0.20', $this->entity->getValorMulta());
        $this->assertSame('0.10', $this->entity->getValorJuros());
        $this->assertSame('0.30', $this->entity->getValorHonorarios());
        $this->assertSame('0.15', $this->entity->getValorDesconto());
        $this->assertSame('0.05', $this->entity->getValorBonificacao());

        // Totais
        $this->entity->setValorTotal('200.00');
        $this->entity->setValorPago('50.00');
        $this->entity->setValorSaldo('150.00');

        $this->assertSame('200.00', $this->entity->getValorTotal());
        $this->assertSame('50.00', $this->entity->getValorPago());
        $this->assertSame('150.00', $this->entity->getValorSaldo());

        // Status e controle
        $this->entity->setSituacao('pago');
        $this->entity->setTipoLancamento('aluguel');
        $this->entity->setOrigem('contrato');

        $this->assertSame('pago', $this->entity->getSituacao());
        $this->assertSame('aluguel', $this->entity->getTipoLancamento());
        $this->assertSame('contrato', $this->entity->getOrigem());

        // Observações
        $this->entity->setDescricao('Descrição teste');
        $this->entity->setHistorico('Historico teste');
        $this->entity->setObservacoes('Observacoes teste');

        $this->assertSame('Descrição teste', $this->entity->getDescricao());
        $this->assertSame('Historico teste', $this->entity->getHistorico());
        $this->assertSame('Observacoes teste', $this->entity->getObservacoes());

        // Auditoria
        $this->entity->setCreatedBy(1);
        $this->entity->setUpdatedBy(2);

        $this->assertSame(1, $this->entity->getCreatedBy());
        $this->assertSame(2, $this->entity->getUpdatedBy());

        // Flags
        $this->entity->setGeradoAutomaticamente(false);
        $this->entity->setAtivo(false);
        $this->entity->setEnviadoEmail(true);
        $this->entity->setImpresso(true);

        $this->assertFalse($this->entity->isGeradoAutomaticamente());
        $this->assertFalse($this->entity->isAtivo());
        $this->assertTrue($this->entity->isEnviadoEmail());
        $this->assertTrue($this->entity->isImpresso());

        // Datas de geração/envio/impressão
        $dataGeracao = new \DateTime('2024-01-20');
        $dataEnvio = new \DateTime('2024-01-21');
        $dataImpressao = new \DateTime('2024-01-22');

        $this->entity->setDataGeracao($dataGeracao);
        $this->entity->setDataEnvioEmail($dataEnvio);
        $this->entity->setDataImpressao($dataImpressao);

        $this->assertSame($dataGeracao, $this->entity->getDataGeracao());
        $this->assertSame($dataEnvio, $this->entity->getDataEnvioEmail());
        $this->assertSame($dataImpressao, $this->entity->getDataImpressao());
    }

    // --------------------------------------------------------------------
    //  Testes de lógica de negócio
    // --------------------------------------------------------------------
    public function testCalcularTotalAndSaldo(): void
    {
        $this->entity->setValorPrincipal('100.00');
        $this->entity->setValorCondominio('10.00');
        $this->entity->setValorIptu('5.00');
        $this->entity->setValorAgua('2.00');
        $this->entity->setValorLuz('3.00');
        $this->entity->setValorGas('1.00');
        $this->entity->setValorOutros('0.50');
        $this->entity->setValorMulta('0.20');
        $this->entity->setValorJuros('0.10');
        $this->entity->setValorHonorarios('0.30');
        $this->entity->setValorDesconto('0.15');
        $this->entity->setValorBonificacao('0.05');

        $result = $this->entity->calcularTotal();

        // calcularTotal() returns self (fluent interface)
        $this->assertSame($result, $this->entity);

        // Total esperado = 100 + 10 + 5 + 2 + 3 + 1 + 0.5 + 0.2 + 0.1 + 0.3 - 0.15 - 0.05 = 121.90
        $this->assertSame('121.90', $this->entity->getValorTotal());

        // Saldo esperado = 121.90 - 0 = 121.90
        $this->assertSame('121.90', $this->entity->getValorSaldo());
    }

    public function testCalcularSaldoWithPago(): void
    {
        $this->entity->setValorTotal('200.00');
        $this->entity->setValorPago('50.00');

        $this->entity->calcularSaldo();

        // Saldo esperado = 200 - 50 = 150
        $this->assertSame('150.00', $this->entity->getValorSaldo());
    }

    public function testIsEmAtraso(): void
    {
        // Situação paga -> não em atraso
        $this->entity->setSituacao('pago');
        $this->entity->setDataVencimento(new \DateTime('-1 day'));
        $this->assertFalse($this->entity->isEmAtraso());

        // Situação aberta e vencimento passado
        $this->entity->setSituacao('aberto');
        $this->entity->setDataVencimento(new \DateTime('-1 day'));
        $this->assertTrue($this->entity->isEmAtraso());

        // Situação aberta e vencimento futuro
        $this->entity->setSituacao('aberto');
        $this->entity->setDataVencimento(new \DateTime('+1 day'));
        $this->assertFalse($this->entity->isEmAtraso());
    }

    public function testGetDiasAtraso(): void
    {
        $this->entity->setSituacao('aberto');
        $this->entity->setDataVencimento(new \DateTime('-5 days'));

        $this->assertSame(5, $this->entity->getDiasAtraso());

        // Se não em atraso, deve retornar 0
        $this->entity->setDataVencimento(new \DateTime('+1 day'));
        $this->assertSame(0, $this->entity->getDiasAtraso());
    }

    public function testIsPagoAndIsParcial(): void
    {
        // Situação paga
        $this->entity->setSituacao('pago');
        $this->entity->setValorSaldo('0.00');
        $this->assertTrue($this->entity->isPago());
        $this->assertFalse($this->entity->isParcial());

        // Situação aberta, saldo <= 0
        $this->entity->setSituacao('aberto');
        $this->entity->setValorSaldo('0.00');
        $this->assertTrue($this->entity->isPago());
        $this->assertFalse($this->entity->isParcial());

        // Situação aberta, saldo > 0, valorPago > 0
        $this->entity->setSituacao('aberto');
        $this->entity->setValorSaldo('50.00');
        $this->entity->setValorPago('10.00');
        $this->assertFalse($this->entity->isPago());
        $this->assertTrue($this->entity->isParcial());
    }

    public function testGetCompetenciaFormatada(): void
    {
        $competencia = new \DateTime('2024-07-01');
        $this->entity->setCompetencia($competencia);
        $this->assertSame('07/2024', $this->entity->getCompetenciaFormatada());
    }

    public function testPreUpdateUpdatesTimestamp(): void
    {
        $original = $this->entity->getUpdatedAt();
        // Simulate a change
        $this->entity->setValorPrincipal('200.00');
        $this->entity->preUpdate();
        $this->assertNotSame($original, $this->entity->getUpdatedAt());
    }

    // --------------------------------------------------------------------
    //  Testes de relacionamento
    // --------------------------------------------------------------------
    public function testBaixasRelationship(): void
    {
        /** @var BaixasFinanceiras|MockObject $baixaMock */
        $baixaMock = $this->createMock(BaixasFinanceiras::class);
        // Set expectations for bidirectional relationship
        $baixaMock->expects($this->atLeastOnce())
            ->method('setLancamento')
            ->withConsecutive(
                [$this->identicalTo($this->entity)],
                [$this->identicalTo(null)]
            );

        $this->entity->addBaixa($baixaMock);
        $this->assertCount(1, $this->entity->getBaixas());
        $this->assertSame($baixaMock, $this->entity->getBaixas()->first());

        // Remove
        $this->entity->removeBaixa($baixaMock);
        $this->assertCount(0, $this->entity->getBaixas());
    }

    public function testBaixasCollectionIsArrayCollection(): void
    {
        $this->assertInstanceOf(ArrayCollection::class, $this->entity->getBaixas());
    }
}
