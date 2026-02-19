<?php

namespace App\Tests\Service;

use App\Entity\ImoveisContratos;
use App\Entity\Imoveis;
use App\Entity\Pessoas;
use App\Entity\Enderecos;
use App\Entity\TiposImoveis;
use App\Entity\Logradouros;
use App\Entity\Bairros;
use App\Entity\Cidades;
use App\Entity\Estados;
use App\Repository\ImoveisContratosRepository;
use App\Repository\ImoveisRepository;
use App\Repository\PessoaRepository;
use App\Service\ContratoService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ContratoServiceTest extends TestCase
{
    private ContratoService $service;
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;
    private ImoveisContratosRepository $contratosRepository;
    private ImoveisRepository $imoveisRepository;
    private PessoaRepository $pessoaRepository;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->contratosRepository = $this->createMock(ImoveisContratosRepository::class);
        $this->imoveisRepository = $this->createMock(ImoveisRepository::class);
        $this->pessoaRepository = $this->createMock(PessoaRepository::class);

        $this->service = new ContratoService(
            $this->entityManager,
            $this->logger,
            $this->contratosRepository,
            $this->imoveisRepository,
            $this->pessoaRepository
        );
    }

    /**
     * Test listarContratosEnriquecidos returns array with contrato data
     */
    public function testListarContratosEnriquecidosReturnsArray(): void
    {
        $contrato = $this->createMock(ImoveisContratos::class);
        $contrato->method('getId')->willReturn(1);
        $contrato->method('getTipoContrato')->willReturn('locacao');
        $contrato->method('getStatus')->willReturn('ativo');
        $contrato->method('isVigente')->willReturn(true);
        $contrato->method('isAtivo')->willReturn(true);
        $contrato->method('getObservacoes')->willReturn('Contrato padrão');

        $imovel = $this->createMock(Imoveis::class);
        $imovel->method('getId')->willReturn(1);
        $imovel->method('getCodigoInterno')->willReturn('APT-001');
        $imovel->method('getValorAluguel')->willReturn('1500.00');

        $endereco = $this->createMock(Enderecos::class);
        $endereco->method('getEndNumero')->willReturn(123);
        $endereco->method('getComplemento')->willReturn(null);

        $estado = $this->createMock(Estados::class);
        $estado->method('getUf')->willReturn('SP');

        $cidade = $this->createMock(Cidades::class);
        $cidade->method('getNome')->willReturn('São Paulo');
        $cidade->method('getEstado')->willReturn($estado);

        $bairro = $this->createMock(Bairros::class);
        $bairro->method('getNome')->willReturn('Centro');
        $bairro->method('getCidade')->willReturn($cidade);

        $logradouro = $this->createMock(Logradouros::class);
        $logradouro->method('getLogradouro')->willReturn('Rua Principal');
        $logradouro->method('getBairro')->willReturn($bairro);

        $endereco->method('getLogradouro')->willReturn($logradouro);
        $imovel->method('getEndereco')->willReturn($endereco);

        $tipo = $this->createMock(TiposImoveis::class);
        $tipo->method('getTipo')->willReturn('Apartamento');
        $imovel->method('getTipoImovel')->willReturn($tipo);

        $contrato->method('getImovel')->willReturn($imovel);
        $contrato->method('getPessoaLocatario')->willReturn(null);
        $contrato->method('getPessoaFiador')->willReturn(null);

        $dataInicio = new \DateTime('2024-01-01');
        $dataFim = new \DateTime('2025-01-01');
        $contrato->method('getDataInicio')->willReturn($dataInicio);
        $contrato->method('getDataFim')->willReturn($dataFim);
        $contrato->method('getDuracaoMeses')->willReturn(12);
        $contrato->method('getValorContrato')->willReturn('1500.00');
        $contrato->method('getValorLiquidoProprietario')->willReturn(1500.00);
        $contrato->method('getDiaVencimento')->willReturn(10);
        $contrato->method('getTaxaAdministracao')->willReturn('10.00');
        $contrato->method('getTipoGarantia')->willReturn('fiador');
        $contrato->method('getValorCaucao')->willReturn(null);
        $contrato->method('getIndiceReajuste')->willReturn('IGPM');
        $contrato->method('getPeriodicidadeReajuste')->willReturn('anual');
        $contrato->method('getDataProximoReajuste')->willReturn(new \DateTime('2025-01-01'));
        $contrato->method('getMultaRescisao')->willReturn('3000.00');
        $contrato->method('getCarenciaDias')->willReturn(0);
        $contrato->method('isGeraBoleto')->willReturn(true);
        $contrato->method('isEnviaEmail')->willReturn(true);
        $contrato->method('getCreatedAt')->willReturn(new \DateTime());
        $contrato->method('getUpdatedAt')->willReturn(new \DateTime());

        $this->contratosRepository
            ->method('findByFiltros')
            ->with([])
            ->willReturn([$contrato]);

        $resultado = $this->service->listarContratosEnriquecidos([]);

        $this->assertIsArray($resultado);
        $this->assertCount(1, $resultado);
        $this->assertEquals(1, $resultado[0]['id']);
        $this->assertEquals('APT-001', $resultado[0]['imovel_codigo']);
        $this->assertEquals(1500.00, $resultado[0]['valor_contrato']);
    }

    /**
     * Test buscarContratoPorId returns contrato data
     */
    public function testBuscarContratoPorIdReturnsData(): void
    {
        $contrato = $this->createMock(ImoveisContratos::class);
        $contrato->method('getId')->willReturn(1);
        $contrato->method('getTipoContrato')->willReturn('locacao');
        $contrato->method('getStatus')->willReturn('ativo');
        $contrato->method('isVigente')->willReturn(true);
        $contrato->method('isAtivo')->willReturn(true);
        $contrato->method('getObservacoes')->willReturn(null);

        $imovel = $this->createMock(Imoveis::class);
        $imovel->method('getId')->willReturn(1);
        $imovel->method('getCodigoInterno')->willReturn('APT-001');

        $tipo = $this->createMock(TiposImoveis::class);
        $imovel->method('getTipoImovel')->willReturn($tipo);

        $imovel->method('getValorAluguel')->willReturn('1500.00');

        $endereco = $this->createMock(Enderecos::class);
        $endereco->method('getEndNumero')->willReturn(123);
        $endereco->method('getComplemento')->willReturn(null);

        $estado = $this->createMock(Estados::class);
        $estado->method('getUf')->willReturn('SP');

        $cidade = $this->createMock(Cidades::class);
        $cidade->method('getNome')->willReturn('São Paulo');
        $cidade->method('getEstado')->willReturn($estado);

        $bairro = $this->createMock(Bairros::class);
        $bairro->method('getNome')->willReturn('Centro');
        $bairro->method('getCidade')->willReturn($cidade);

        $logradouro = $this->createMock(Logradouros::class);
        $logradouro->method('getLogradouro')->willReturn('Rua Principal');
        $logradouro->method('getBairro')->willReturn($bairro);

        $endereco->method('getLogradouro')->willReturn($logradouro);
        $imovel->method('getEndereco')->willReturn($endereco);

        $contrato->method('getImovel')->willReturn($imovel);
        $contrato->method('getPessoaLocatario')->willReturn(null);
        $contrato->method('getPessoaFiador')->willReturn(null);

        $contrato->method('getDataInicio')->willReturn(new \DateTime('2024-01-01'));
        $contrato->method('getDataFim')->willReturn(new \DateTime('2025-01-01'));
        $contrato->method('getDuracaoMeses')->willReturn(12);
        $contrato->method('getValorContrato')->willReturn('1500.00');
        $contrato->method('getValorLiquidoProprietario')->willReturn(1500.00);
        $contrato->method('getDiaVencimento')->willReturn(10);
        $contrato->method('getTaxaAdministracao')->willReturn('10.00');
        $contrato->method('getTipoGarantia')->willReturn('fiador');
        $contrato->method('getValorCaucao')->willReturn(null);
        $contrato->method('getIndiceReajuste')->willReturn('IGPM');
        $contrato->method('getPeriodicidadeReajuste')->willReturn('anual');
        $contrato->method('getDataProximoReajuste')->willReturn(new \DateTime('2025-01-01'));
        $contrato->method('getMultaRescisao')->willReturn('3000.00');
        $contrato->method('getCarenciaDias')->willReturn(0);
        $contrato->method('isGeraBoleto')->willReturn(true);
        $contrato->method('isEnviaEmail')->willReturn(true);
        $contrato->method('getCreatedAt')->willReturn(new \DateTime());
        $contrato->method('getUpdatedAt')->willReturn(new \DateTime());

        $this->contratosRepository
            ->method('find')
            ->with(1)
            ->willReturn($contrato);

        $resultado = $this->service->buscarContratoPorId(1);

        $this->assertIsArray($resultado);
        $this->assertEquals(1, $resultado['id']);
    }

    /**
     * Test buscarContratoPorId returns null when not found
     */
    public function testBuscarContratoPorIdReturnsNullWhenNotFound(): void
    {
        $this->contratosRepository
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $resultado = $this->service->buscarContratoPorId(999);

        $this->assertNull($resultado);
    }

    /**
     * Test obterEstatisticas calls repository method
     */
    public function testObterEstatisticasCallsRepository(): void
    {
        $stats = [
            'total_contratos' => 10,
            'contratos_ativos' => 8,
            'contratos_encerrados' => 2,
            'valor_total' => 15000.00
        ];

        $this->contratosRepository
            ->method('getEstatisticas')
            ->willReturn($stats);

        $resultado = $this->service->obterEstatisticas();

        $this->assertIsArray($resultado);
        $this->assertEquals(10, $resultado['total_contratos']);
        $this->assertEquals(8, $resultado['contratos_ativos']);
    }

    /**
     * Test listarImoveisDisponiveis returns array of available imoveis
     */
    public function testListarImoveisDisponiveisReturnsArray(): void
    {
        $imovel1 = $this->createMock(Imoveis::class);
        $imovel1->method('getId')->willReturn(1);
        $imovel1->method('getCodigoInterno')->willReturn('APT-001');
        $imovel1->method('getValorAluguel')->willReturn('1500.00');

        $endereco1 = $this->createMock(Enderecos::class);
        $endereco1->method('getEndNumero')->willReturn(123);
        $endereco1->method('getComplemento')->willReturn(null);

        $estado = $this->createMock(Estados::class);
        $estado->method('getUf')->willReturn('SP');

        $cidade = $this->createMock(Cidades::class);
        $cidade->method('getNome')->willReturn('São Paulo');
        $cidade->method('getEstado')->willReturn($estado);

        $bairro = $this->createMock(Bairros::class);
        $bairro->method('getNome')->willReturn('Centro');
        $bairro->method('getCidade')->willReturn($cidade);

        $logradouro = $this->createMock(Logradouros::class);
        $logradouro->method('getLogradouro')->willReturn('Rua Principal');
        $logradouro->method('getBairro')->willReturn($bairro);

        $endereco1->method('getLogradouro')->willReturn($logradouro);
        $imovel1->method('getEndereco')->willReturn($endereco1);

        $tipo = $this->createMock(TiposImoveis::class);
        $tipo->method('getTipo')->willReturn('Apartamento');
        $imovel1->method('getTipoImovel')->willReturn($tipo);

        $imovel2 = $this->createMock(Imoveis::class);
        $imovel2->method('getId')->willReturn(2);
        $imovel2->method('getCodigoInterno')->willReturn('APT-002');
        $imovel2->method('getValorAluguel')->willReturn('2000.00');

        $endereco2 = $this->createMock(Enderecos::class);
        $endereco2->method('getEndNumero')->willReturn(456);
        $endereco2->method('getComplemento')->willReturn(null);
        $endereco2->method('getLogradouro')->willReturn($logradouro);
        $imovel2->method('getEndereco')->willReturn($endereco2);
        $imovel2->method('getTipoImovel')->willReturn($tipo);

        $this->imoveisRepository
            ->method('findAll')
            ->willReturn([$imovel1, $imovel2]);

        $this->contratosRepository
            ->method('findContratoVigenteImovel')
            ->willReturnMap([
                [1, null], // imovel1 has no vigente contract
                [2, $this->createMock(ImoveisContratos::class)] // imovel2 has vigente contract
            ]);

        $resultado = $this->service->listarImoveisDisponiveis();

        $this->assertIsArray($resultado);
        $this->assertCount(1, $resultado);
        $this->assertEquals('APT-001', $resultado[0]['codigo_interno']);
    }

    /**
     * Test salvarContrato throws exception when contrato vigente already exists
     */
    public function testSalvarContratoThrowsExceptionWhenContratoVigenteExists(): void
    {
        $contrato = $this->createMock(ImoveisContratos::class);
        $contratoVigente = $this->createMock(ImoveisContratos::class);

        $connection = $this->createMock(\Doctrine\DBAL\Connection::class);
        $connection->expects($this->once())->method('beginTransaction');
        $connection->expects($this->once())->method('rollBack');

        $this->entityManager
            ->method('getConnection')
            ->willReturn($connection);

        $this->contratosRepository
            ->method('findContratoVigenteImovel')
            ->with(1)
            ->willReturn($contratoVigente);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Já existe um contrato vigente');

        $this->service->salvarContrato($contrato, ['imovel_id' => 1]);
    }

    /**
     * Test encerrarContrato changes status to encerrado
     */
    public function testEncerrarContratoChangesStatus(): void
    {
        $contrato = $this->createMock(ImoveisContratos::class);
        $contrato->method('getStatus')->willReturn('ativo');
        $contrato->method('getObservacoes')->willReturn(null);

        $contrato->expects($this->once())->method('setStatus')->with('encerrado');
        $contrato->expects($this->once())->method('setAtivo')->with(false);

        $connection = $this->createMock(\Doctrine\DBAL\Connection::class);
        $connection->expects($this->once())->method('beginTransaction');
        $connection->expects($this->once())->method('commit');

        $this->entityManager
            ->method('getConnection')
            ->willReturn($connection);

        $this->contratosRepository
            ->method('find')
            ->with(1)
            ->willReturn($contrato);

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with('Contrato encerrado', $this->isType('array'));

        $this->service->encerrarContrato(1, new \DateTime());
    }

    /**
     * Test encerrarContrato throws exception when status is not ativo
     */
    public function testEncerrarContratoThrowsExceptionWhenNotAtivo(): void
    {
        $contrato = $this->createMock(ImoveisContratos::class);
        $contrato->method('getStatus')->willReturn('encerrado');

        $connection = $this->createMock(\Doctrine\DBAL\Connection::class);
        $connection->expects($this->once())->method('beginTransaction');
        $connection->expects($this->once())->method('rollBack');

        $this->entityManager
            ->method('getConnection')
            ->willReturn($connection);

        $this->contratosRepository
            ->method('find')
            ->with(1)
            ->willReturn($contrato);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Apenas contratos ativos podem ser encerrados');

        $this->service->encerrarContrato(1, new \DateTime());
    }

    /**
     * Test renovarContrato creates new contrato and marks old as encerrado
     */
    public function testRenovarContratoCreatesNewContrato(): void
    {
        $contratoAntigo = $this->createMock(ImoveisContratos::class);
        $contratoAntigo->method('getId')->willReturn(1);
        $contratoAntigo->method('getImovel')->willReturn($this->createMock(Imoveis::class));
        $contratoAntigo->method('getPessoaLocatario')->willReturn(null);
        $contratoAntigo->method('getPessoaFiador')->willReturn(null);
        $contratoAntigo->method('getTipoContrato')->willReturn('locacao');
        $contratoAntigo->method('getDiaVencimento')->willReturn(10);
        $contratoAntigo->method('getTaxaAdministracao')->willReturn('10.00');
        $contratoAntigo->method('getTipoGarantia')->willReturn('fiador');
        $contratoAntigo->method('getIndiceReajuste')->willReturn('IGPM');
        $contratoAntigo->method('getPeriodicidadeReajuste')->willReturn('anual');
        $contratoAntigo->method('getMultaRescisao')->willReturn('3000.00');
        $contratoAntigo->method('getCarenciaDias')->willReturn(0);
        $contratoAntigo->method('isGeraBoleto')->willReturn(true);
        $contratoAntigo->method('isEnviaEmail')->willReturn(true);

        $connection = $this->createMock(\Doctrine\DBAL\Connection::class);
        $connection->expects($this->once())->method('beginTransaction');
        $connection->expects($this->once())->method('commit');

        $this->entityManager
            ->method('getConnection')
            ->willReturn($connection);

        $this->contratosRepository
            ->method('find')
            ->with(1)
            ->willReturn($contratoAntigo);

        $this->entityManager
            ->expects($this->once())
            ->method('persist');

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->logger
            ->expects($this->once())
            ->method('info');

        $resultado = $this->service->renovarContrato(1, ['data_inicio' => '2025-01-01']);

        $this->assertInstanceOf(ImoveisContratos::class, $resultado);
    }
}
