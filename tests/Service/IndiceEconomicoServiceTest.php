<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\IndiceEconomico;
use App\Repository\IndiceEconomicoRepository;
use App\Service\IndiceEconomicoService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class IndiceEconomicoServiceTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private IndiceEconomicoRepository $repository;
    private LoggerInterface $logger;
    private IndiceEconomicoService $service;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(IndiceEconomicoRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->service = new IndiceEconomicoService($this->entityManager, $this->repository, $this->logger);
    }

    public function testCriarPersisteEFlush(): void
    {
        $indice = new IndiceEconomico();
        $indice->setTipo(IndiceEconomico::TIPO_IGPM);
        $indice->setCompetencia('2026-04');

        $this->entityManager->expects($this->once())->method('persist')->with($indice);
        $this->entityManager->expects($this->once())->method('flush');

        $this->service->criar($indice);
    }

    public function testDeletarRemoveEFlush(): void
    {
        $indice = new IndiceEconomico();

        $this->entityManager->expects($this->once())->method('remove')->with($indice);
        $this->entityManager->expects($this->once())->method('flush');

        $this->service->deletar($indice);
    }

    public function testBuscarVigenteDelegaParaRepository(): void
    {
        $indice = new IndiceEconomico();

        $this->repository
            ->expects($this->once())
            ->method('findVigente')
            ->with(IndiceEconomico::TIPO_IGPM, '2026-04')
            ->willReturn($indice);

        $resultado = $this->service->buscarVigente(IndiceEconomico::TIPO_IGPM, '2026-04');

        $this->assertSame($indice, $resultado);
    }

    public function testCriarRelancaExcecaoEmCasoDeErro(): void
    {
        $indice = new IndiceEconomico();

        $this->entityManager->method('persist')->willThrowException(new \Exception('Falha no banco'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Falha no banco');

        $this->service->criar($indice);
    }
}
