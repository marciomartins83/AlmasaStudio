<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\ContratosCobrancas;
use App\Entity\ImoveisContratos;
use App\Entity\Boletos;
use App\Entity\Users;
use PHPUnit\Framework\TestCase;
use DateTime;
use DateTimeImmutable;

final class ContratosCobrancasTest extends TestCase
{
    private ContratosCobrancas $cobranca;

    protected function setUp(): void
    {
        $this->cobranca = new ContratosCobrancas();
    }

    // --------------------------------------------------------------------
    //  Getters / Setters
    // --------------------------------------------------------------------
    public function testIdIsNullInitially(): void
    {
        $this->assertNull($this->cobranca->getId());
    }

    public function testContrato(): void
    {
        $contrato = $this->createMock(ImoveisContratos::class);
        $this->assertSame($this->cobranca, $this->cobranca->setContrato($contrato));
        $this->assertSame($contrato, $this->cobranca->getContrato());
    }

    public function testBoleto(): void
    {
        $boleto = $this->createMock(Boletos::class);
        $this->assertSame($this->cobranca, $this->cobranca->setBoleto($boleto));
        $this->assertSame($boleto, $this->cobranca->getBoleto());

        // null
        $this->assertSame($this->cobranca, $this->cobranca->setBoleto(null));
        $this->assertNull($this->cobranca->getBoleto());
    }

    public function testCompetencia(): void
    {
        $this->assertSame($this->cobranca, $this->cobranca->setCompetencia('2024-02'));
        $this->assertSame('2024-02', $this->cobranca->getCompetencia());
    }

    public function testPeriodoInicio(): void
    {
        $date = new DateTimeImmutable('2024-02-01');
        $this->assertSame($this->cobranca, $this->cobranca->setPeriodoInicio($date));
        $this->assertSame($date, $this->cobranca->getPeriodoInicio());
    }

    public function testPeriodoFim(): void
    {
        $date = new DateTimeImmutable('2024-02-28');
        $this->assertSame($this->cobranca, $this->cobranca->setPeriodoFim($date));
        $this->assertSame($date, $this->cobranca->getPeriodoFim());
    }

    public function testDataVencimento(): void
    {
        $date = new DateTimeImmutable('2024-03-05');
        $this->assertSame($this->cobranca, $this->cobranca->setDataVencimento($date));
        $this->assertSame($date, $this->cobranca->getDataVencimento());
    }

    public function testValores(): void
    {
        $this->assertSame($this->cobranca, $this->cobranca->setValorAluguel('100.50'));
        $this->assertSame('100.50', $this->cobranca->getValorAluguel());

        $this->assertSame($this->cobranca, $this->cobranca->setValorAluguel(200.75));
        $this->assertSame('200.75', $this->cobranca->getValorAluguel());

        $this->assertSame($this->cobranca, $this->cobranca->setValorIptu('50.00'));
        $this->assertSame('50.00', $this->cobranca->getValorIptu());

        $this->assertSame($this->cobranca, $this->cobranca->setValorCondominio('30.00'));
        $this->assertSame('30.00', $this->cobranca->getValorCondominio());

        $this->assertSame($this->cobranca, $this->cobranca->setValorTaxaAdmin('5.00'));
        $this->assertSame('5.00', $this->cobranca->getValorTaxaAdmin());

        $this->assertSame($this->cobranca, $this->cobranca->setValorOutros('10.00'));
        $this->assertSame('10.00', $this->cobranca->getValorOutros());
    }

    public function testValorTotal(): void
    {
        $this->cobranca->setValorAluguel('100.00');
        $this->cobranca->setValorIptu('20.00');
        $this->cobranca->setValorCondominio('30.00');
        $this->cobranca->setValorTaxaAdmin('5.00');
        $this->cobranca->setValorOutros('5.00');

        $this->assertSame('100.00', $this->cobranca->getValorAluguel());
        $this->assertSame('20.00', $this->cobranca->getValorIptu());
        $this->assertSame('30.00', $this->cobranca->getValorCondominio());
        $this->assertSame('5.00', $this->cobranca->getValorTaxaAdmin());
        $this->assertSame('5.00', $this->cobranca->getValorOutros());

        $this->cobranca->setValorTotal('200.00');
        $this->assertSame('200.00', $this->cobranca->getValorTotal());
        $this->assertSame(200.00, $this->cobranca->getValorTotalFloat());
    }

