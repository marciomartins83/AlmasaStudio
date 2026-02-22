<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\BaixasFinanceiras;
use App\Entity\ContasBancarias;
use App\Entity\Imoveis;
use App\Entity\ImoveisContratos;
use App\Entity\LancamentosFinanceiros;
use App\Entity\Pessoas;
use App\Entity\PlanoContas;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use DateTime;

final class LancamentosFinanceirosTest extends TestCase
{
    private LancamentosFinanceiros $entity;

    protected function setUp(): void
    {
        $this->entity = new LancamentosFinanceiros();
    }

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
        $competencia = new DateTime('2023-05-01');
        $dataLancamento = new DateTime('2023-05-02');
        $dataVencimento = new DateTime('2023-05-15');
        $dataLimite = new DateTime('2023-05-10');

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
        $this->entity->setValorGas('4.00');
        $this->entity->setValorOutros('1.00');
        $this->entity->setValorMulta('0.50');
        $this->entity->setValorJuros('0.20');
        $this->entity->setValorHonorarios('0.30');
        $this->entity->setValorDesconto('1.00');
        $this->entity->setValorBonificacao('0.50');
        $this->entity->setValorTotal('124.50');
        $this->entity->setValorPago('20.00');
        $this->entity->setValorSaldo('104.50');

        $this->assertSame('100.00', $this->entity->getValorPrincipal());
        $this->assertSame('10.00', $this->entity->getValorCondominio());
        $this->assertSame('5.00', $this->entity->getValorIptu());
        $this->assertSame('2.00', $this->entity->getValorAgua());
        $this->assertSame('3.00', $this->entity->getValorLuz());
        $this->assertSame('4.00', $this->entity->getValorGas());
        $this->assertSame('1.00', $this->entity->getValorOutros());
        $this->assertSame('0.50', $this->entity->getValorMulta());
        $this->assertSame('0.20', $this->entity->getValorJuros());
        $this->assertSame('0.30', $this->entity->getValorHonorarios());
        $this->assertSame('1.00', $this->entity->getValorDesconto());
        $this->assertSame('0.50', $this->entity->getValorBonificacao());
        $this->assertSame('124.50', $this->entity->getValorTotal());
        $this->assertSame('20.00', $this->entity->getValorPago());
        $this->assertSame('104.50', $this->entity->getValorSaldo());

        // Status
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

        // Flags
        $this->entity->setGeradoAutomaticamente(true);
        $this->entity->setAtivo(false);
        $this->entity->setEnviadoEmail(true);
        $this->entity->setImpresso(false);

        $this->assertTrue($this->entity->isGeradoAutomaticamente());
        $this->assertFalse($this->entity->isAtivo());
        $this->assertTrue($this->entity->isEnviadoEmail());
        $this->assertFalse($this->entity->isImpresso());

        // Datas de auditoria
        $dataGeracao = new DateTime('2023-05-20');
        $dataEnvioEmail = new DateTime('2023-05-21');
        $dataImpressao = new DateTime('2023-05-22');

        $this->entity->setDataGeracao($dataGeracao);
        $this->entity->setDataEnvioEmail($dataEnvioEmail);
        $this->entity->setDataImpressao($dataImpressao);

        $this->assertSame($dataGeracao, $this->entity->getDataGeracao());
        $this->assertSame($dataEnvioEmail, $this->entity->getDataEnvioEmail());
        $this->assertSame($dataImpressao, $this->entity->getDataImpressao());

        // Usuário
        $this->entity->setCreatedBy(1);
        $this->entity->setUpdatedBy(2);

        $this->assertSame(1, $this->entity->getCreatedBy());
        $this->assertSame(2, $this->entity->getUpdatedBy());
    }

    public function testRelationships(): void
    {
        $baixaMock = $this->createMock(BaixasFinanceiras::class);
        $baixaMock->expects($this->atLeastOnce())
            ->method('setLancamento');
        $baixaMock->method('getLancamento')
            ->willReturn($this->entity);

        $this->entity->addBaixa($baixaMock);
        $this->assertCount(1, $this->entity->getBaixas());
        $this->assertTrue($this->entity->getBaixas()->contains($baixaMock));

        $this->entity->removeBaixa($baixaMock);
        $this->assertCount(0, $this->entity->getBaixas());
    }

    public function testBusinessLogicMethods(): void
    {
        // Valores para cálculo
        $this->entity->setValorPrincipal('100.00');
        $this->entity->setValorCondominio('10.00');
        $this->entity->setValorIptu('5.00');
        $this->entity->setValorAgua('2.00');
        $this->entity->setValorLuz('3.00');
        $this->entity->setValorGas('4.00');
        $this->entity->setValorOutros('1.00');
        $this->entity->setValorMulta('0.50');
        $this->entity->setValorJuros('0.20');
        $this->entity->setValorHonorarios('0.30');
        $this->entity->setValorDesconto('1.00');
        $this->entity->setValorBonificacao('0.50');
        $this->entity->setValorPago('20.00');

        $this->entity->calcularTotal();

        $this->assertSame('124.50', $this->entity->getValorTotal());
        $this->assertSame('104.50', $this->entity->getValorSaldo());

        // Teste de atraso
        $this->entity->setSituacao('aberto');
        $this->entity->setDataVencimento((new DateTime())->modify('-1 day'));
        $this->assertTrue($this->entity->isEmAtraso());
        $this->assertSame(1, $this->entity->getDiasAtraso());

        // Situação paga
        $this->entity->setSituacao('pago');
        $this->assertTrue($this->entity->isPago());
        $this->entity->setSituacao('aberto');
        $this->entity->setValorSaldo('0.00');
        $this->assertTrue($this->entity->isPago());

        // Parcial
        $this->entity->setValorPago('10.00');
        $this->entity->setValorSaldo('5.00');
        $this->assertTrue($this->entity->isParcial());

        // Competência formatada
        $competencia = new DateTime('2023-05-01');
        $this->entity->setCompetencia($competencia);
        $this->assertSame('05/2023', $this->entity->getCompetenciaFormatada());
    }

    public function testCreatedAndUpdatedAt(): void
    {
        $createdAt = $this->entity->getCreatedAt();
        $updatedAt = $this->entity->getUpdatedAt();

        $this->assertInstanceOf(DateTime::class, $createdAt);
        $this->assertInstanceOf(DateTime::class, $updatedAt);

        // PreUpdate deve atualizar updatedAt
        $oldUpdatedAt = clone $updatedAt;
        $this->entity->preUpdate();
        $newUpdatedAt = $this->entity->getUpdatedAt();

        $this->assertNotSame($oldUpdatedAt, $newUpdatedAt);
        $this->assertGreaterThan($oldUpdatedAt, $newUpdatedAt);
    }

    public function testCollectionIsArrayCollection(): void
    {
        $this->assertInstanceOf(ArrayCollection::class, $this->entity->getBaixas());
        $this->assertCount(0, $this->entity->getBaixas());
    }
}
