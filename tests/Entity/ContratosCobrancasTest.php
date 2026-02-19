<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\ContratosCobrancas;
use App\Entity\ImoveisContratos;
use App\Entity\Boletos;
use App\Entity\Users;
use PHPUnit\Framework\TestCase;
use Doctrine\ORM\Mapping as ORM;

class ContratosCobrancasTest extends TestCase
{
    private ContratosCobrancas $cobranca;
    private ImoveisContratos $contratoMock;
    private Boletos $boletoMock;
    private Users $userMock;

    protected function setUp(): void
    {
        $this->contratoMock = $this->createMock(ImoveisContratos::class);
        $this->boletoMock = $this->createMock(Boletos::class);
        $this->userMock = $this->createMock(Users::class);

        $this->cobranca = new ContratosCobrancas();
        $this->cobranca->setContrato($this->contratoMock)
            ->setCompetencia('2023-05')
            ->setPeriodoInicio(new \DateTime('2023-05-01'))
            ->setPeriodoFim(new \DateTime('2023-05-31'))
            ->setDataVencimento(new \DateTime('2023-05-15'))
            ->setValorAluguel('1000.00')
            ->setValorIptu('200.00')
            ->setValorCondominio('150.00')
            ->setValorTaxaAdmin('50.00')
            ->setValorOutros('0.00')
            ->setValorTotal('1500.00')
            ->setStatus(ContratosCobrancas::STATUS_PENDENTE)
            ->setTipoEnvio(ContratosCobrancas::TIPO_ENVIO_AUTOMATICO)
            ->setEmailDestino('cliente@example.com')
            ->setCreatedBy($this->userMock);
    }

    public function testDefaultValues(): void
    {
        $this->assertEquals(ContratosCobrancas::STATUS_PENDENTE, $this->cobranca->getStatus());
        $this->assertNull($this->cobranca->getBoleto());
        $this->assertFalse($this->cobranca->isBloqueadoRotinaAuto());
        $this->assertInstanceOf(\DateTimeInterface::class, $this->cobranca->getCreatedAt());
        $this->assertInstanceOf(\DateTimeInterface::class, $this->cobranca->getUpdatedAt());
    }

    public function testSettersAndGetters(): void
    {
        $boleto = $this->createMock(Boletos::class);
        $this->cobranca->setBoleto($boleto);
        $this->assertSame($boleto, $this->cobranca->getBoleto());

        $this->cobranca->setCompetencia('2024-01');
        $this->assertEquals('2024-01', $this->cobranca->getCompetencia());

        $inicio = new \DateTime('2024-01-01');
        $this->cobranca->setPeriodoInicio($inicio);
        $this->assertSame($inicio, $this->cobranca->getPeriodoInicio());

        $fim = new \DateTime('2024-01-31');
        $this->cobranca->setPeriodoFim($fim);
        $this->assertSame($fim, $this->cobranca->getPeriodoFim());

        $vencimento = new \DateTime('2024-01-15');
        $this->cobranca->setDataVencimento($vencimento);
        $this->assertSame($vencimento, $this->cobranca->getDataVencimento());

        $this->cobranca->setValorAluguel('1200.50');
        $this->assertEquals('1200.50', $this->cobranca->getValorAluguel());

        $this->cobranca->setValorIptu('250.75');
        $this->assertEquals('250.75', $this->cobranca->getValorIptu());

        $this->cobranca->setValorCondominio('180.25');
        $this->assertEquals('180.25', $this->cobranca->getValorCondominio());

        $this->cobranca->setValorTaxaAdmin('60.00');
        $this->assertEquals('60.00', $this->cobranca->getValorTaxaAdmin());

        $this->cobranca->setValorOutros('10.00');
        $this->assertEquals('10.00', $this->cobranca->getValorOutros());

        $this->cobranca->setValorTotal('1701.50');
        $this->assertEquals('1701.50', $this->cobranca->getValorTotal());

        $this->cobranca->setStatus(ContratosCobrancas::STATUS_BOLETO_GERADO);
        $this->assertEquals(ContratosCobrancas::STATUS_BOLETO_GERADO, $this->cobranca->getStatus());

        $this->cobranca->setTipoEnvio(ContratosCobrancas::TIPO_ENVIO_MANUAL);
        $this->assertEquals(ContratosCobrancas::TIPO_ENVIO_MANUAL, $this->cobranca->getTipoEnvio());

        $this->cobranca->setEmailDestino('novo@example.com');
        $this->assertEquals('novo@example.com', $this->cobranca->getEmailDestino());

        $this->cobranca->setBloqueadoRotinaAuto(true);
        $this->assertTrue($this->cobranca->isBloqueadoRotinaAuto());

        $enviado = new \DateTime('2024-01-20');
        $this->cobranca->setEnviadoEm($enviado);
        $this->assertSame($enviado, $this->cobranca->getEnviadoEm());
    }

