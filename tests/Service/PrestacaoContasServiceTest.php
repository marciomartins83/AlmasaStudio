<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\PrestacoesContas;
use App\Entity\Pessoas;
use App\Entity\Imoveis;
use App\Repository\PrestacoesContasRepository;
use App\Repository\PrestacoesContasItensRepository;
use App\Repository\LancamentosFinanceirosRepository;
use App\Repository\LancamentosRepository;
use App\Repository\ImoveisRepository;
use App\Repository\ImoveisContratosRepository;
use App\Repository\PessoaRepository;
use App\Repository\ContasBancariasRepository;
use App\Service\PrestacaoContasService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class PrestacaoContasServiceTest extends TestCase
{
    private PrestacaoContasService $service;
    private EntityManagerInterface $em;
    private PrestacoesContasRepository $prestacaoRepo;
    private PrestacoesContasItensRepository $itemRepo;
    private LancamentosFinanceirosRepository $lancFinanceiroRepo;
    private LancamentosRepository $lancamentoRepo;
    private ImoveisRepository $imovelRepo;
    private ImoveisContratosRepository $contratoRepo;
    private PessoaRepository $pessoaRepo;
    private ContasBancariasRepository $contaBancariaRepo;
    private Security $security;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->prestacaoRepo = $this->createMock(PrestacoesContasRepository::class);
        $this->itemRepo = $this->createMock(PrestacoesContasItensRepository::class);
        $this->lancFinanceiroRepo = $this->createMock(LancamentosFinanceirosRepository::class);
        $this->lancamentoRepo = $this->createMock(LancamentosRepository::class);
        $this->imovelRepo = $this->createMock(ImoveisRepository::class);
        $this->contratoRepo = $this->createMock(ImoveisContratosRepository::class);
        $this->pessoaRepo = $this->createMock(PessoaRepository::class);
        $this->contaBancariaRepo = $this->createMock(ContasBancariasRepository::class);
        $this->security = $this->createMock(Security::class);

        $params = $this->createMock(ParameterBagInterface::class);
        $params
            ->expects($this->any())
            ->method('get')
            ->with('kernel.project_dir')
            ->willReturn('/app');

        $this->service = new PrestacaoContasService(
            $this->em,
            $this->prestacaoRepo,
            $this->itemRepo,
            $this->lancFinanceiroRepo,
            $this->lancamentoRepo,
            $this->imovelRepo,
            $this->contratoRepo,
            $this->pessoaRepo,
            $this->contaBancariaRepo,
            $this->security,
            $params
        );
    }

    public function testListarPrestacoes(): void
    {
        $this->prestacaoRepo
            ->expects($this->once())
            ->method('findByFiltros')
            ->with([])
            ->willReturn([]);

        $resultado = $this->service->listarPrestacoes();

        $this->assertIsArray($resultado);
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
        $prestacao = new PrestacoesContas();
        $this->setPrivateProperty($prestacao, 'id', 1);

        $this->prestacaoRepo
            ->expects($this->once())
            ->method('findByIdComItens')
            ->with(1)
            ->willReturn($prestacao);

        $resultado = $this->service->buscarPorId(1);

        $this->assertInstanceOf(PrestacoesContas::class, $resultado);
    }

    public function testBuscarPorIdNotFound(): void
    {
        $this->prestacaoRepo
            ->expects($this->once())
            ->method('findByIdComItens')
            ->with(999)
            ->willReturn(null);

        $resultado = $this->service->buscarPorId(999);

        $this->assertNull($resultado);
    }

    public function testCalcularPeriodoDiario(): void
    {
        $dataBase = new \DateTime('2025-12-15');

        $periodo = $this->service->calcularPeriodo(PrestacoesContas::PERIODO_DIARIO, $dataBase);

        $this->assertEquals('2025-12-15', $periodo['inicio']->format('Y-m-d'));
        $this->assertEquals('2025-12-15', $periodo['fim']->format('Y-m-d'));
    }

    public function testCalcularPeriodoSemanal(): void
    {
        $dataBase = new \DateTime('2025-12-15'); // Monday

        $periodo = $this->service->calcularPeriodo(PrestacoesContas::PERIODO_SEMANAL, $dataBase);

        $this->assertIsArray($periodo);
        $this->assertArrayHasKey('inicio', $periodo);
        $this->assertArrayHasKey('fim', $periodo);
    }

    public function testCalcularPeriodoMensal(): void
    {
        $dataBase = new \DateTime('2025-12-15');

        $periodo = $this->service->calcularPeriodo(PrestacoesContas::PERIODO_MENSAL, $dataBase);

        $this->assertEquals('2025-12-01', $periodo['inicio']->format('Y-m-d'));
        $this->assertEquals('2025-12-31', $periodo['fim']->format('Y-m-d'));
    }

    public function testCalcularPeriodoQuinzenal(): void
    {
        $dataBase = new \DateTime('2025-12-08'); // Before 15th

        $periodo = $this->service->calcularPeriodo(PrestacoesContas::PERIODO_QUINZENAL, $dataBase);

        $this->assertEquals('2025-12-01', $periodo['inicio']->format('Y-m-d'));
        $this->assertEquals('2025-12-15', $periodo['fim']->format('Y-m-d'));
    }

    public function testCalcularPeriodoQuinzenalSecondHalf(): void
    {
        $dataBase = new \DateTime('2025-12-20'); // After 15th

        $periodo = $this->service->calcularPeriodo(PrestacoesContas::PERIODO_QUINZENAL, $dataBase);

        $this->assertEquals('2025-12-16', $periodo['inicio']->format('Y-m-d'));
        $this->assertEquals('2025-12-31', $periodo['fim']->format('Y-m-d'));
    }

    public function testCalcularPeriodoSemestral(): void
    {
        $dataBase = new \DateTime('2025-06-15');

        $periodo = $this->service->calcularPeriodo(PrestacoesContas::PERIODO_SEMESTRAL, $dataBase);

        $this->assertIsArray($periodo);
        $this->assertArrayHasKey('inicio', $periodo);
        $this->assertArrayHasKey('fim', $periodo);
    }

    public function testCalcularPeriodoAnual(): void
    {
        $dataBase = new \DateTime('2025-06-15');

        $periodo = $this->service->calcularPeriodo(PrestacoesContas::PERIODO_ANUAL, $dataBase);

        $this->assertEquals('2025-01-01', $periodo['inicio']->format('Y-m-d'));
        $this->assertEquals('2025-12-31', $periodo['fim']->format('Y-m-d'));
    }

    public function testCalcularTaxaAdminZeroProprietario(): void
    {
        $taxa = $this->service->calcularTaxaAdmin(1000.00, null);

        $this->assertEquals(0, $taxa);
    }

    public function testCalcularTaxaAdminZeroValor(): void
    {
        $taxa = $this->service->calcularTaxaAdmin(0, 1);

        $this->assertEquals(0, $taxa);
    }

    public function testCalcularTaxaAdminValue(): void
    {
        $contrato = $this->createMock(\App\Entity\ImoveisContratos::class);

        $this->contratoRepo
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($contrato);

        $taxa = $this->service->calcularTaxaAdmin(1000.00, 1);

        // Default 10%
        $this->assertEquals(100.0, $taxa);
    }

    public function testCalcularRetencaoIR(): void
    {
        $retencao = $this->service->calcularRetencaoIR(1000.00, 1);

        // Currently returns 0
        $this->assertEquals(0, $retencao);
    }

    public function testGetEstatisticas(): void
    {
        $this->prestacaoRepo
            ->expects($this->once())
            ->method('getEstatisticas')
            ->with(null)
            ->willReturn([
                'total' => 10,
                'aprovadas' => 5,
                'pendentes' => 3,
            ]);

        $resultado = $this->service->getEstatisticas();

        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('total', $resultado);
    }

    public function testGetEstatisticasAno(): void
    {
        $this->prestacaoRepo
            ->expects($this->once())
            ->method('getEstatisticas')
            ->with(2025)
            ->willReturn([]);

        $resultado = $this->service->getEstatisticas(2025);

        $this->assertIsArray($resultado);
    }

    public function testGetEstatisticasMesAtual(): void
    {
        $this->prestacaoRepo
            ->expects($this->once())
            ->method('getEstatisticasMesAtual')
            ->willReturn([]);

        $resultado = $this->service->getEstatisticasMesAtual();

        $this->assertIsArray($resultado);
    }

    public function testGetHistoricoPorProprietario(): void
    {
        $this->prestacaoRepo
            ->expects($this->once())
            ->method('findByProprietario')
            ->with(1)
            ->willReturn([]);

        $resultado = $this->service->getHistoricoPorProprietario(1);

        $this->assertIsArray($resultado);
    }

    public function testGetImoveisDoProprietario(): void
    {
        $mockQb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $mockQb->expects($this->any())->method('where')->willReturnSelf();
        $mockQb->expects($this->any())->method('setParameter')->willReturnSelf();
        $mockQb->expects($this->any())->method('orderBy')->willReturnSelf();

        $mockQuery = $this->createMock(\Doctrine\ORM\Query::class);
        $mockQuery->expects($this->once())->method('getResult')->willReturn([]);

        $mockQb->expects($this->any())->method('getQuery')->willReturn($mockQuery);

        $this->imovelRepo
            ->expects($this->any())
            ->method('createQueryBuilder')
            ->willReturn($mockQb);

        $resultado = $this->service->getImoveisDoProprietario(1);

        $this->assertIsArray($resultado);
    }

    public function testGetAnosDisponiveis(): void
    {
        $this->prestacaoRepo
            ->expects($this->once())
            ->method('getAnosDisponiveis')
            ->willReturn([2025, 2024, 2023]);

        $resultado = $this->service->getAnosDisponiveis();

        $this->assertIsArray($resultado);
        $this->assertContains(2025, $resultado);
    }

    public function testPreviewSimple(): void
    {
        $filtros = [
            'proprietario' => 1,
            'data_inicio' => new \DateTime('2025-12-01'),
            'data_fim' => new \DateTime('2025-12-31'),
            'incluir_ficha_financeira' => false,
            'incluir_lancamentos' => false,
        ];

        $resultado = $this->service->preview($filtros);

        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('itens', $resultado);
        $this->assertArrayHasKey('resumo', $resultado);
    }

    public function testAprovarPrestacaoNotFound(): void
    {
        $this->prestacaoRepo
            ->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('não encontrada');

        $this->service->aprovarPrestacao(999);
    }

    public function testExcluirPrestacaoNotFound(): void
    {
        $this->prestacaoRepo
            ->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('não encontrada');

        $this->service->excluirPrestacao(999);
    }
}
