<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Lancamentos;
use App\Entity\LancamentosFinanceiros;
use App\Entity\ContasBancarias;
use App\Entity\PlanoContas;
use App\Repository\LancamentosRepository;
use App\Repository\LancamentosFinanceirosRepository;
use App\Repository\PlanoContasRepository;
use App\Service\RelatorioService;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Twig\Environment;

class RelatorioServiceTest extends TestCase
{
    private RelatorioService $service;
    private EntityManagerInterface $em;
    private LancamentosRepository $lancamentosRepository;
    private LancamentosFinanceirosRepository $lancamentosFinanceirosRepository;
    private PlanoContasRepository $planoContasRepository;
    private Environment $twig;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->lancamentosRepository = $this->createMock(LancamentosRepository::class);
        $this->lancamentosFinanceirosRepository = $this->createMock(LancamentosFinanceirosRepository::class);
        $this->planoContasRepository = $this->createMock(PlanoContasRepository::class);
        $this->twig = $this->createMock(Environment::class);

        $params = $this->createMock(ParameterBagInterface::class);
        $params
            ->expects($this->any())
            ->method('get')
            ->with('kernel.project_dir')
            ->willReturn('/app');

        $this->service = new RelatorioService(
            $this->em,
            $this->lancamentosRepository,
            $this->lancamentosFinanceirosRepository,
            $this->planoContasRepository,
            $this->twig,
            $params
        );
    }

    public function testGetInadimplentesSemFiltros(): void
    {
        $mockQb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $mockQb->expects($this->any())->method('select')->willReturnSelf();
        $mockQb->expects($this->any())->method('from')->willReturnSelf();
        $mockQb->expects($this->any())->method('leftJoin')->willReturnSelf();
        $mockQb->expects($this->any())->method('where')->willReturnSelf();
        $mockQb->expects($this->any())->method('andWhere')->willReturnSelf();
        $mockQb->expects($this->any())->method('setParameter')->willReturnSelf();
        $mockQb->expects($this->any())->method('orderBy')->willReturnSelf();

        $mockQuery = $this->createMock(\Doctrine\ORM\Query::class);
        $mockQuery->expects($this->once())->method('getResult')->willReturn([]);

        $mockQb->expects($this->any())->method('getQuery')->willReturn($mockQuery);

        $this->em
            ->expects($this->any())
            ->method('createQueryBuilder')
            ->willReturn($mockQb);

        $resultado = $this->service->getInadimplentes([]);

        $this->assertIsArray($resultado);
    }

    public function testCalcularJurosMulta(): void
    {
        $resultado = $this->service->calcularJurosMulta(1000.00, 30, null);

        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('multa', $resultado);
        $this->assertArrayHasKey('juros', $resultado);
        $this->assertArrayHasKey('valor_atualizado', $resultado);

        // Default: 2% multa + 1% a.m. juros
        $this->assertEquals(20.0, $resultado['multa']); // 2% de 1000
        $this->assertEquals(10.0, $resultado['juros']); // 1% / 30 * 30 = 1% = 10
        $this->assertEquals(1030.0, $resultado['valor_atualizado']);
    }

    public function testCalcularJurosMultaZeroDias(): void
    {
        $resultado = $this->service->calcularJurosMulta(1000.00, 0, null);

        $this->assertEquals(20.0, $resultado['multa']);
        $this->assertEquals(0.0, $resultado['juros']);
        $this->assertEquals(1020.0, $resultado['valor_atualizado']);
    }

    public function testCalcularJurosMulta60Dias(): void
    {
        $resultado = $this->service->calcularJurosMulta(1000.00, 60, null);

        $this->assertEquals(20.0, $resultado['multa']);
        $this->assertEquals(20.0, $resultado['juros']); // 60/30 = 2 meses * 1% = 2%
        $this->assertEquals(1040.0, $resultado['valor_atualizado']);
    }

    public function testGetTotaisInadimplentesClosed(): void
    {
        $dados = [
            [
                'lancamento' => $this->createMock(LancamentosFinanceiros::class),
                'dias_atraso' => 30,
                'valor_original' => 1000.00,
                'valor_juros' => 10.00,
                'valor_multa' => 20.00,
                'valor_atualizado' => 1030.00,
            ],
            [
                'lancamento' => $this->createMock(LancamentosFinanceiros::class),
                'dias_atraso' => 15,
                'valor_original' => 500.00,
                'valor_juros' => 5.00,
                'valor_multa' => 10.00,
                'valor_atualizado' => 515.00,
            ],
        ];

        $resultado = $this->service->getTotaisInadimplentes($dados);

        $this->assertIsArray($resultado);
        $this->assertEquals(2, $resultado['quantidade']);
        $this->assertEquals(1500.00, $resultado['total_original']);
        $this->assertEquals(15.00, $resultado['total_juros']);
        $this->assertEquals(30.00, $resultado['total_multa']);
        $this->assertEquals(1545.00, $resultado['total_atualizado']);
    }

    public function testGetTotaisInadimplentesClosed_Grouped(): void
    {
        $dados = [
            'grupo1' => [
                'nome' => 'Grupo 1',
                'itens' => [
                    [
                        'lancamento' => $this->createMock(LancamentosFinanceiros::class),
                        'valor_original' => 1000.00,
                        'valor_juros' => 10.00,
                        'valor_multa' => 20.00,
                        'valor_atualizado' => 1030.00,
                    ],
                ],
                'total_original' => 1000.00,
                'total_juros' => 10.00,
                'total_multa' => 20.00,
                'total_atualizado' => 1030.00,
            ],
        ];

        $resultado = $this->service->getTotaisInadimplentes($dados);

        $this->assertEquals(1, $resultado['quantidade']);
        $this->assertEquals(1000.00, $resultado['total_original']);
    }

    public function testGetDespesasReceitas(): void
    {
        $mockQb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $mockQb->expects($this->any())->method('select')->willReturnSelf();
        $mockQb->expects($this->any())->method('from')->willReturnSelf();
        $mockQb->expects($this->any())->method('leftJoin')->willReturnSelf();
        $mockQb->expects($this->any())->method('where')->willReturnSelf();
        $mockQb->expects($this->any())->method('andWhere')->willReturnSelf();
        $mockQb->expects($this->any())->method('setParameter')->willReturnSelf();
        $mockQb->expects($this->any())->method('orderBy')->willReturnSelf();

        $mockQuery = $this->createMock(\Doctrine\ORM\Query::class);
        $mockQuery->expects($this->any())->method('getResult')->willReturn([]);

        $mockQb->expects($this->any())->method('getQuery')->willReturn($mockQuery);

        $this->em
            ->expects($this->any())
            ->method('createQueryBuilder')
            ->willReturn($mockQb);

        $resultado = $this->service->getDespesasReceitas(['status' => 'todos']);

        $this->assertIsArray($resultado);
    }

    public function testGerarComparativoSinteticoConsideraDespesasNormalizadas(): void
    {
        $reflectionMethod = new \ReflectionMethod($this->service, 'gerarComparativoSintetico');
        $reflectionMethod->setAccessible(true);

        $resultado = $reflectionMethod->invoke($this->service, [
            [
                'valorFloat' => 150.0,
                'planoConta' => ['descricao' => 'Taxa Administrativa'],
                '_planoContaId' => '10',
                '_imovelId' => '5',
                '_mes' => '2026-04',
            ],
        ], [], [
            'agrupar_por' => 'mes',
        ]);

        $this->assertEquals([
            '2026-04' => [
                'nome' => '04/2026',
                'receitas' => 0.0,
                'despesas' => 150.0,
                'saldo' => -150.0,
                'percentual_receitas' => 0,
                'percentual_despesas' => 100.0,
            ],
        ], $resultado);
    }

    public function testGetDespesas(): void
    {
        $mockQb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $mockQb->expects($this->any())->method('select')->willReturnSelf();
        $mockQb->expects($this->any())->method('from')->willReturnSelf();
        $mockQb->expects($this->any())->method('leftJoin')->willReturnSelf();
        $mockQb->expects($this->any())->method('where')->willReturnSelf();
        $mockQb->expects($this->any())->method('andWhere')->willReturnSelf();
        $mockQb->expects($this->any())->method('setParameter')->willReturnSelf();
        $mockQb->expects($this->any())->method('orderBy')->willReturnSelf();

        $mockQuery = $this->createMock(\Doctrine\ORM\Query::class);
        $mockQuery->expects($this->any())->method('getResult')->willReturn([]);

        $mockQb->expects($this->any())->method('getQuery')->willReturn($mockQuery);

        $this->em
            ->expects($this->any())
            ->method('createQueryBuilder')
            ->willReturn($mockQb);

        $resultado = $this->service->getDespesas([]);

        $this->assertIsArray($resultado);
    }

    public function testGetReceitas(): void
    {
        $mockQb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $mockQb->expects($this->any())->method('select')->willReturnSelf();
        $mockQb->expects($this->any())->method('from')->willReturnSelf();
        $mockQb->expects($this->any())->method('leftJoin')->willReturnSelf();
        $mockQb->expects($this->any())->method('where')->willReturnSelf();
        $mockQb->expects($this->any())->method('andWhere')->willReturnSelf();
        $mockQb->expects($this->any())->method('setParameter')->willReturnSelf();
        $mockQb->expects($this->any())->method('orderBy')->willReturnSelf();

        $mockQuery = $this->createMock(\Doctrine\ORM\Query::class);
        $mockQuery->expects($this->any())->method('getResult')->willReturn([]);

        $mockQb->expects($this->any())->method('getQuery')->willReturn($mockQuery);

        $this->em
            ->expects($this->any())
            ->method('createQueryBuilder')
            ->willReturn($mockQb);

        $resultado = $this->service->getReceitas(['origem' => 'todos']);

        $this->assertIsArray($resultado);
    }

    public function testGetTotalDespesas(): void
    {
        $mockQb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $mockQb->expects($this->any())->method('select')->willReturnSelf();
        $mockQb->expects($this->any())->method('from')->willReturnSelf();
        $mockQb->expects($this->any())->method('leftJoin')->willReturnSelf();
        $mockQb->expects($this->any())->method('where')->willReturnSelf();
        $mockQb->expects($this->any())->method('andWhere')->willReturnSelf();
        $mockQb->expects($this->any())->method('setParameter')->willReturnSelf();
        $mockQb->expects($this->any())->method('orderBy')->willReturnSelf();

        $mockQuery = $this->createMock(\Doctrine\ORM\Query::class);
        $mockQuery->expects($this->any())->method('getResult')->willReturn([]);

        $mockQb->expects($this->any())->method('getQuery')->willReturn($mockQuery);

        $this->em
            ->expects($this->any())
            ->method('createQueryBuilder')
            ->willReturn($mockQb);

        $resultado = $this->service->getTotalDespesas([]);

        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('quantidade', $resultado);
        $this->assertArrayHasKey('total_aberto', $resultado);
        $this->assertArrayHasKey('total_pago', $resultado);
        $this->assertArrayHasKey('total_geral', $resultado);
    }

    public function testGetTotalReceitas(): void
    {
        $mockQb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $mockQb->expects($this->any())->method('select')->willReturnSelf();
        $mockQb->expects($this->any())->method('from')->willReturnSelf();
        $mockQb->expects($this->any())->method('leftJoin')->willReturnSelf();
        $mockQb->expects($this->any())->method('where')->willReturnSelf();
        $mockQb->expects($this->any())->method('andWhere')->willReturnSelf();
        $mockQb->expects($this->any())->method('setParameter')->willReturnSelf();
        $mockQb->expects($this->any())->method('orderBy')->willReturnSelf();

        $mockQuery = $this->createMock(\Doctrine\ORM\Query::class);
        $mockQuery->expects($this->any())->method('getResult')->willReturn([]);

        $mockQb->expects($this->any())->method('getQuery')->willReturn($mockQuery);

        $this->em
            ->expects($this->any())
            ->method('createQueryBuilder')
            ->willReturn($mockQb);

        $resultado = $this->service->getTotalReceitas(['origem' => 'todos']);

        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('quantidade', $resultado);
    }

    public function testGetSaldoPeriodo(): void
    {
        $mockQb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $mockQb->expects($this->any())->method('select')->willReturnSelf();
        $mockQb->expects($this->any())->method('from')->willReturnSelf();
        $mockQb->expects($this->any())->method('leftJoin')->willReturnSelf();
        $mockQb->expects($this->any())->method('where')->willReturnSelf();
        $mockQb->expects($this->any())->method('andWhere')->willReturnSelf();
        $mockQb->expects($this->any())->method('setParameter')->willReturnSelf();
        $mockQb->expects($this->any())->method('orderBy')->willReturnSelf();

        $mockQuery = $this->createMock(\Doctrine\ORM\Query::class);
        $mockQuery->expects($this->any())->method('getResult')->willReturn([]);

        $mockQb->expects($this->any())->method('getQuery')->willReturn($mockQuery);

        $this->em
            ->expects($this->any())
            ->method('createQueryBuilder')
            ->willReturn($mockQb);

        $resultado = $this->service->getSaldoPeriodo([]);

        $this->assertIsFloat($resultado);
        $this->assertEquals(0.0, $resultado);
    }

    public function testGetPlanoContas(): void
    {
        $mockQb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $mockQb->expects($this->any())->method('select')->willReturnSelf();
        $mockQb->expects($this->any())->method('from')->willReturnSelf();
        $mockQb->expects($this->any())->method('where')->willReturnSelf();
        $mockQb->expects($this->any())->method('andWhere')->willReturnSelf();
        $mockQb->expects($this->any())->method('setParameter')->willReturnSelf();
        $mockQb->expects($this->any())->method('orderBy')->willReturnSelf();

        $mockQuery = $this->createMock(\Doctrine\ORM\Query::class);
        $mockQuery->expects($this->any())->method('getResult')->willReturn([]);

        $mockQb->expects($this->any())->method('getQuery')->willReturn($mockQuery);

        $this->em
            ->expects($this->any())
            ->method('createQueryBuilder')
            ->willReturn($mockQb);

        $resultado = $this->service->getPlanoContas([]);

        $this->assertIsArray($resultado);
    }

    public function testGetMovimentosContaBancaria(): void
    {
        $mockQb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $mockQb->expects($this->any())->method('select')->willReturnSelf();
        $mockQb->expects($this->any())->method('from')->willReturnSelf();
        $mockQb->expects($this->any())->method('leftJoin')->willReturnSelf();
        $mockQb->expects($this->any())->method('where')->willReturnSelf();
        $mockQb->expects($this->any())->method('andWhere')->willReturnSelf();
        $mockQb->expects($this->any())->method('setParameter')->willReturnSelf();
        $mockQb->expects($this->any())->method('orderBy')->willReturnSelf();

        $mockQuery = $this->createMock(\Doctrine\ORM\Query::class);
        $mockQuery->expects($this->any())->method('getResult')->willReturn([]);

        $mockQb->expects($this->any())->method('getQuery')->willReturn($mockQuery);

        $this->em
            ->expects($this->any())
            ->method('createQueryBuilder')
            ->willReturn($mockQb);

        $resultado = $this->service->getMovimentosContaBancaria([]);

        $this->assertIsArray($resultado);
    }

    public function testGetMovimentosContaBancariaUsaDataPagamentoDoCrudQuandoDisponivel(): void
    {
        $queryLf = $this->createMock(\Doctrine\ORM\Query::class);
        $queryLf->expects($this->once())->method('getResult')->willReturn([]);

        $queryCrud = $this->createMock(\Doctrine\ORM\Query::class);

        $contaBancaria = $this->createMock(ContasBancarias::class);
        $contaBancaria->expects($this->any())->method('getId')->willReturn(803);

        $lancamento = (new Lancamentos())
            ->setContaBancaria($contaBancaria)
            ->setTipo(Lancamentos::TIPO_RECEBER)
            ->setStatus(Lancamentos::STATUS_PAGO)
            ->setValor('120.00')
            ->setValorPago('120.00')
            ->setHistorico('Taxa de Administracao')
            ->setNumeroDocumento('123')
            ->setDataVencimento(new \DateTime('2026-03-27'))
            ->setDataPagamento(new \DateTime('2026-04-07'));

        $queryCrud->expects($this->once())->method('getResult')->willReturn([$lancamento]);

        $queryTransferencia = $this->createMock(\Doctrine\ORM\Query::class);
        $queryTransferencia->expects($this->once())->method('getResult')->willReturn([]);

        $qbLf = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $qbCrud = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $qbTransferencia = $this->createMock(\Doctrine\ORM\QueryBuilder::class);

        foreach ([$qbLf, $qbCrud, $qbTransferencia] as $qb) {
            $qb->expects($this->any())->method('select')->willReturnSelf();
            $qb->expects($this->any())->method('from')->willReturnSelf();
            $qb->expects($this->any())->method('leftJoin')->willReturnSelf();
            $qb->expects($this->any())->method('where')->willReturnSelf();
            $qb->expects($this->any())->method('andWhere')->willReturnSelf();
            $qb->expects($this->any())->method('setParameter')->willReturnSelf();
            $qb->expects($this->any())->method('orderBy')->willReturnSelf();
        }

        $qbLf->expects($this->any())->method('getQuery')->willReturn($queryLf);
        $qbCrud->expects($this->any())->method('getQuery')->willReturn($queryCrud);
        $qbTransferencia->expects($this->any())->method('getQuery')->willReturn($queryTransferencia);

        $this->em
            ->expects($this->exactly(3))
            ->method('createQueryBuilder')
            ->willReturnOnConsecutiveCalls($qbLf, $qbCrud, $qbTransferencia);

        $vinculoRepo = $this->createMock(EntityRepository::class);
        $vinculoRepo
            ->expects($this->once())
            ->method('findBy')
            ->with(
                $this->callback(static fn(array $criteria): bool => isset($criteria['almasaPlanoConta']) && ($criteria['ativo'] ?? null) === true),
                ['padrao' => 'DESC'],
                1
            )
            ->willReturn([]);

        $this->em
            ->expects($this->once())
            ->method('getRepository')
            ->with(\App\Entity\AlmasaVinculoBancario::class)
            ->willReturn($vinculoRepo);

        $resultado = $this->service->getMovimentosContaBancaria([
            'data_inicio' => new \DateTime('2026-04-01'),
            'data_fim' => new \DateTime('2026-04-30'),
        ]);

        $this->assertCount(1, $resultado);
        $this->assertEquals(new \DateTime('2026-04-07'), $resultado[0]['dataPagamento']);
        $this->assertTrue($resultado[0]['receber']);
    }

    public function testGetSaldoInicialConta(): void
    {
        $connection = $this->createMock(Connection::class);
        $result1 = $this->createMock(Result::class);
        $result2 = $this->createMock(Result::class);
        $result3 = $this->createMock(Result::class);
        $result4 = $this->createMock(Result::class);
        $sqlExecutado = [];

        $this->em
            ->expects($this->once())
            ->method('getConnection')
            ->willReturn($connection);

        $connection
            ->expects($this->exactly(4))
            ->method('executeQuery')
            ->willReturnCallback(function (string $sql) use (&$sqlExecutado, $result1, $result2, $result3, $result4) {
                $sqlExecutado[] = $sql;

                return match (count($sqlExecutado)) {
                    1 => $result1,
                    2 => $result2,
                    3 => $result3,
                    default => $result4,
                };
            });

        $result1->expects($this->once())->method('fetchOne')->willReturn('4000');
        $result2->expects($this->once())->method('fetchOne')->willReturn('1500');
        $result3->expects($this->once())->method('fetchOne')->willReturn('700');
        $result4->expects($this->once())->method('fetchOne')->willReturn('200');

        $resultado = $this->service->getSaldoInicialConta(1, new \DateTime());

        $this->assertEquals(5000.00, $resultado);
        $this->assertStringContainsString('COALESCE(data_pagamento, data_vencimento) < :data', $sqlExecutado[1]);
        $this->assertStringContainsString('COALESCE(data_pagamento, data_vencimento) < :data', $sqlExecutado[2]);
        $this->assertStringContainsString('COALESCE(l.data_pagamento, l.data_vencimento) < :data', $sqlExecutado[3]);
    }

    public function testGetResumoContas(): void
    {
        $mockQb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $mockQb->expects($this->any())->method('select')->willReturnSelf();
        $mockQb->expects($this->any())->method('from')->willReturnSelf();
        $mockQb->expects($this->any())->method('leftJoin')->willReturnSelf();
        $mockQb->expects($this->any())->method('where')->willReturnSelf();
        $mockQb->expects($this->any())->method('andWhere')->willReturnSelf();
        $mockQb->expects($this->any())->method('setParameter')->willReturnSelf();
        $mockQb->expects($this->any())->method('orderBy')->willReturnSelf();

        $mockQuery = $this->createMock(\Doctrine\ORM\Query::class);
        $mockQuery->expects($this->any())->method('getResult')->willReturn([]);

        $mockQb->expects($this->any())->method('getQuery')->willReturn($mockQuery);

        $this->em
            ->expects($this->any())
            ->method('createQueryBuilder')
            ->willReturn($mockQb);

        $resultado = $this->service->getResumoContas([]);

        $this->assertIsArray($resultado);
    }
}
