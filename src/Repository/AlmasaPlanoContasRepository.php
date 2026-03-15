<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\AlmasaPlanoContas;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AlmasaPlanoContas>
 */
class AlmasaPlanoContasRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AlmasaPlanoContas::class);
    }

    /**
     * @return AlmasaPlanoContas[]
     */
    /**
     * Aplica ordenação numérica por segmento do código (ex: 2.1.01.103 antes de 2.1.01.1031).
     */
    private function applyNumericCodigoSort(object $qb): object
    {
        return $qb
            ->orderBy("LENGTH(SPLIT_PART(a.codigo, '.', 1))", 'ASC')
            ->addOrderBy("SPLIT_PART(a.codigo, '.', 1)", 'ASC')
            ->addOrderBy("LENGTH(SPLIT_PART(a.codigo, '.', 2))", 'ASC')
            ->addOrderBy("SPLIT_PART(a.codigo, '.', 2)", 'ASC')
            ->addOrderBy("LENGTH(SPLIT_PART(a.codigo, '.', 3))", 'ASC')
            ->addOrderBy("SPLIT_PART(a.codigo, '.', 3)", 'ASC')
            ->addOrderBy("LENGTH(SPLIT_PART(a.codigo, '.', 4))", 'ASC')
            ->addOrderBy("SPLIT_PART(a.codigo, '.', 4)", 'ASC');
    }

    /**
     * @return AlmasaPlanoContas[]
     */
    public function findAtivos(): array
    {
        return $this->applyNumericCodigoSort(
            $this->createQueryBuilder('a')
                ->where('a.ativo = :ativo')
                ->setParameter('ativo', true)
        )->getQuery()->getResult();
    }

    /**
     * @return AlmasaPlanoContas[]
     */
    public function findContasQueAceitamLancamentos(): array
    {
        return $this->applyNumericCodigoSort(
            $this->createQueryBuilder('a')
                ->where('a.aceitaLancamentos = :aceita')
                ->andWhere('a.ativo = :ativo')
                ->setParameter('aceita', true)
                ->setParameter('ativo', true)
        )->getQuery()->getResult();
    }

    /**
     * @return AlmasaPlanoContas[]
     */
    public function findByNivel(int $nivel): array
    {
        return $this->applyNumericCodigoSort(
            $this->createQueryBuilder('a')
                ->where('a.nivel = :nivel')
                ->andWhere('a.ativo = :ativo')
                ->setParameter('nivel', $nivel)
                ->setParameter('ativo', true)
        )->getQuery()->getResult();
    }

    /**
     * @return AlmasaPlanoContas[]
     */
    public function findHierarquiaCompleta(): array
    {
        return $this->applyNumericCodigoSort(
            $this->createQueryBuilder('a')
                ->where('a.ativo = :ativo')
                ->setParameter('ativo', true)
        )->getQuery()->getResult();
    }
}
