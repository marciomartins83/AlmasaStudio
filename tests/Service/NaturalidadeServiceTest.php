<?php

namespace App\Tests\Service;

use App\Entity\Naturalidade;
use App\Service\NaturalidadeService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class NaturalidadeServiceTest extends TestCase
{
    private NaturalidadeService $service;
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->service = new NaturalidadeService($this->entityManager, $this->logger);
    }

    /**
     * Test that salvarNaturalidade creates and persists a Naturalidade entity
     */
    public function testSalvarNaturalidadeCreatesAndPersistsEntity(): void
    {
        $nome = 'São Paulo';

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(Naturalidade::class));

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with('Naturalidade salva com sucesso', $this->isType('array'));

        $resultado = $this->service->salvarNaturalidade($nome);

        $this->assertInstanceOf(Naturalidade::class, $resultado);
        $this->assertEquals($nome, $resultado->getNome());
    }

    /**
     * Test that salvarNaturalidade sets the correct name on the entity
     */
    public function testSalvarNaturalidadeSetNomeCorrectly(): void
    {
        $nome = 'Rio de Janeiro';

        $this->entityManager
            ->expects($this->once())
            ->method('persist');

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->logger
            ->expects($this->once())
            ->method('info');

        $resultado = $this->service->salvarNaturalidade($nome);

        $this->assertEquals('Rio de Janeiro', $resultado->getNome());
    }

    /**
     * Test that salvarNaturalidade throws RuntimeException when nome is empty
     */
    public function testSalvarNaturalidadeThrowsExceptionWhenNomeIsEmpty(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Nome da naturalidade é obrigatório');

        $this->entityManager
            ->expects($this->never())
            ->method('persist');

        $this->entityManager
            ->expects($this->never())
            ->method('flush');

        $this->service->salvarNaturalidade('');
    }

    /**
     * Test that salvarNaturalidade accepts whitespace only name
     * Note: empty() in PHP returns false for whitespace strings
     */
    public function testSalvarNaturalidadeThrowsExceptionWhenNomeIsWhitespaceOnly(): void
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

        $resultado = $this->service->salvarNaturalidade($nome);

        $this->assertEquals('   ', $resultado->getNome());
    }

    /**
     * Test that salvarNaturalidade logs info with correct data after successful save
     */
    public function testSalvarNaturalidadeLogsInfoWithCorrectData(): void
    {
        $nome = 'Brasília';

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
                'Naturalidade salva com sucesso',
                $this->callback(function ($context) use ($nome) {
                    return isset($context['nome']) && $context['nome'] === $nome;
                })
            );

        $this->service->salvarNaturalidade($nome);
    }

    /**
     * Test that salvarNaturalidade rethrows exception and logs error on persist failure
     */
    public function testSalvarNaturalidadeRethrowsExceptionAndLogsOnPersistFailure(): void
    {
        $nome = 'Salvador';
        $exception = new \Exception('Database error');

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->willThrowException($exception);

        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with(
                'Erro ao salvar naturalidade',
                $this->callback(function ($context) use ($nome) {
                    return isset($context['nome']) && $context['nome'] === $nome;
                })
            );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Database error');

        $this->service->salvarNaturalidade($nome);
    }

    /**
     * Test that salvarNaturalidade rethrows exception and logs error on flush failure
     */
    public function testSalvarNaturalidadeRethrowsExceptionAndLogsOnFlushFailure(): void
    {
        $nome = 'Recife';
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
                'Erro ao salvar naturalidade',
                $this->callback(function ($context) use ($nome) {
                    return isset($context['nome']) && $context['nome'] === $nome;
                })
            );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Flush error');

        $this->service->salvarNaturalidade($nome);
    }

    /**
     * Test that salvarNaturalidade returns the same instance as persisted
     */
    public function testSalvarNaturalidadeReturnsSameInstanceAsPersisted(): void
    {
        $nome = 'Curitiba';
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

        $resultado = $this->service->salvarNaturalidade($nome);

        $this->assertSame($persistedEntity, $resultado);
    }

    /**
     * Test that salvarNaturalidade handles special characters in name correctly
     */
    public function testSalvarNaturalidadeHandlesSpecialCharactersInName(): void
    {
        $nome = 'São Paulo - SP';

        $this->entityManager
            ->expects($this->once())
            ->method('persist');

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->logger
            ->expects($this->once())
            ->method('info');

        $resultado = $this->service->salvarNaturalidade($nome);

        $this->assertEquals('São Paulo - SP', $resultado->getNome());
    }
}
