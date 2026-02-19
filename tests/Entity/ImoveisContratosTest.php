<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\ImoveisContratos;
use App\Entity\Imoveis;
use App\Entity\Pessoas;
use App\Entity\ContratosItensCobranca;
use App\Entity\ContratosCobrancas;
use PHPUnit\Framework\TestCase;
use Doctrine\Common\Collections\ArrayCollection;
use DateTime;

class ImoveisContratosTest extends TestCase
{
    private ImoveisContratos $contrato;

    protected function setUp(): void
    {
        $this->contrato = new ImoveisContratos();
    }

    public function testGettersAndSetters(): void
    {
        // Imovel
        $imovelMock = $this->createMock(Imoveis::class);
        $this->contrato->setImovel($imovelMock);
        $this->assertSame($imovelMock, $this->contrato->getImovel());

        // Pessoa Locatario
        $pessoaLocatarioMock = $this->createMock(Pessoas::class);
        $this->contrato->setPessoaLocatario($pessoaLocatarioMock);
        $this->assertSame($pessoaLocatarioMock, $this->contrato->getPessoaLocatario());

        // Pessoa Fiador
        $pessoaFiadorMock = $this->createMock(Pessoas::class);
        $this->contrato->setPessoaFiador($pessoaFiadorMock);
        $this->assertSame($pessoaFiadorMock, $this->contrato->getPessoaFiador());

        // Tipo Contrato
        $this->contrato->setTipoContrato('residencial');
        $this->assertSame('residencial', $this->contrato->getTipoContrato());

        // Data Inicio
        $dataInicio = new DateTime('2023-01-01');
        $this->contrato->setDataInicio($dataInicio);
        $this->assertSame($dataInicio, $this->contrato->getDataInicio());

        // Data Fim
        $dataFim = new DateTime('2024-01-01');
        $this->contrato->setDataFim($dataFim);
        $this->assertSame($dataFim, $this->contrato->getDataFim());

        // Valor Contrato
        $this->contrato->setValorContrato('1200.50');
        $this->assertSame('1200.50', $this->contrato->getValorContrato());

        // Dia Vencimento
        $this->contrato->setDiaVencimento(15);
        $this->assertSame(15, $this->contrato->getDiaVencimento());

        // Status
        $this->contrato->setStatus('ativo');
        $this->assertSame('ativo', $this->contrato->getStatus());

        // Observacoes
        $this->contrato->setObservacoes('Teste');
        $this->assertSame('Teste', $this->contrato->getObservacoes());

        // Taxa Administracao
        $this->contrato->setTaxaAdministracao('8.00');
        $this->assertSame('8.00', $this->contrato->getTaxaAdministracao());

        // Tipo Garantia
        $this->contrato->setTipoGarantia('caucao');
        $this->assertSame('caucao', $this->contrato->getTipoGarantia());

        // Valor Caucao
        $this->contrato->setValorCaucao('500.00');
        $this->assertSame('500.00', $this->contrato->getValorCaucao());

        // Indice Reajuste
        $this->contrato->setIndiceReajuste('IPCA');
        $this->assertSame('IPCA', $this->contrato->getIndiceReajuste());

        // Periodicidade Reajuste
        $this->contrato->setPeriodicidadeReajuste('mensal');
        $this->assertSame('mensal', $this->contrato->getPeriodicidadeReajuste());

        // Data Proximo Reajuste
        $dataProx = new DateTime('2023-06-01');
        $this->contrato->setDataProximoReajuste($dataProx);
        $this->assertSame($dataProx, $this->contrato->getDataProximoReajuste());

        // Multa Rescisao
        $this->contrato->setMultaRescisao('100.00');
        $this->assertSame('100.00', $this->contrato->getMultaRescisao());

        // Carencia Dias
        $this->contrato->setCarenciaDias(30);
        $this->assertSame(30, $this->contrato->getCarenciaDias());

        // Gera Boleto
        $this->contrato->setGeraBoleto(false);
        $this->assertFalse($this->contrato->isGeraBoleto());

        // Envia Email
        $this->contrato->setEnviaEmail(false);
        $this->assertFalse($this->contrato->isEnviaEmail());

        // Ativo
        $this->contrato->setAtivo(false);
        $this->assertFalse($this->contrato->isAtivo());

        // Dias Antecedencia Boleto
        $this->contrato->setDiasAntecedenciaBoleto(10);
        $this->assertSame(10, $this->contrato->getDiasAntecedenciaBoleto());

        // CreatedAt
        $created = new DateTime('2023-01-01 10:00:00');
        $this->contrato->setCreatedAt($created);
        $this->assertSame($created, $this->contrato->getCreatedAt());

        // UpdatedAt
        $updated = new DateTime('2023-01-02 12:00:00');
        $this->contrato->setUpdatedAt($updated);
        $this->assertSame($updated, $this->contrato->getUpdatedAt());
    }

