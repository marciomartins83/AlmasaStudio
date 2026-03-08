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
}