    public function testItensDetalhados(): void
    {
        $this->assertNull($this->cobranca->getItensDetalhados());

        $array = ['aluguel' => 100, 'iptu' => 20];
        $this->assertSame($this->cobranca, $this->cobranca->setItensDetalhados($array));
        $this->assertSame($array, $this->cobranca->getItensDetalhados());
    }

    public function testStatus(): void
    {
        $this->assertSame($this->cobranca, $this->cobranca->setStatus(ContratosCobrancas::STATUS_PENDENTE));
        $this->assertSame(ContratosCobrancas::STATUS_PENDENTE, $this->cobranca->getStatus());
    }

    public function testTipoEnvio(): void
    {
        $this->assertSame($this->cobranca, $this->cobranca->setTipoEnvio(ContratosCobrancas::TIPO_ENVIO_AUTOMATICO));
        $this->assertSame(ContratosCobrancas::TIPO_ENVIO_AUTOMATICO, $this->cobranca->getTipoEnvio());

        $this->assertSame($this->cobranca, $this->cobranca->setTipoEnvio(null));
        $this->assertNull($this->cobranca->getTipoEnvio());
    }

    public function testEnviadoEm(): void
    {
        $date = new DateTimeImmutable('2024-03-01 10:00:00');
        $this->assertSame($this->cobranca, $this->cobranca->setEnviadoEm($date));
        $this->assertSame($date, $this->cobranca->getEnviadoEm());

        $this->assertSame($this->cobranca, $this->cobranca->setEnviadoEm(null));
        $this->assertNull($this->cobranca->getEnviadoEm());
    }

    public function testEmailDestino(): void
    {
        $this->assertSame($this->cobranca, $this->cobranca->setEmailDestino('teste@example.com'));
        $this->assertSame('teste@example.com', $this->cobranca->getEmailDestino());

        $this->assertSame($this->cobranca, $this->cobranca->setEmailDestino(null));
        $this->assertNull($this->cobranca->getEmailDestino());
    }

    public function testBloqueadoRotinaAuto(): void
    {
        $this->assertSame($this->cobranca, $this->cobranca->setBloqueadoRotinaAuto(true));
        $this->assertTrue($this->cobranca->isBloqueadoRotinaAuto());

        $this->assertSame($this->cobranca, $this->cobranca->setBloqueadoRotinaAuto(false));
        $this->assertFalse($this->cobranca->isBloqueadoRotinaAuto());
    }

    public function testCreatedAtUpdatedAt(): void
    {
        $created = $this->cobranca->getCreatedAt();
        $updated = $this->cobranca->getUpdatedAt();

        $this->assertInstanceOf(DateTime::class, $created);
        $this->assertInstanceOf(DateTime::class, $updated);
        $this->assertEquals($created, $updated);
    }

    public function testCreatedBy(): void
    {
        $user = $this->createMock(Users::class);
        $this->assertSame($this->cobranca, $this->cobranca->setCreatedBy($user));
        $this->assertSame($user, $this->cobranca->getCreatedBy());

        $this->assertSame($this->cobranca, $this->cobranca->setCreatedBy(null));
        $this->assertNull($this->cobranca->getCreatedBy());
    }

    // --------------------------------------------------------------------
    //  Business logic
    // --------------------------------------------------------------------
    public function testCalcularTotal(): void
    {
        $this->cobranca->setValorAluguel('100.00');
        $this->cobranca->setValorIptu('20.00');
        $this->cobranca->setValorCondominio('30.00');
        $this->cobranca->setValorTaxaAdmin('5.00');
        $this->cobranca->setValorOutros('5.00');

        $this->assertSame(160.00, $this->cobranca->calcularTotal());
    }

    public function testGetValorTotalFormatado(): void
    {
        $this->cobranca->setValorTotal('1234.56');
        $this->assertSame('R$ 1.234,56', $this->cobranca->getValorTotalFormatado());
    }

