<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\AlmasaLancamento;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AlmasaLancamento>
 */
class AlmasaLancamentoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AlmasaLancamento::class);
    }

    /**
     * Verifica se ja existe lancamento Almasa para o lancamento de origem (idempotencia)
     */
    public function findByLancamentoOrigem(int $lancamentoOrigemId): ?AlmasaLancamento
    {
        return $this->createQueryBuilder('a')
            ->where('a.lancamentoOrigem = :id')
            ->setParameter('id', $lancamentoOrigemId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return AlmasaLancamento[]
     */
    public function findByPeriodo(\DateTimeInterface $inicio, \DateTimeInterface $fim, ?string $tipo = null): array
    {
        $qb = $this->createQueryBuilder('a')
            ->where('a.dataCompetencia BETWEEN :inicio AND :fim')
            ->setParameter('inicio', $inicio)
            ->setParameter('fim', $fim)
            ->orderBy('a.dataCompetencia', 'ASC');

        if ($tipo) {
            $qb->andWhere('a.tipo = :tipo')
               ->setParameter('tipo', $tipo);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Total por tipo (receita/despesa) em um periodo — base para DRE
     *
     * @return array{receitas: string, despesas: string, saldo: string}
     */
    public function getTotaisPorTipoPeriodo(\DateTimeInterface $inicio, \DateTimeInterface $fim, ?string $status = null): array
    {
        $qb = $this->createQueryBuilder('a')
            ->select(
                "SUM(CASE WHEN a.tipo = 'receita' THEN a.valor ELSE 0 END) AS receitas",
                "SUM(CASE WHEN a.tipo = 'despesa' THEN a.valor ELSE 0 END) AS despesas"
            )
            ->where('a.dataCompetencia BETWEEN :inicio AND :fim')
            ->setParameter('inicio', $inicio)
            ->setParameter('fim', $fim);

        if ($status) {
            $qb->andWhere('a.status = :status')
               ->setParameter('status', $status);
        }

        $result = $qb->getQuery()->getSingleResult();

        $receitas = $result['receitas'] ?? '0.00';
        $despesas = $result['despesas'] ?? '0.00';

        return [
            'receitas' => $receitas,
            'despesas' => $despesas,
            'saldo' => bcsub($receitas, $despesas, 2),
        ];
    }

    /**
     * Totais agrupados por plano de contas (nivel 3) — DRE detalhada
     *
     * @return array<int, array{conta_id: int, codigo: string, descricao: string, tipo: string, total: string}>
     */
    public function getTotaisPorContaPeriodo(\DateTimeInterface $inicio, \DateTimeInterface $fim, ?string $status = null): array
    {
        $qb = $this->createQueryBuilder('a')
            ->select(
                'IDENTITY(a.almasaPlanoConta) AS conta_id',
                'pc.codigo',
                'pc.descricao',
                'pc.tipo',
                'SUM(a.valor) AS total'
            )
            ->join('a.almasaPlanoConta', 'pc')
            ->where('a.dataCompetencia BETWEEN :inicio AND :fim')
            ->setParameter('inicio', $inicio)
            ->setParameter('fim', $fim)
            ->groupBy('a.almasaPlanoConta, pc.codigo, pc.descricao, pc.tipo')
            ->orderBy('pc.codigo', 'ASC');

        if ($status) {
            $qb->andWhere('a.status = :status')
               ->setParameter('status', $status);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Totais agrupados por subgrupo (nivel 2) — DRE resumida
     *
     * @return array<int, array{codigo_pai: string, descricao_pai: string, tipo: string, total: string}>
     */
    public function getTotaisPorSubgrupoPeriodo(\DateTimeInterface $inicio, \DateTimeInterface $fim, ?string $status = null): array
    {
        $qb = $this->createQueryBuilder('a')
            ->select(
                'pai.codigo AS codigo_pai',
                'pai.descricao AS descricao_pai',
                'pai.tipo',
                'SUM(a.valor) AS total'
            )
            ->join('a.almasaPlanoConta', 'pc')
            ->join('pc.pai', 'pai')
            ->where('a.dataCompetencia BETWEEN :inicio AND :fim')
            ->setParameter('inicio', $inicio)
            ->setParameter('fim', $fim)
            ->groupBy('pai.codigo, pai.descricao, pai.tipo')
            ->orderBy('pai.codigo', 'ASC');

        if ($status) {
            $qb->andWhere('a.status = :status')
               ->setParameter('status', $status);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * QueryBuilder base para paginacao no controller
     */
    public function createBaseQueryBuilder(): \Doctrine\ORM\QueryBuilder
    {
        return $this->createQueryBuilder('a')
            ->leftJoin('a.almasaPlanoConta', 'pc')
            ->orderBy('a.dataCompetencia', 'DESC');
    }
}