    public function testBusinessLogicMethods(): void
    {
        // Duracao Meses
        $inicio = new DateTime('2023-01-01');
        $fim = new DateTime('2024-01-01');
        $this->contrato->setDataInicio($inicio);
        $this->contrato->setDataFim($fim);
        $this->assertSame(12, $this->contrato->getDuracaoMeses());

        // Is Vigente
        $this->contrato->setStatus('ativo');
        $this->contrato->setDataInicio(new DateTime('2020-01-01'));
        $this->contrato->setDataFim(null);
        $this->assertTrue($this->contrato->isVigente());

        // Valor Liquido Proprietario
        $this->contrato->setValorContrato('1000.00');
        $this->contrato->setTaxaAdministracao('10.00');
        $this->assertSame(900.0, $this->contrato->getValorLiquidoProprietario());

        // Is Envio Automatico Ativo
        $this->contrato->setAtivo(true);
        $this->contrato->setGeraBoleto(true);
        $this->contrato->setEnviaEmail(true);
        $this->contrato->setStatus('ativo');
        $this->assertTrue($this->contrato->isEnvioAutomaticoAtivo());

        // Calcular Valor Cobranca Mensal
        $item1 = $this->createMock(ContratosItensCobranca::class);
        $item1->method('isAtivo')->willReturn(true);
        $item1->method('calcularValorEfetivo')->with(1000.0)->willReturn(200.0);

        $item2 = $this->createMock(ContratosItensCobranca::class);
        $item2->method('isAtivo')->willReturn(true);
        $item2->method('calcularValorEfetivo')->with(1000.0)->willReturn(300.0);

        $this->contrato->addItemCobranca($item1);
        $this->contrato->addItemCobranca($item2);

        $this->assertSame(500.0, $this->contrato->calcularValorCobrancaMensal());
    }

    public function testRelationships(): void
    {
        // Itens de Cobrança
        $item = $this->createMock(ContratosItensCobranca::class);
        $item->method('isAtivo')->willReturn(true);
        $item->method('calcularValorEfetivo')->willReturn(0.0);
        $item->method('setContrato')->willReturnSelf();

        $this->contrato->addItemCobranca($item);
        $this->assertCount(1, $this->contrato->getItensCobranca());
        $this->assertSame($item, $this->contrato->getItensCobranca()->first());

        $this->contrato->removeItemCobranca($item);
        $this->assertCount(0, $this->contrato->getItensCobranca());

        // Cobrancas
        $cobranca = $this->createMock(ContratosCobrancas::class);
        $cobranca->method('setContrato')->willReturnSelf();
        $cobranca->method('getCompetencia')->willReturn('2024-01');

        $this->contrato->addCobranca($cobranca);
        $this->assertCount(1, $this->contrato->getCobrancas());
        $this->assertSame($cobranca, $this->contrato->getCobrancas()->first());

        $this->assertSame($cobranca, $this->contrato->getCobrancaPorCompetencia('2024-01'));
        $this->assertNull($this->contrato->getCobrancaPorCompetencia('2025-01'));

        $this->contrato->removeCobranca($cobranca);
        $this->assertCount(0, $this->contrato->getCobrancas());
    }

    public function testPreUpdate(): void
    {
        $original = $this->contrato->getUpdatedAt();
        $this->contrato->preUpdate();
        $this->assertNotSame($original, $this->contrato->getUpdatedAt());
        $this->assertInstanceOf(DateTime::class, $this->contrato->getUpdatedAt());
    }

    public function testCreatedUpdatedAt(): void
    {
        $this->assertInstanceOf(DateTime::class, $this->contrato->getCreatedAt());
        $this->assertInstanceOf(DateTime::class, $this->contrato->getUpdatedAt());
    }
}
