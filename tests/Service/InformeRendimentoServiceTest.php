<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\DimobConfiguracoes;
use App\Entity\InformesRendimentos;
use App\Entity\Pessoas;
use App\Entity\Imoveis;
use App\Repository\DimobConfiguracoesRepository;
use App\Repository\InformesRendimentosRepository;
use App\Repository\InformesRendimentosValoresRepository;
use App\Repository\LancamentosRepository;
use App\Repository\PessoaRepository;
use App\Repository\PlanoContasRepository;
use App\Repository\ImoveisRepository;
use App\Service\InformeRendimentoService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class InformeRendimentoServiceTest extends TestCase
{
    private InformeRendimentoService $service;
    private EntityManagerInterface $em;
    private InformesRendimentosRepository $informesRepo;
    private InformesRendimentosValoresRepository $valoresRepo;
    private LancamentosRepository $lancamentosRepo;
    private PlanoContasRepository $planoContasRepo;
    private DimobConfiguracoesRepository $dimobRepo;
    private PessoaRepository $pessoasRepo;
    private ImoveisRepository $imoveisRepo;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->informesRepo = $this->createMock(InformesRendimentosRepository::class);
        $this->valoresRepo = $this->createMock(InformesRendimentosValoresRepository::class);
        $this->lancamentosRepo = $this->createMock(LancamentosRepository::class);
        $this->planoContasRepo = $this->createMock(PlanoContasRepository::class);
        $this->dimobRepo = $this->createMock(DimobConfiguracoesRepository::class);
        $this->pessoasRepo = $this->createMock(PessoaRepository::class);
        $this->imoveisRepo = $this->createMock(ImoveisRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->service = new InformeRendimentoService(
            $this->em,
            $this->informesRepo,
            $this->valoresRepo,
            $this->lancamentosRepo,
            $this->planoContasRepo,
            $this->dimobRepo,
            $this->pessoasRepo,
            $this->imoveisRepo,
            $this->logger
        );
    }

    public function testProcessarInformesAnoEmpty(): void
    {
        $this->lancamentosRepo
            ->expects($this->once())
            ->method('findParaProcessamentoInforme')
            ->with(2025, null, null)
            ->willReturn([]);

        $resultado = $this->service->processarInformesAno(2025);

        $this->assertIsArray($resultado);
        $this->assertEquals(0, $resultado['processados']);
        $this->assertEquals(0, $resultado['criados']);
    }

    public function testBuscarInformesComFiltros(): void
    {
        $this->informesRepo
            ->expects($this->once())
            ->method('findByFiltros')
            ->with([])
            ->willReturn([]);

        $resultado = $this->service->buscarInformesComFiltros([]);

        $this->assertIsArray($resultado);
    }

    public function testBuscarInformePorId(): void
    {
        $informe = new InformesRendimentos();
        // Use reflection to set the id property since there's no setId() method
        $reflectionProperty = new \ReflectionProperty($informe, 'id');
        $reflectionProperty->setValue($informe, 1);
        $informe->setAno(2025);

        $this->informesRepo
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($informe);

        $resultado = $this->service->buscarInformePorId(1);

        $this->assertInstanceOf(InformesRendimentos::class, $resultado);
        $this->assertEquals(1, $resultado->getId());
    }

    public function testBuscarInformePorIdNotFound(): void
    {
        $this->informesRepo
            ->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $resultado = $this->service->buscarInformePorId(999);

        $this->assertNull($resultado);
    }

    public function testBuscarDimobPorAno(): void
    {
        $config = new DimobConfiguracoes();
        $config->setAno(2025);

        $this->dimobRepo
            ->expects($this->once())
            ->method('findByAno')
            ->with(2025)
            ->willReturn($config);

        $resultado = $this->service->buscarDimobPorAno(2025);

        $this->assertInstanceOf(DimobConfiguracoes::class, $resultado);
        $this->assertEquals(2025, $resultado->getAno());
    }

    public function testBuscarDimobPorAnoNotFound(): void
    {
        $this->dimobRepo
            ->expects($this->once())
            ->method('findByAno')
            ->with(2025)
            ->willReturn(null);

        $resultado = $this->service->buscarDimobPorAno(2025);

        $this->assertNull($resultado);
    }

    public function testSalvarDimobConfiguracao(): void
    {
        $this->dimobRepo
            ->expects($this->once())
            ->method('findByAno')
            ->with(2025)
            ->willReturn(null);

        $this->em
            ->expects($this->once())
            ->method('persist');

        $this->em
            ->expects($this->once())
            ->method('flush');

        $resultado = $this->service->salvarDimobConfiguracao([
            'ano' => 2025,
            'cnpjDeclarante' => '00000000000191',
            'cpfResponsavel' => '12345678901',
            'codigoCidade' => '3550308',
        ]);

        $this->assertInstanceOf(DimobConfiguracoes::class, $resultado);
        $this->assertEquals(2025, $resultado->getAno());
    }

    public function testGerarArquivoDimobNotFound(): void
    {
        $this->dimobRepo
            ->expects($this->once())
            ->method('findByAno')
            ->with(2025)
            ->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Configuração DIMOB não encontrada');

        $this->service->gerarArquivoDimob(2025);
    }

    public function testGerarArquivoDimobNoInformes(): void
    {
        $config = new DimobConfiguracoes();
        $config->setAno(2025);

        $this->dimobRepo
            ->expects($this->once())
            ->method('findByAno')
            ->with(2025)
            ->willReturn($config);

        $this->informesRepo
            ->expects($this->once())
            ->method('findParaDimob')
            ->with(2025, null, null)
            ->willReturn([]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Nenhum informe encontrado');

        $this->service->gerarArquivoDimob(2025);
    }

    public function testListarProprietarios(): void
    {
        $mockQb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $mockQb->expects($this->any())->method('select')->willReturnSelf();
        $mockQb->expects($this->any())->method('from')->willReturnSelf();
        $mockQb->expects($this->any())->method('innerJoin')->willReturnSelf();
        $mockQb->expects($this->any())->method('orderBy')->willReturnSelf();

        $mockQuery = $this->createMock(\Doctrine\ORM\Query::class);
        $mockQuery
            ->expects($this->once())
            ->method('getResult')
            ->willReturn([
                ['id' => 1, 'nome' => 'João Silva'],
                ['id' => 2, 'nome' => 'Maria Santos'],
            ]);

        $mockQb->expects($this->any())->method('getQuery')->willReturn($mockQuery);

        $this->em
            ->expects($this->any())
            ->method('createQueryBuilder')
            ->willReturn($mockQb);

        $resultado = $this->service->listarProprietarios();

        $this->assertIsArray($resultado);
        $this->assertCount(2, $resultado);
    }

    public function testListarAnosDisponiveis(): void
    {
        $this->lancamentosRepo
            ->expects($this->once())
            ->method('findAnosComLancamentos')
            ->willReturn([2025, 2024]);

        $this->informesRepo
            ->expects($this->once())
            ->method('findAnosComInformes')
            ->willReturn([2025, 2023]);

        $resultado = $this->service->listarAnosDisponiveis();

        $this->assertIsArray($resultado);
        $this->assertContains(2025, $resultado);
        $this->assertContains(2024, $resultado);
        $this->assertContains(2023, $resultado);
    }

    public function testListarAnosDisponiveisEmpty(): void
    {
        $this->lancamentosRepo
            ->expects($this->once())
            ->method('findAnosComLancamentos')
            ->willReturn([]);

        $this->informesRepo
            ->expects($this->once())
            ->method('findAnosComInformes')
            ->willReturn([]);

        $resultado = $this->service->listarAnosDisponiveis();

        $this->assertIsArray($resultado);
        // Should return current year and previous year
        $anoAtual = (int) date('Y');
        $this->assertContains($anoAtual, $resultado);
    }

    public function testAtualizarInformeNotFound(): void
    {
        $this->informesRepo
            ->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Informe não encontrado');

        $this->service->atualizarInforme(999, []);
    }

    public function testGerarDadosPdfModelo1(): void
    {
        $this->informesRepo
            ->expects($this->once())
            ->method('findByFiltros')
            ->with(['ano' => 2025])
            ->willReturn([]);

        $resultado = $this->service->gerarDadosPdfModelo1(2025, null, false);

        $this->assertIsArray($resultado);
    }

    public function testGerarDadosPdfModelo1ComProprietario(): void
    {
        $this->informesRepo
            ->expects($this->once())
            ->method('findByFiltros')
            ->with(['ano' => 2025, 'idProprietario' => 1])
            ->willReturn([]);

        $resultado = $this->service->gerarDadosPdfModelo1(2025, 1, false);

        $this->assertIsArray($resultado);
    }

    public function testGerarDadosPdfModelo2(): void
    {
        $this->informesRepo
            ->expects($this->any())
            ->method('findByFiltros')
            ->willReturn([]);

        $resultado = $this->service->gerarDadosPdfModelo2(2025, null, false);

        $this->assertIsArray($resultado);
    }
}