    public function testStatusMethods(): void
    {
        $this->cobranca->setStatus(ContratosCobrancas::STATUS_PENDENTE);
        $this->assertTrue($this->cobranca->isPendente());
        $this->assertFalse($this->cobranca->isBoletoGerado());
        $this->assertFalse($this->cobranca->isEnviado());
        $this->assertFalse($this->cobranca->isPago());
        $this->assertFalse($this->cobranca->isCancelado());

        $this->cobranca->setStatus(ContratosCobrancas::STATUS_BOLETO_GERADO);
        $this->assertTrue($this->cobranca->isBoletoGerado());

        $this->cobranca->setStatus(ContratosCobrancas::STATUS_ENVIADO);
        $this->assertTrue($this->cobranca->isEnviado());

        $this->cobranca->setStatus(ContratosCobrancas::STATUS_PAGO);
        $this->assertTrue($this->cobranca->isPago());

        $this->cobranca->setStatus(ContratosCobrancas::STATUS_CANCELADO);
        $this->assertTrue($this->cobranca->isCancelado());
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

    public function testGetStatusLabelAndClass(): void
    {
        $this->cobranca->setStatus(ContratosCobrancas::STATUS_PENDENTE);
        $this->assertEquals('Pendente', $this->cobranca->getStatusLabel());
        $this->assertEquals('warning', $this->cobranca->getStatusClass());

        $this->cobranca->setStatus(ContratosCobrancas::STATUS_BOLETO_GERADO);
        $this->assertEquals('Boleto Gerado', $this->cobranca->getStatusLabel());
        $this->assertEquals('info', $this->cobranca->getStatusClass());

        $this->cobranca->setStatus(ContratosCobrancas::STATUS_ENVIADO);
        $this->assertEquals('Enviado', $this->cobranca->getStatusLabel());
        $this->assertEquals('primary', $this->cobranca->getStatusClass());

        $this->cobranca->setStatus(ContratosCobrancas::STATUS_PAGO);
        $this->assertEquals('Pago', $this->cobranca->getStatusLabel());
        $this->assertEquals('success', $this->cobranca->getStatusClass());

        $this->cobranca->setStatus(ContratosCobrancas::STATUS_CANCELADO);
        $this->assertEquals('Cancelado', $this->cobranca->getStatusLabel());
        $this->assertEquals('secondary', $this->cobranca->getStatusClass());
    }

    public function testGetTipoEnvioLabel(): void
    {
        $this->cobranca->setTipoEnvio(null);
        $this->assertNull($this->cobranca->getTipoEnvioLabel());

        $this->cobranca->setTipoEnvio(ContratosCobrancas::TIPO_ENVIO_AUTOMATICO);
        $this->assertEquals('Automático', $this->cobranca->getTipoEnvioLabel());

        $this->cobranca->setTipoEnvio(ContratosCobrancas::TIPO_ENVIO_MANUAL);
        $this->assertEquals('Manual', $this->cobranca->getTipoEnvioLabel());
    }

    public function testGetCompetenciaFormatada(): void
    {
        $this->cobranca->setCompetencia('2023-05');
        $this->assertEquals('Maio/2023', $this->cobranca->getCompetenciaFormatada());

        $this->cobranca->setCompetencia('2023-12');
        $this->assertEquals('Dezembro/2023', $this->cobranca->getCompetenciaFormatada());
    }

    public function testGetPeriodoFormatado(): void
    {
        $inicio = new \DateTime('2023-05-01');
        $fim = new \DateTime('2023-05-31');
        $this->cobranca->setPeriodoInicio($inicio);
        $this->cobranca->setPeriodoFim($fim);
        $this->assertEquals('01/05/2023 a 31/05/2023', $this->cobranca->getPeriodoFormatado());
    }

    public function testGetValorTotalFormatado(): void
    {
        $this->cobranca->setValorTotal('1500.50');
        $this->assertEquals('R$ 1.500,50', $this->cobranca->getValorTotalFormatado());
    }

    public function testCalcularTotal(): void
    {
        $this->cobranca->setValorAluguel('1000.00')
            ->setValorIptu('200.00')
            ->setValorCondominio('150.00')
            ->setValorTaxaAdmin('50.00')
            ->setValorOutros('0.00');
        $result = $this->cobranca->calcularTotal();
        // calcularTotal() returns a float with the sum of all values
        $this->assertEquals(1400.00, $result);
    }

    public function testGetValorTotalFloat(): void
    {
        $this->cobranca->setValorTotal('1234.56');
        $this->assertEquals(1234.56, $this->cobranca->getValorTotalFloat());
    }

    public function testGetStatusDisponiveis(): void
    {
        $disponiveis = ContratosCobrancas::getStatusDisponiveis();
        $this->assertIsArray($disponiveis);
        $this->assertArrayHasKey(ContratosCobrancas::STATUS_PENDENTE, $disponiveis);
        $this->assertEquals('Pendente', $disponiveis[ContratosCobrancas::STATUS_PENDENTE]);
    }

    public function testLifecycleCallbacks(): void
    {
        $c = new ContratosCobrancas();
        $c->setContrato($this->contratoMock);
        $c->setCompetencia('2023-01');
        $c->setPeriodoInicio(new \DateTime('2023-01-01'));
        $c->setPeriodoFim(new \DateTime('2023-01-31'));
        $c->setDataVencimento(new \DateTime('2023-01-15'));
        $c->setValorAluguel('1000.00');
        $c->setValorIptu('200.00');
        $c->setValorCondominio('150.00');
        $c->setValorTaxaAdmin('50.00');
        $c->setValorOutros('0.00');
        $c->setValorTotal('1500.00');

        // Simulate persist
        $c->onPrePersist();
        $createdAt = $c->getCreatedAt();
        $updatedAt = $c->getUpdatedAt();
        $this->assertInstanceOf(\DateTimeInterface::class, $createdAt);
        $this->assertInstanceOf(\DateTimeInterface::class, $updatedAt);

        // Simulate update
        sleep(1);
        $c->onPreUpdate();
        $this->assertGreaterThan($updatedAt, $c->getUpdatedAt());
    }
}
