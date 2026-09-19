<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Boletos;
use App\Entity\ConfiguracoesApiBanco;
use App\Entity\ContratosCobrancas;
use App\Entity\ContratosItensCobranca;
use App\Entity\ImoveisContratos;
use App\Entity\Imoveis;
use App\Entity\Pessoas;
use App\Repository\ContratosCobrancasRepository;
use App\Repository\ContratosItensCobrancaRepository;
use App\Repository\ImoveisContratosRepository;
use App\Repository\ConfiguracoesApiBancoRepository;
use App\Repository\LancamentosFinanceirosRepository;
use App\Service\BoletoSantanderService;
use App\Service\CobrancaContratoService;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class CobrancaContratoServiceTest extends TestCase
{
    private CobrancaContratoService $service;
    private EntityManagerInterface $em;
    private BoletoSantanderService $boletoService;
    private EmailService $emailService;
    private ContratosCobrancasRepository $cobrancasRepo;
    private ContratosItensCobrancaRepository $itensRepo;
    private ImoveisContratosRepository $contratosRepo;
    private ConfiguracoesApiBancoRepository $configApiBancoRepo;
    private LancamentosFinanceirosRepository $lancamentosFinanceirosRepo;
    private LoggerInterface $logger;
    private string $tempProjectDir;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->boletoService = $this->createMock(BoletoSantanderService::class);
        $this->emailService = $this->createMock(EmailService::class);
        $this->cobrancasRepo = $this->createMock(ContratosCobrancasRepository::class);
        $this->itensRepo = $this->createMock(ContratosItensCobrancaRepository::class);
        $this->contratosRepo = $this->createMock(ImoveisContratosRepository::class);
        $this->configApiBancoRepo = $this->createMock(ConfiguracoesApiBancoRepository::class);
        $this->lancamentosFinanceirosRepo = $this->createMock(LancamentosFinanceirosRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->tempProjectDir = sys_get_temp_dir() . '/cobranca_contrato_service_test';

        $this->service = new CobrancaContratoService(
            $this->em,
            $this->boletoService,
            $this->emailService,
            $this->cobrancasRepo,
            $this->itensRepo,
            $this->contratosRepo,
            $this->configApiBancoRepo,
            $this->lancamentosFinanceirosRepo,
            $this->logger,
            $this->tempProjectDir
        );
    }

    protected function tearDown(): void
    {
        $tempDir = $this->tempProjectDir . '/var/temp';
        if (is_dir($tempDir)) {
            foreach (glob($tempDir . '/*.pdf') as $arquivo) {
                unlink($arquivo);
            }
            rmdir($tempDir);
            rmdir($this->tempProjectDir . '/var');
            rmdir($this->tempProjectDir);
        }
    }

    private function criarContratoComLocatario(string $canalEnvio = ImoveisContratos::CANAL_EMAIL): ImoveisContratos
    {
        $imovel = new Imoveis();
        $this->setPrivateProperty($imovel, 'id', 10);

        $locatario = new Pessoas();
        $this->setPrivateProperty($locatario, 'idpessoa', 20);

        $contrato = new ImoveisContratos();
        $this->setPrivateProperty($contrato, 'id', 1);
        $contrato->setImovel($imovel);
        $contrato->setPessoaLocatario($locatario);
        $contrato->setCanalEnvio($canalEnvio);

        return $contrato;
    }

    private function criarCobrancaFixture(ImoveisContratos $contrato): ContratosCobrancas
    {
        $cobranca = new ContratosCobrancas();
        $this->setPrivateProperty($cobranca, 'id', 100);
        $cobranca->setContrato($contrato);
        $cobranca->setCompetencia('2026-01');
        $cobranca->setPeriodoInicio(new \DateTime('2025-12-11'));
        $cobranca->setPeriodoFim(new \DateTime('2026-01-10'));
        $cobranca->setValorTotal('1000.00');
        $cobranca->setDataVencimento(new \DateTime('2026-01-10'));

        return $cobranca;
    }

    public function testCalcularCompetenciaCurrentMonth(): void
    {
        $contrato = new ImoveisContratos();
        $contrato->setDiaVencimento(10);

        $dataRef = new \DateTime('2025-12-05'); // Dia 5, antes do vencimento (10)

        $competencia = $this->service->calcularCompetencia($contrato, $dataRef);

        $this->assertEquals('2025-12', $competencia);
    }

    public function testCalcularCompetenciaNextMonth(): void
    {
        $contrato = new ImoveisContratos();
        $contrato->setDiaVencimento(10);

        $dataRef = new \DateTime('2025-12-15'); // Dia 15, após o vencimento (10)

        $competencia = $this->service->calcularCompetencia($contrato, $dataRef);

        $this->assertEquals('2026-01', $competencia);
    }

    public function testCalcularCompetenciaExactDay(): void
    {
        $contrato = new ImoveisContratos();
        $contrato->setDiaVencimento(10);

        $dataRef = new \DateTime('2025-12-10'); // Exatamente no vencimento

        $competencia = $this->service->calcularCompetencia($contrato, $dataRef);

        $this->assertEquals('2026-01', $competencia);
    }

    public function testCalcularValoresSimple(): void
    {
        $contrato = new ImoveisContratos();
        $contrato->setValorContrato('1000.00');

        $valores = $this->service->calcularValores($contrato);

        $this->assertIsArray($valores);
        $this->assertEquals(1000.00, $valores['aluguel']);
        $this->assertEquals(1000.00, $valores['total']);
        $this->assertEquals(0, $valores['iptu']);
        $this->assertEquals(0, $valores['condominio']);
    }

    public function testExisteCobranca(): void
    {
        $this->cobrancasRepo
            ->expects($this->once())
            ->method('findByContratoCompetencia')
            ->with(1, '2025-12')
            ->willReturn(null);

        $existe = $this->service->existeCobranca(1, '2025-12');

        $this->assertFalse($existe);
    }

    public function testExisteCobrancaTrue(): void
    {
        $cobranca = new ContratosCobrancas();

        $this->cobrancasRepo
            ->expects($this->once())
            ->method('findByContratoCompetencia')
            ->with(1, '2025-12')
            ->willReturn($cobranca);

        $existe = $this->service->existeCobranca(1, '2025-12');

        $this->assertTrue($existe);
    }

    public function testCriarCobrancaDuplicated(): void
    {
        $contrato = new ImoveisContratos();
        $this->setPrivateProperty($contrato, 'id', 1);

        $this->cobrancasRepo
            ->expects($this->once())
            ->method('findByContratoCompetencia')
            ->willReturn(new ContratosCobrancas());

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Já existe cobrança');

        $this->service->criarCobranca($contrato, '2025-12');
    }


    private function setPrivateProperty(object $object, string $property, mixed $value): void
    {
        $reflection = new \ReflectionClass($object);
        $prop = $reflection->getProperty($property);
        $prop->setAccessible(true);
        $prop->setValue($object, $value);
    }

    public function testBuscarPorId(): void
    {
        $cobranca = new ContratosCobrancas();
        $this->setPrivateProperty($cobranca, 'id', 1);

        $this->cobrancasRepo
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($cobranca);

        $resultado = $this->service->buscarPorId(1);

        $this->assertInstanceOf(ContratosCobrancas::class, $resultado);
        $this->assertEquals(1, $resultado->getId());
    }

    public function testBuscarPorIdNotFound(): void
    {
        $this->cobrancasRepo
            ->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $resultado = $this->service->buscarPorId(999);

        $this->assertNull($resultado);
    }

    public function testListarCobrancas(): void
    {
        $this->cobrancasRepo
            ->expects($this->once())
            ->method('findByFiltros')
            ->with([], 20, 0)
            ->willReturn([
                'cobrancas' => [],
                'total' => 0
            ]);

        $resultado = $this->service->listarCobrancas();

        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('cobrancas', $resultado);
        $this->assertArrayHasKey('total', $resultado);
    }

    public function testListarCobrancasComFiltros(): void
    {
        $this->cobrancasRepo
            ->expects($this->once())
            ->method('findByFiltros')
            ->with(['status' => 'PENDENTE'], 50, 10)
            ->willReturn([
                'cobrancas' => [],
                'total' => 5
            ]);

        $resultado = $this->service->listarCobrancas(
            ['status' => 'PENDENTE'],
            50,
            10
        );

        $this->assertEquals(5, $resultado['total']);
    }

    public function testGetEstatisticas(): void
    {
        $this->cobrancasRepo
            ->expects($this->once())
            ->method('getEstatisticas')
            ->with(null)
            ->willReturn([
                'total' => 100,
                'pendentes' => 30,
                'pagos' => 70
            ]);

        $resultado = $this->service->getEstatisticas();

        $this->assertIsArray($resultado);
        $this->assertEquals(100, $resultado['total']);
    }

    public function testCancelarCobrancaSuccess(): void
    {
        $cobranca = new ContratosCobrancas();
        $this->setPrivateProperty($cobranca, 'id', 1);
        $cobranca->setStatus(ContratosCobrancas::STATUS_PENDENTE);

        // Mock the podeCancelar method
        $reflection = new \ReflectionClass($cobranca);
        $method = $reflection->getMethod('podeCancelar');
        $method->setAccessible(true);

        $this->em
            ->expects($this->once())
            ->method('persist');

        $this->em
            ->expects($this->once())
            ->method('flush');

        $resultado = $this->service->cancelarCobranca($cobranca);

        $this->assertTrue($resultado['sucesso']);
    }

    public function testBuscarCobrancasPendentes(): void
    {
        $data = new \DateTime('2025-12-15');

        $this->cobrancasRepo
            ->expects($this->once())
            ->method('findPendentesPorVencimento')
            ->with($data, false)
            ->willReturn([]);

        $resultado = $this->service->buscarCobrancasPendentes($data);

        $this->assertIsArray($resultado);
    }

    public function testGerarEEnviarBoletoCanalEmailFluxoAtual(): void
    {
        $contrato = $this->criarContratoComLocatario(ImoveisContratos::CANAL_EMAIL);
        $cobranca = $this->criarCobrancaFixture($contrato);

        $configApi = new ConfiguracoesApiBanco();
        $this->setPrivateProperty($configApi, 'id', 5);
        $this->configApiBancoRepo->method('findBy')->willReturn([$configApi]);

        $boleto = new Boletos();
        $this->setPrivateProperty($boleto, 'id', 200);

        $this->boletoService->expects($this->once())
            ->method('criarBoletoFromArray')
            ->willReturn($boleto);

        $this->boletoService->expects($this->once())
            ->method('registrarBoleto')
            ->with($boleto)
            ->willReturn(['sucesso' => true]);

        $this->emailService->expects($this->once())
            ->method('enviarBoletoLocatario')
            ->willReturn(['sucesso' => true, 'email' => 'inquilino@teste.com']);

        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $resultado = $this->service->gerarEEnviarBoleto($cobranca);

        $this->assertTrue($resultado['sucesso']);
        $this->assertEquals(ContratosCobrancas::STATUS_ENVIADO, $cobranca->getStatus());
        $this->assertEquals(ImoveisContratos::CANAL_EMAIL, $cobranca->getCanalEnvio());
        $this->assertEquals('inquilino@teste.com', $cobranca->getEmailDestino());
    }

    public function testGerarEEnviarBoletoCanalCorreioAguardaEntregaSemEmail(): void
    {
        $contrato = $this->criarContratoComLocatario(ImoveisContratos::CANAL_CORREIO);
        $cobranca = $this->criarCobrancaFixture($contrato);

        $configApi = new ConfiguracoesApiBanco();
        $this->setPrivateProperty($configApi, 'id', 5);
        $this->configApiBancoRepo->method('findBy')->willReturn([$configApi]);

        $boleto = new Boletos();
        $this->setPrivateProperty($boleto, 'id', 201);

        $this->boletoService->expects($this->once())
            ->method('criarBoletoFromArray')
            ->willReturn($boleto);

        $this->boletoService->expects($this->once())
            ->method('registrarBoleto')
            ->willReturn(['sucesso' => true]);

        $this->emailService->expects($this->never())
            ->method('enviarBoletoLocatario');

        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $resultado = $this->service->gerarEEnviarBoleto($cobranca);

        $this->assertTrue($resultado['sucesso']);
        $this->assertEquals(ContratosCobrancas::STATUS_AGUARDANDO_ENTREGA, $cobranca->getStatus());
        $this->assertEquals(ImoveisContratos::CANAL_CORREIO, $cobranca->getCanalEnvio());
        $this->assertStringContainsString('Correio', $resultado['mensagem']);
    }

    public function testMarcarEntregueSuccess(): void
    {
        $contrato = $this->criarContratoComLocatario(ImoveisContratos::CANAL_ADMINISTRACAO);
        $cobranca = $this->criarCobrancaFixture($contrato);
        $cobranca->setStatus(ContratosCobrancas::STATUS_AGUARDANDO_ENTREGA);

        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $resultado = $this->service->marcarEntregue($cobranca);

        $this->assertTrue($resultado['sucesso']);
        $this->assertEquals(ContratosCobrancas::STATUS_ENVIADO, $cobranca->getStatus());
        $this->assertNotNull($cobranca->getEnviadoEm());
    }

    public function testMarcarEntregueStatusInvalido(): void
    {
        $contrato = $this->criarContratoComLocatario(ImoveisContratos::CANAL_EMAIL);
        $cobranca = $this->criarCobrancaFixture($contrato);
        $cobranca->setStatus(ContratosCobrancas::STATUS_PENDENTE);

        $this->em->expects($this->never())->method('persist');
        $this->em->expects($this->never())->method('flush');

        $resultado = $this->service->marcarEntregue($cobranca);

        $this->assertFalse($resultado['sucesso']);
    }

    public function testProcessarEnvioAutomaticoBloqueiaInadimplente(): void
    {
        $contrato = $this->criarContratoComLocatario(ImoveisContratos::CANAL_EMAIL);
        $contrato->setDiaVencimento(10);
        $contrato->setDiasAntecedenciaBoleto(45);

        $cobrancaExistente = $this->criarCobrancaFixture($contrato);
        $cobrancaExistente->setStatus(ContratosCobrancas::STATUS_PENDENTE);

        $this->contratosRepo->method('findContratosParaEnvioAutomatico')
            ->willReturn([$contrato]);

        $this->cobrancasRepo->method('findByContratoCompetencia')
            ->willReturn($cobrancaExistente);

        $this->lancamentosFinanceirosRepo->expects($this->once())
            ->method('possuiLancamentoEmAbertoAntesDe')
            ->with(20, $this->isInstanceOf(\DateTimeInterface::class))
            ->willReturn(true);

        $this->boletoService->expects($this->never())->method('criarBoletoFromArray');
        $this->emailService->expects($this->never())->method('enviarBoletoLocatario');

        $resultado = $this->service->processarEnvioAutomatico();

        $this->assertEquals(0, $resultado['sucesso']);
        $this->assertEquals(1, $resultado['ignorados']);
        $this->assertStringContainsString('inadimplente', strtolower($resultado['detalhes'][0]['motivo']));
    }
}
