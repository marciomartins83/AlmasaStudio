<?php

namespace App\Tests\Service;

use App\Entity\Imoveis;
use App\Entity\ImoveisFotos;
use App\Entity\ImoveisMedidores;
use App\Entity\ImoveisPropriedades;
use App\Entity\ImoveisGarantias;
use App\Entity\PropriedadesCatalogo;
use App\Entity\TiposImoveis;
use App\Repository\ImoveisRepository;
use App\Repository\PropriedadesCatalogoRepository;
use App\Repository\TiposImoveisRepository;
use App\Repository\PessoaRepository;
use App\Service\ImovelService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ImovelServiceTest extends TestCase
{
    private ImovelService $service;
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;
    private ImoveisRepository $imoveisRepository;
    private PropriedadesCatalogoRepository $propriedadesCatalogoRepository;
    private TiposImoveisRepository $tiposImoveisRepository;
    private PessoaRepository $pessoaRepository;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->imoveisRepository = $this->createMock(ImoveisRepository::class);
        $this->propriedadesCatalogoRepository = $this->createMock(PropriedadesCatalogoRepository::class);
        $this->tiposImoveisRepository = $this->createMock(TiposImoveisRepository::class);
        $this->pessoaRepository = $this->createMock(PessoaRepository::class);

        $this->service = new ImovelService(
            $this->entityManager,
            $this->logger,
            $this->imoveisRepository,
            $this->propriedadesCatalogoRepository,
            $this->tiposImoveisRepository,
            $this->pessoaRepository
        );
    }

    /**
     * Test listarImoveisEnriquecidos returns array with imovel data
     */
    public function testListarImoveisEnriquecidosReturnsArray(): void
    {
        $imovel = $this->createMock(Imoveis::class);
        $imovel->method('getId')->willReturn(1);
        $imovel->method('getCodigoInterno')->willReturn('APT-001');
        $imovel->method('getSituacao')->willReturn('disponivel');
        $imovel->method('getValorAluguel')->willReturn('1500.00');
        $imovel->method('getValorVenda')->willReturn('300000.00');
        $imovel->method('isAluguelGarantido')->willReturn(false);
        $imovel->method('isDisponivelVenda')->willReturn(true);
        $imovel->method('getQtdQuartos')->willReturn(2);
        $imovel->method('getQtdBanheiros')->willReturn(1);
        $imovel->method('getAreaTotal')->willReturn('80.00');

        $tipo = $this->createMock(TiposImoveis::class);
        $tipo->method('getTipo')->willReturn('Apartamento');
        $imovel->method('getTipoImovel')->willReturn($tipo);

        $endereco = $this->createMock(\App\Entity\Enderecos::class);
        $imovel->method('getEndereco')->willReturn($endereco);

        $proprietario = $this->createMock(\App\Entity\Pessoas::class);
        $imovel->method('getPessoaProprietario')->willReturn($proprietario);

        $this->imoveisRepository
            ->method('findAll')
            ->willReturn([$imovel]);

        $resultado = $this->service->listarImoveisEnriquecidos();

        $this->assertIsArray($resultado);
        $this->assertCount(1, $resultado);
        $this->assertEquals('APT-001', $resultado[0]['codigo_interno']);
        $this->assertEquals('Apartamento', $resultado[0]['tipo']);
    }

    /**
     * Test carregarDadosCompletos returns array with all data
     */
    public function testCarregarDadosCompletosReturnsArrayWithAllData(): void
    {
        $imovel = $this->createMock(Imoveis::class);
        $imovel->method('getId')->willReturn(1);

        $this->imoveisRepository
            ->method('find')
            ->with(1)
            ->willReturn($imovel);

        $this->entityManager
            ->method('getRepository')
            ->willReturnCallback(function ($class) use ($imovel) {
                $repo = $this->createMock(\Doctrine\ORM\EntityRepository::class);
                $repo->method('findBy')->willReturn([]);
                $repo->method('findOneBy')->willReturn(null);
                return $repo;
            });

        $resultado = $this->service->carregarDadosCompletos(1);

        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('propriedades', $resultado);
        $this->assertArrayHasKey('medidores', $resultado);
        $this->assertArrayHasKey('fotos', $resultado);
        $this->assertArrayHasKey('garantias', $resultado);
    }

    /**
     * Test carregarDadosCompletos throws exception when imovel not found
     */
    public function testCarregarDadosCompletosThrowsExceptionWhenNotFound(): void
    {
        $this->imoveisRepository
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Imóvel não encontrado.');

        $this->service->carregarDadosCompletos(999);
    }

    /**
     * Test buscarPorCodigoInterno returns imovel data when found
     */
    public function testBuscarPorCodigoInternoReturnsDataWhenFound(): void
    {
        $imovel = $this->createMock(Imoveis::class);
        $imovel->method('getId')->willReturn(1);
        $imovel->method('getCodigoInterno')->willReturn('APT-001');
        $imovel->method('getSituacao')->willReturn('disponivel');
        $imovel->method('getValorAluguel')->willReturn('1500.00');
        $imovel->method('getValorVenda')->willReturn('300000.00');
        $imovel->method('getAreaTotal')->willReturn('80.00');
        $imovel->method('getQtdQuartos')->willReturn(2);
        $imovel->method('getQtdBanheiros')->willReturn(1);
        $imovel->method('getDescricao')->willReturn('Apto bem localizado');

        $tipo = $this->createMock(TiposImoveis::class);
        $tipo->method('getId')->willReturn(1);
        $imovel->method('getTipoImovel')->willReturn($tipo);

        $endereco = $this->createMock(\App\Entity\Enderecos::class);
        $imovel->method('getEndereco')->willReturn($endereco);

        $proprietario = $this->createMock(\App\Entity\Pessoas::class);
        $imovel->method('getPessoaProprietario')->willReturn($proprietario);

        $this->imoveisRepository
            ->method('findOneBy')
            ->with(['codigoInterno' => 'APT-001'])
            ->willReturn($imovel);

        $resultado = $this->service->buscarPorCodigoInterno('APT-001');

        $this->assertIsArray($resultado);
        $this->assertEquals('APT-001', $resultado['codigo_interno']);
        $this->assertEquals(1, $resultado['tipo_imovel_id']);
    }

    /**
     * Test buscarPorCodigoInterno returns null when not found
     */
    public function testBuscarPorCodigoInternoReturnsNullWhenNotFound(): void
    {
        $this->imoveisRepository
            ->method('findOneBy')
            ->with(['codigoInterno' => 'UNKNOWN'])
            ->willReturn(null);

        $resultado = $this->service->buscarPorCodigoInterno('UNKNOWN');

        $this->assertNull($resultado);
    }

    /**
     * Test deletarFoto removes foto from imovel
     */
    public function testDeletarFotoRemovesFoto(): void
    {
        $foto = $this->createMock(ImoveisFotos::class);

        $this->entityManager
            ->method('getRepository')
            ->willReturnCallback(function ($class) use ($foto) {
                $repo = $this->createMock(\Doctrine\ORM\EntityRepository::class);
                $repo->method('find')->willReturn($foto);
                return $repo;
            });

        $this->entityManager
            ->expects($this->once())
            ->method('remove')
            ->with($foto);

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with($this->stringContains('FOTO DELETADA'));

        $this->service->deletarFoto(1);
    }

    /**
     * Test deletarFoto throws exception when not found
     */
    public function testDeletarFotoThrowsExceptionWhenNotFound(): void
    {
        $this->entityManager
            ->method('getRepository')
            ->willReturnCallback(function ($class) {
                $repo = $this->createMock(\Doctrine\ORM\EntityRepository::class);
                $repo->method('find')->willReturn(null);
                return $repo;
            });

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Foto não encontrada.');

        $this->service->deletarFoto(999);
    }

    /**
     * Test deletarMedidor removes medidor from imovel
     */
    public function testDeletarMedidorRemovesMedidor(): void
    {
        $medidor = $this->createMock(ImoveisMedidores::class);

        $this->entityManager
            ->method('getRepository')
            ->willReturnCallback(function ($class) use ($medidor) {
                $repo = $this->createMock(\Doctrine\ORM\EntityRepository::class);
                $repo->method('find')->willReturn($medidor);
                return $repo;
            });

        $this->entityManager
            ->expects($this->once())
            ->method('remove')
            ->with($medidor);

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with($this->stringContains('MEDIDOR DELETADO'));

        $this->service->deletarMedidor(1);
    }

    /**
     * Test deletarMedidor throws exception when not found
     */
    public function testDeletarMedidorThrowsExceptionWhenNotFound(): void
    {
        $this->entityManager
            ->method('getRepository')
            ->willReturnCallback(function ($class) {
                $repo = $this->createMock(\Doctrine\ORM\EntityRepository::class);
                $repo->method('find')->willReturn(null);
                return $repo;
            });

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Medidor não encontrado.');

        $this->service->deletarMedidor(999);
    }

    /**
     * Test deletarPropriedade removes propriedade relationship
     */
    public function testDeletarPropriedadeRemovesRelationship(): void
    {
        $propriedade = $this->createMock(ImoveisPropriedades::class);

        $this->entityManager
            ->method('getRepository')
            ->willReturnCallback(function ($class) use ($propriedade) {
                $repo = $this->createMock(\Doctrine\ORM\EntityRepository::class);
                $repo->method('findOneBy')->willReturn($propriedade);
                return $repo;
            });

        $this->entityManager
            ->expects($this->once())
            ->method('remove')
            ->with($propriedade);

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with($this->stringContains('PROPRIEDADE REMOVIDA'));

        $this->service->deletarPropriedade(1, 2);
    }

    /**
     * Test deletarPropriedade throws exception when not found
     */
    public function testDeletarPropriedadeThrowsExceptionWhenNotFound(): void
    {
        $this->entityManager
            ->method('getRepository')
            ->willReturnCallback(function ($class) {
                $repo = $this->createMock(\Doctrine\ORM\EntityRepository::class);
                $repo->method('findOneBy')->willReturn(null);
                return $repo;
            });

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Propriedade não encontrada neste imóvel.');

        $this->service->deletarPropriedade(999, 999);
    }

    /**
     * Test listarPropriedadesCatalogo returns array of active propriedades
     */
    public function testListarPropriedadesCatalogoReturnsArray(): void
    {
        $propriedade = $this->createMock(PropriedadesCatalogo::class);
        $propriedade->method('getId')->willReturn(1);
        $propriedade->method('getNome')->willReturn('Ar Condicionado');
        $propriedade->method('getCategoria')->willReturn('climatizacao');
        $propriedade->method('getIcone')->willReturn('ac-icon');

        $this->propriedadesCatalogoRepository
            ->method('findBy')
            ->with(['ativo' => true])
            ->willReturn([$propriedade]);

        $resultado = $this->service->listarPropriedadesCatalogo();

        $this->assertIsArray($resultado);
        $this->assertCount(1, $resultado);
        $this->assertEquals('Ar Condicionado', $resultado[0]['nome']);
        $this->assertEquals('climatizacao', $resultado[0]['categoria']);
    }
}
