<?php

namespace App\Tests\Service;

use App\Entity\Pessoas;
use App\Repository\PessoaRepository;
use App\Service\PessoaService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class PessoaServiceTest extends TestCase
{
    private PessoaService $service;
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;
    private PessoaRepository $pessoaRepository;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->pessoaRepository = $this->createMock(PessoaRepository::class);

        $this->service = new PessoaService(
            $this->entityManager,
            $this->logger,
            $this->pessoaRepository
        );
    }

    /**
     * Test excluirEndereco removes endereco successfully
     */
    public function testExcluirEnderecoRemovesEntity(): void
    {
        $endereco = $this->createMock(\App\Entity\Enderecos::class);

        $this->entityManager
            ->method('getRepository')
            ->willReturnCallback(function ($class) use ($endereco) {
                $repo = $this->createMock(\Doctrine\ORM\EntityRepository::class);
                $repo->method('find')->willReturn($endereco);
                return $repo;
            });

        $this->entityManager
            ->expects($this->once())
            ->method('remove')
            ->with($endereco);

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->service->excluirEndereco(1);
    }

    /**
     * Test excluirEndereco throws exception when not found
     */
    public function testExcluirEnderecoThrowsExceptionWhenNotFound(): void
    {
        $this->entityManager
            ->method('getRepository')
            ->willReturnCallback(function ($class) {
                $repo = $this->createMock(\Doctrine\ORM\EntityRepository::class);
                $repo->method('find')->willReturn(null);
                return $repo;
            });

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Endereço não encontrado');

        $this->service->excluirEndereco(999);
    }

    /**
     * Test excluirTelefone throws exception when not found
     */
    public function testExcluirTelefoneThrowsExceptionWhenNotFound(): void
    {
        $this->entityManager
            ->method('getRepository')
            ->willReturnCallback(function ($class) {
                $repo = $this->createMock(\Doctrine\ORM\EntityRepository::class);
                $repo->method('find')->willReturn(null);
                return $repo;
            });

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Telefone não encontrado');

        $this->service->excluirTelefone(999);
    }

    /**
     * Test excluirEmail throws exception when not found
     */
    public function testExcluirEmailThrowsExceptionWhenNotFound(): void
    {
        $this->entityManager
            ->method('getRepository')
            ->willReturnCallback(function ($class) {
                $repo = $this->createMock(\Doctrine\ORM\EntityRepository::class);
                $repo->method('find')->willReturn(null);
                return $repo;
            });

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Email não encontrado');

        $this->service->excluirEmail(999);
    }

    /**
     * Test buscaPorNome returns pessoa when found
     */
    public function testBuscaPorNomeReturnsPessoaWhenFound(): void
    {
        $pessoa = $this->createMock(Pessoas::class);
        $pessoa->method('getNome')->willReturn('João Silva');

        $this->pessoaRepository
            ->method('findByNome')
            ->with('João Silva')
            ->willReturn([$pessoa]);

        $resultado = $this->service->buscaPorNome('João Silva', null, null);

        $this->assertInstanceOf(Pessoas::class, $resultado);
        $this->assertEquals('João Silva', $resultado->getNome());
    }

    /**
     * Test buscaPorNome returns null when not found
     */
    public function testBuscaPorNomeReturnsNullWhenNotFound(): void
    {
        $this->pessoaRepository
            ->method('findByNome')
            ->with('Unknown')
            ->willReturn([]);

        $resultado = $this->service->buscaPorNome('Unknown', null, null);

        $this->assertNull($resultado);
    }

    /**
     * Test buscaPorNome throws exception when multiple pessoas found
     */
    public function testBuscaPorNomeThrowsExceptionWhenMultiplePessoasFound(): void
    {
        $pessoa1 = $this->createMock(Pessoas::class);
        $pessoa2 = $this->createMock(Pessoas::class);

        $this->pessoaRepository
            ->method('findByNome')
            ->with('Silva')
            ->willReturn([$pessoa1, $pessoa2]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Múltiplas pessoas encontradas');

        $this->service->buscaPorNome('Silva', null, null);
    }

    /**
     * Test excluirDocumento removes documento successfully
     */
    public function testExcluirDocumentoRemovesEntity(): void
    {
        $documento = $this->createMock(\App\Entity\PessoasDocumentos::class);

        $this->entityManager
            ->method('getRepository')
            ->willReturnCallback(function ($class) use ($documento) {
                $repo = $this->createMock(\Doctrine\ORM\EntityRepository::class);
                $repo->method('find')->willReturn($documento);
                return $repo;
            });

        $this->entityManager
            ->expects($this->once())
            ->method('remove')
            ->with($documento);

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->service->excluirDocumento(1);
    }

    /**
     * Test excluirDocumento throws exception when not found
     */
    public function testExcluirDocumentoThrowsExceptionWhenNotFound(): void
    {
        $this->entityManager
            ->method('getRepository')
            ->willReturnCallback(function ($class) {
                $repo = $this->createMock(\Doctrine\ORM\EntityRepository::class);
                $repo->method('find')->willReturn(null);
                return $repo;
            });

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Documento não encontrado');

        $this->service->excluirDocumento(999);
    }

    /**
     * Test excluirProfissao removes profissao successfully
     */
    public function testExcluirProfissaoRemovesEntity(): void
    {
        $profissao = $this->createMock(\App\Entity\PessoasProfissoes::class);

        $this->entityManager
            ->method('getRepository')
            ->willReturnCallback(function ($class) use ($profissao) {
                $repo = $this->createMock(\Doctrine\ORM\EntityRepository::class);
                $repo->method('find')->willReturn($profissao);
                return $repo;
            });

        $this->entityManager
            ->expects($this->once())
            ->method('remove')
            ->with($profissao);

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->service->excluirProfissao(1);
    }

    /**
     * Test excluirChavePix removes chave pix successfully
     */
    public function testExcluirChavePixRemovesEntity(): void
    {
        $chavePix = $this->createMock(\App\Entity\ChavesPix::class);

        $this->entityManager
            ->method('getRepository')
            ->willReturnCallback(function ($class) use ($chavePix) {
                $repo = $this->createMock(\Doctrine\ORM\EntityRepository::class);
                $repo->method('find')->willReturn($chavePix);
                return $repo;
            });

        $this->entityManager
            ->expects($this->once())
            ->method('remove')
            ->with($chavePix);

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->service->excluirChavePix(1);
    }

    /**
     * Test excluirContaBancaria removes conta bancaria successfully
     */
    public function testExcluirContaBancariaRemovesEntity(): void
    {
        $conta = $this->createMock(\App\Entity\ContasBancarias::class);

        $this->entityManager
            ->method('getRepository')
            ->willReturnCallback(function ($class) use ($conta) {
                $repo = $this->createMock(\Doctrine\ORM\EntityRepository::class);
                $repo->method('find')->willReturn($conta);
                return $repo;
            });

        $this->entityManager
            ->expects($this->once())
            ->method('remove')
            ->with($conta);

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->service->excluirContaBancaria(1);
    }

    /**
     * Test buscarConjugePorCriterio returns array when found
     */
    public function testBuscarConjugePorCriterioByCpfReturnsArray(): void
    {
        $pessoa = $this->createMock(Pessoas::class);
        $pessoa->method('getFisicaJuridica')->willReturn('fisica');
        $pessoa->method('getIdpessoa')->willReturn(1);

        $this->pessoaRepository
            ->method('findByCpfDocumento')
            ->with('12345678900')
            ->willReturn($pessoa);

        $resultado = $this->service->buscarConjugePorCriterio('cpf', '12345678900');

        $this->assertIsArray($resultado);
        $this->assertCount(1, $resultado);
    }

    /**
     * Test buscarConjugePorCriterio excludes pessoa when pessoaIdExcluir matches
     */
    public function testBuscarConjugePorCriterioExcludesPessoa(): void
    {
        $pessoa = $this->createMock(Pessoas::class);
        $pessoa->method('getFisicaJuridica')->willReturn('fisica');
        $pessoa->method('getIdpessoa')->willReturn(1);

        $this->pessoaRepository
            ->method('findByCpfDocumento')
            ->with('12345678900')
            ->willReturn($pessoa);

        $resultado = $this->service->buscarConjugePorCriterio('cpf', '12345678900', 1);

        $this->assertIsArray($resultado);
        $this->assertCount(0, $resultado);
    }

    /**
     * Test salvarBanco creates new banco
     */
    public function testSalvarBancoCriaNewBanco(): void
    {
        $this->entityManager
            ->expects($this->once())
            ->method('beginTransaction');

        $this->entityManager
            ->expects($this->once())
            ->method('commit');

        $this->entityManager
            ->method('getRepository')
            ->willReturnCallback(function ($class) {
                $repo = $this->createMock(\Doctrine\ORM\EntityRepository::class);
                $repo->method('findOneBy')->willReturn(null);
                return $repo;
            });

        $this->entityManager
            ->expects($this->once())
            ->method('persist');

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->logger
            ->expects($this->never())
            ->method('error');

        $resultado = $this->service->salvarBanco('Banco Test', 123);

        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('id', $resultado);
    }

    /**
     * Test salvarBanco throws exception when already exists
     */
    public function testSalvarBancoThrowsExceptionWhenAlreadyExists(): void
    {
        $bancoExistente = $this->createMock(\App\Entity\Bancos::class);

        $this->entityManager
            ->method('getRepository')
            ->willReturnCallback(function ($class) use ($bancoExistente) {
                $repo = $this->createMock(\Doctrine\ORM\EntityRepository::class);
                $repo->method('findOneBy')->willReturn($bancoExistente);
                return $repo;
            });

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Já existe um banco com este número');

        $this->service->salvarBanco('Banco Test', 123);
    }
}
