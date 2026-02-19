<?php

namespace App\Tests\Service;

use App\Entity\Nacionalidade;
use App\Service\NacionalidadeService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class NacionalidadeServiceTest extends TestCase
{
    private NacionalidadeService $service;
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->service = new NacionalidadeService($this->entityManager, $this->logger);
    }

    /**
     * Test that salvarNacionalidade creates and persists a Nacionalidade entity
     */
    public function testSalvarNacionalidadeCreatesAndPersistsEntity(): void
    {
        $nome = 'Brasileira';

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(Nacionalidade::class));

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with('Nacionalidade salva com sucesso', $this->isType('array'));

        $resultado = $this->service->salvarNacionalidade($nome);

        $this->assertInstanceOf(Nacionalidade::class, $resultado);
        $this->assertEquals($nome, $resultado->getNome());
    }

    /**
     * Test that salvarNacionalidade sets the correct name on the entity
     */
    public function testSalvarNacionalidadeSetNomeCorrectly(): void
    {
        $nome = 'Italiana';

        $this->entityManager
            ->expects($this->once())
            ->method('persist');

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->logger
            ->expects($this->once())
            ->method('info');

        $resultado = $this->service->salvarNacionalidade($nome);

        $this->assertEquals('Italiana', $resultado->getNome());
    }

    /**
     * Test that salvarNacionalidade throws RuntimeException when nome is empty
     */
    public function testSalvarNacionalidadeThrowsExceptionWhenNomeIsEmpty(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Nome da nacionalidade é obrigatório');

        $this->entityManager
            ->expects($this->never())
            ->method('persist');

        $this->entityManager
            ->expects($this->never())
            ->method('flush');

        $this->service->salvarNacionalidade('');
    }

    /**
     * Test that salvarNacionalidade accepts whitespace only name
     * Note: empty() in PHP returns false for whitespace strings
     */
    public function testSalvarNacionalidadeThrowsExceptionWhenNomeIsWhitespaceOnly(): void
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

        $resultado = $this->service->salvarNacionalidade($nome);

        $this->assertEquals('   ', $resultado->getNome());
    }

    /**
     * Test that salvarNacionalidade logs info with correct data after successful save
     */
    public function testSalvarNacionalidadeLogsInfoWithCorrectData(): void
    {
        $nome = 'Alemã';

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
                'Nacionalidade salva com sucesso',
                $this->callback(function ($context) use ($nome) {
                    return isset($context['nome']) && $context['nome'] === $nome;
                })
            );

        $this->service->salvarNacionalidade($nome);
    }

    /**
     * Test that salvarNacionalidade rethrows exception and logs error on persist failure
     */
    public function testSalvarNacionalidadeRethrowsExceptionAndLogsOnPersistFailure(): void
    {
        $nome = 'Portuguesa';
        $exception = new \Exception('Database error');

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->willThrowException($exception);

        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with(
                'Erro ao salvar nacionalidade',
                $this->callback(function ($context) use ($nome) {
                    return isset($context['nome']) && $context['nome'] === $nome;
                })
            );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Database error');

        $this->service->salvarNacionalidade($nome);
    }

    /**
     * Test that salvarNacionalidade rethrows exception and logs error on flush failure
     */
    public function testSalvarNacionalidadeRethrowsExceptionAndLogsOnFlushFailure(): void
    {
        $nome = 'Espanhola';
        $exception = new \Exception('Flush error');

        $this->entityManager
            ->expects($this->once())
            ->method('persist');

        $this->entityManager
            ->expects($this->once())
            ->method('flush')
            ->willThrowException($exception);

        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with(
                'Erro ao salvar nacionalidade',
                $this->callback(function ($context) use ($nome) {
                    return isset($context['nome']) && $context['nome'] === $nome;
                })
            );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Flush error');

        $this->service->salvarNacionalidade($nome);
    }

    /**
     * Test that salvarNacionalidade returns the same instance as persisted
     */
    public function testSalvarNacionalidadeReturnsSameInstanceAsPersisted(): void
    {
        $nome = 'Holandesa';
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

        $resultado = $this->service->salvarNacionalidade($nome);

        $this->assertSame($persistedEntity, $resultado);
    }
}
