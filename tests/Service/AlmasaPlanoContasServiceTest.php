<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\AlmasaPlanoContas;
use App\Entity\AlmasaVinculoBancario;
use App\Entity\ContasBancarias;
use App\Entity\Lancamentos;
use App\Repository\AlmasaVinculoBancarioRepository;
use App\Repository\LancamentosRepository;
use App\Service\AlmasaPlanoContasService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class AlmasaPlanoContasServiceTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;
    private AlmasaPlanoContasService $service;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->service = new AlmasaPlanoContasService($this->entityManager, $this->logger);
    }

    public function testAtualizarLancamentoSaldoAnteriorDesvinculaContaBancariaSemVinculoAtivo(): void
    {
        $conta = (new AlmasaPlanoContas())
            ->setCodigo('1.01.001')
            ->setDescricao('Conta Caixa')
            ->setSaldoAnterior('250.00');

        $lancamento = (new Lancamentos())
            ->setValor('100.00')
            ->setValorPago('100.00')
            ->setHistorico('Saldo anterior antigo')
            ->setContaBancaria(new ContasBancarias());

        $lancamentosRepository = $this->createMock(LancamentosRepository::class);
        $vinculoRepository = $this->createMock(AlmasaVinculoBancarioRepository::class);
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        $this->entityManager
            ->method('getRepository')
            ->willReturnMap([
                [Lancamentos::class, $lancamentosRepository],
                [AlmasaVinculoBancario::class, $vinculoRepository],
            ]);

        $lancamentosRepository
            ->expects($this->once())
            ->method('createQueryBuilder')
            ->with('l')
            ->willReturn($queryBuilder);

        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('andWhere')->willReturnSelf();
        $queryBuilder->method('setParameter')->willReturnSelf();
        $queryBuilder->method('setMaxResults')->willReturnSelf();
        $queryBuilder->expects($this->once())->method('getQuery')->willReturn($query);

        $query
            ->expects($this->once())
            ->method('getOneOrNullResult')
            ->willReturn($lancamento);

        $vinculoRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(
                ['almasaPlanoConta' => $conta, 'ativo' => true],
                ['padrao' => 'DESC']
            )
            ->willReturn(null);

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $reflectionMethod = new \ReflectionMethod($this->service, 'atualizarLancamentoSaldoAnterior');
        $reflectionMethod->setAccessible(true);
        $reflectionMethod->invoke($this->service, $conta);

        $this->assertNull($lancamento->getContaBancaria());
        $this->assertSame('250.00', $lancamento->getValor());
        $this->assertSame('250.00', $lancamento->getValorPago());
        $this->assertSame('Saldo anterior — 1.01.001 Conta Caixa', $lancamento->getHistorico());
    }
}