    public function testGetCompetenciaFormatada(): void
    {
        $this->cobranca->setCompetencia('2024-02');
        $this->assertSame('Fevereiro/2024', $this->cobranca->getCompetenciaFormatada());
    }

    public function testGetPeriodoFormatado(): void
    {
        $inicio = new DateTimeImmutable('2024-02-01');
        $fim = new DateTimeImmutable('2024-02-28');
        $this->cobranca->setPeriodoInicio($inicio);
        $this->cobranca->setPeriodoFim($fim);

        $this->assertSame('01/02/2024 a 28/02/2024', $this->cobranca->getPeriodoFormatado());
    }

    public function testStatusLabelAndClass(): void
    {
        foreach (ContratosCobrancas::getStatusDisponiveis() as $status => $label) {
            $this->cobranca->setStatus($status);
            $this->assertSame($label, $this->cobranca->getStatusLabel());
            $this->assertIsString($this->cobranca->getStatusClass());
        }
    }

    public function testTipoEnvioLabel(): void
    {
        $this->cobranca->setTipoEnvio(ContratosCobrancas::TIPO_ENVIO_AUTOMATICO);
        $this->assertSame('Automático', $this->cobranca->getTipoEnvioLabel());

        $this->cobranca->setTipoEnvio(ContratosCobrancas::TIPO_ENVIO_MANUAL);
        $this->assertSame('Manual', $this->cobranca->getTipoEnvioLabel());

        $this->cobranca->setTipoEnvio(null);
        $this->assertNull($this->cobranca->getTipoEnvioLabel());
    }

    public function testPodeEnviarManualmente(): void
    {
        $this->cobranca->setStatus(ContratosCobrancas::STATUS_PENDENTE);
        $this->assertTrue($this->cobranca->podeEnviarManualmente());

        $this->cobranca->setStatus(ContratosCobrancas::STATUS_BOLETO_GERADO);
        $this->assertTrue($this->cobranca->podeEnviarManualmente());

        $this->cobranca->setStatus(ContratosCobrancas::STATUS_ENVIADO);
        $this->assertFalse($this->cobranca->podeEnviarManualmente());
    }

    public function testPodeGerarBoleto(): void
    {
        $this->cobranca->setStatus(ContratosCobrancas::STATUS_PENDENTE);
        $this->cobranca->setBoleto(null);
        $this->assertTrue($this->cobranca->podeGerarBoleto());

        $boleto = $this->createMock(Boletos::class);
        $this->cobranca->setBoleto($boleto);
        $this->assertFalse($this->cobranca->podeGerarBoleto());
    }

    public function testPodeCancelar(): void
    {
        $this->cobranca->setStatus(ContratosCobrancas::STATUS_PENDENTE);
        $this->assertTrue($this->cobranca->podeCancelar());

        $this->cobranca->setStatus(ContratosCobrancas::STATUS_BOLETO_GERADO);
        $this->assertTrue($this->cobranca->podeCancelar());

        $this->cobranca->setStatus(ContratosCobrancas::STATUS_ENVIADO);
        $this->assertTrue($this->cobranca->podeCancelar());

        $this->cobranca->setStatus(ContratosCobrancas::STATUS_PAGO);
        $this->assertFalse($this->cobranca->podeCancelar());
    }

    // --------------------------------------------------------------------
    //  Lifecycle callbacks
    // --------------------------------------------------------------------
    public function testOnPrePersistUpdatesTimestamps(): void
    {
        $c = new ContratosCobrancas();
        $c->onPrePersist();

        $this->assertInstanceOf(DateTime::class, $c->getCreatedAt());
        $this->assertInstanceOf(DateTime::class, $c->getUpdatedAt());
    }

    public function testOnPreUpdateUpdatesTimestamp(): void
    {
        $c = new ContratosCobrancas();
        $c->onPrePersist(); // set initial
        $initial = $c->getUpdatedAt();

        // simulate a change
        $c->setValorAluguel('200.00');
        $c->onPreUpdate();

        $this->assertNotEquals($initial, $c->getUpdatedAt());
    }
}
