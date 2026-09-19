<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\FaixaImpostoRenda;
use App\Repository\FaixaImpostoRendaRepository;
use App\Service\TabelaImpostoRendaService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class TabelaImpostoRendaServiceTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private FaixaImpostoRendaRepository $repository;
    private LoggerInterface $logger;
    private TabelaImpostoRendaService $service;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(FaixaImpostoRendaRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->service = new TabelaImpostoRendaService($this->entityManager, $this->repository, $this->logger);
    }

    private function criarFaixa(string $inicial, ?string $final, string $aliquota, string $parcelaDeduzir): FaixaImpostoRenda
    {
        $faixa = new FaixaImpostoRenda();
        $faixa->setDataVigencia(new \DateTime('2026-01-01'));
        $faixa->setValorInicial($inicial);
        $faixa->setValorFinal($final);
        $faixa->setAliquota($aliquota);
        $faixa->setParcelaDeduzir($parcelaDeduzir);

        return $faixa;
    }

    private function tabelaExemplo(): array
    {
        return [
            $this->criarFaixa('0.00', '2000.00', '0.00', '0.00'),
            $this->criarFaixa('2000.01', '3000.00', '10.00', '200.00'),
            $this->criarFaixa('3000.01', null, '20.00', '500.00'),
        ];
    }

    public function testCalcularRetencaoFaixaIsenta(): void
    {
        $dataVigente = new \DateTime('2026-01-01');
        $this->repository->method('findDataVigenteMaisRecente')->willReturn($dataVigente);
        $this->repository->method('findByDataVigencia')->with($dataVigente)->willReturn($this->tabelaExemplo());

        $retencao = $this->service->calcularRetencao(1500.00);

        $this->assertSame(0.0, $retencao);
    }

    public function testCalcularRetencaoFaixaIntermediaria(): void
    {
        $dataVigente = new \DateTime('2026-01-01');
        $this->repository->method('findDataVigenteMaisRecente')->willReturn($dataVigente);
        $this->repository->method('findByDataVigencia')->with($dataVigente)->willReturn($this->tabelaExemplo());

        // 2500 * 10% - 200 = 50
        $retencao = $this->service->calcularRetencao(2500.00);

        $this->assertSame(50.0, $retencao);
    }

    public function testCalcularRetencaoUltimaFaixaSemTeto(): void
    {
        $dataVigente = new \DateTime('2026-01-01');
        $this->repository->method('findDataVigenteMaisRecente')->willReturn($dataVigente);
        $this->repository->method('findByDataVigencia')->with($dataVigente)->willReturn($this->tabelaExemplo());

        // 10000 * 20% - 500 = 1500
        $retencao = $this->service->calcularRetencao(10000.00);

        $this->assertSame(1500.0, $retencao);
    }

    public function testCalcularRetencaoRetornaZeroQuandoValorNaoPositivo(): void
    {
        $this->repository->expects($this->never())->method('findDataVigenteMaisRecente');

        $this->assertSame(0.0, $this->service->calcularRetencao(0));
        $this->assertSame(0.0, $this->service->calcularRetencao(-100));
    }

    public function testCalcularRetencaoRetornaZeroQuandoNaoHaTabelaVigente(): void
    {
        $this->repository->method('findDataVigenteMaisRecente')->willReturn(null);

        $retencao = $this->service->calcularRetencao(5000.00);

        $this->assertSame(0.0, $retencao);
    }

    public function testCalcularRetencaoNuncaRetornaNegativo(): void
    {
        $dataVigente = new \DateTime('2026-01-01');
        $this->repository->method('findDataVigenteMaisRecente')->willReturn($dataVigente);
        // Faixa hipotética com parcela a deduzir maior que a retenção bruta
        $this->repository->method('findByDataVigencia')->willReturn([
            $this->criarFaixa('0.00', null, '5.00', '1000.00'),
        ]);

        $retencao = $this->service->calcularRetencao(100.00);

        $this->assertSame(0.0, $retencao);
    }
}
