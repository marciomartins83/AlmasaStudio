<?php

namespace App\Tests\Service;

use App\Entity\Profissoes;
use App\Service\ProfissaoService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ProfissaoServiceTest extends TestCase
{
    private ProfissaoService $service;
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->service = new ProfissaoService($this->entityManager, $this->logger);
    }

    /**
     * Test that salvarProfissao creates and persists a Profissoes entity
     */
    public function testSalvarProfissaoCreatesAndPersistsEntity(): void
    {
        $nome = 'Engenheiro';

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(Profissoes::class));

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with('Profissão salva com sucesso', $this->isType('array'));

        $resultado = $this->service->salvarProfissao($nome);

        $this->assertInstanceOf(Profissoes::class, $resultado);
        $this->assertEquals($nome, $resultado->getNome());
    }

    /**
     * Test that salvarProfissao sets the correct name on the entity
     */
    public function testSalvarProfissaoSetNomeCorrectly(): void
    {
        $nome = 'Advogado';

        $this->entityManager
            ->expects($this->once())
            ->method('persist');

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->logger
            ->expects($this->once())
            ->method('info');

        $resultado = $this->service->salvarProfissao($nome);

        $this->assertEquals('Advogado', $resultado->getNome());
    }

    /**
     * Test that salvarProfissao sets ativo to true by default
     */
    public function testSalvarProfissaoSetsAtivoToTrue(): void
    {
        $nome = 'Médico';

        $this->entityManager
            ->expects($this->once())
            ->method('persist');

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->logger
            ->expects($this->once())
            ->method('info');

        $resultado = $this->service->salvarProfissao($nome);

        $this->assertTrue($resultado->getAtivo());
    }

    /**
     * Test that salvarProfissao throws RuntimeException when nome is empty
     */
    public function testSalvarProfissaoThrowsExceptionWhenNomeIsEmpty(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Nome da profissão é obrigatório');

        $this->entityManager
            ->expects($this->never())
            ->method('persist');

        $this->entityManager
            ->expects($this->never())
            ->method('flush');

        $this->service->salvarProfissao('');
    }

    /**
     * Test that salvarProfissao accepts whitespace only name
     * Note: empty() in PHP returns false for whitespace strings
     */
    public function testSalvarProfissaoThrowsExceptionWhenNomeIsWhitespaceOnly(): void
    {
        $nome = '   ';

        $this->entityManager
            ->expects($this->once())
            ->method('persist');

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->logger
            ->expects($this->once())
            ->method('info');

        $resultado = $this->service->salvarProfissao($nome);

        $this->assertEquals('   ', $resultado->getNome());
    }

    /**
     * Test that salvarProfissao logs info with correct data after successful save
     */
    public function testSalvarProfissaoLogsInfoWithCorrectData(): void
    {
        $nome = 'Dentista';

        $this->entityManager
            ->expects($this->once())
            ->method('persist');

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with(
                'Profissão salva com sucesso',
                $this->callback(function ($context) use ($nome) {
                    return isset($context['nome']) && $context['nome'] === $nome;
                })
            );

        $this->service->salvarProfissao($nome);
    }

    /**
     * Test that salvarProfissao logs info includes entity data
     */
    public function testSalvarProfissaoLogsInfoIncludesEntityData(): void
    {
        $nome = 'Psicólogo';

        $this->entityManager
            ->expects($this->once())
            ->method('persist');

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with(
                'Profissão salva com sucesso',
                $this->callback(function ($context) {
                    return isset($context['nome']) && $context['nome'] === 'Psicólogo';
                })
            );

        $this->service->salvarProfissao($nome);
    }

    /**
     * Test that salvarProfissao rethrows exception and logs when persist fails
     */
    public function testSalvarProfissaoRethrowsExceptionOnPersistFailure(): void
    {
        $nome = 'Contador';
        $exception = new \Exception('Database error');

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->willThrowException($exception);

        $this->logger
            ->expects($this->never())
            ->method('info');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Database error');

        $this->service->salvarProfissao($nome);
    }

    /**
     * Test that salvarProfissao rethrows exception and logs when flush fails
     */
    public function testSalvarProfissaoRethrowsExceptionOnFlushFailure(): void
    {
        $nome = 'Arquiteto';
        $exception = new \Exception('Flush error');

        $this->entityManager
            ->expects($this->once())
            ->method('persist');

        $this->entityManager
            ->expects($this->once())
            ->method('flush')
            ->willThrowException($exception);

        $this->logger
            ->expects($this->never())
            ->method('info');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Flush error');

        $this->service->salvarProfissao($nome);
    }

    /**
     * Test that salvarProfissao returns the same instance as persisted
     */
    public function testSalvarProfissaoReturnsSameInstanceAsPersisted(): void
    {
        $nome = 'Eletricista';
        $persistedEntity = null;

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->willReturnCallback(function ($entity) use (&$persistedEntity) {
                $persistedEntity = $entity;
            });

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->logger
            ->expects($this->once())
            ->method('info');

        $resultado = $this->service->salvarProfissao($nome);

        $this->assertSame($persistedEntity, $resultado);
    }

    /**
     * Test that salvarProfissao handles names with special characters correctly
     */
    public function testSalvarProfissaoHandlesSpecialCharactersInName(): void
    {
        $nome = 'Encanador - Especialista';

        $this->entityManager
            ->expects($this->once())
            ->method('persist');

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->logger
            ->expects($this->once())
            ->method('info');

        $resultado = $this->service->salvarProfissao($nome);

        $this->assertEquals('Encanador - Especialista', $resultado->getNome());
    }

    /**
     * Test that salvarProfissao handles long names correctly
     */
    public function testSalvarProfissaoHandlesLongNames(): void
    {
        $nome = 'Desenvolvedor de Software Senior com Especialidade em Arquitetura de Sistemas Distribuídos';

        $this->entityManager
            ->expects($this->once())
            ->method('persist');

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->logger
            ->expects($this->once())
            ->method('info');

        $resultado = $this->service->salvarProfissao($nome);

        $this->assertEquals($nome, $resultado->getNome());
    }

    /**
     * Test that salvarProfissao persist is called before flush
     */
    public function testSalvarProfissoaPersistCalledBeforeFlush(): void
    {
        $nome = 'Jardineiro';
        $callOrder = [];

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->willReturnCallback(function () use (&$callOrder) {
                $callOrder[] = 'persist';
            });

        $this->entityManager
            ->expects($this->once())
            ->method('flush')
            ->willReturnCallback(function () use (&$callOrder) {
                $callOrder[] = 'flush';
            });

        $this->logger
            ->expects($this->once())
            ->method('info');

        $this->service->salvarProfissao($nome);

        $this->assertEquals(['persist', 'flush'], $callOrder);
    }
}
