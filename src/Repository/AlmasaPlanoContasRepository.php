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
    public function findAtivos(): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.ativo = :ativo')
            ->setParameter('ativo', true)
            ->orderBy('a.codigo', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return AlmasaPlanoContas[]
     */
    public function findContasQueAceitamLancamentos(): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.aceitaLancamentos = :aceita')
            ->andWhere('a.ativo = :ativo')
            ->setParameter('aceita', true)
            ->setParameter('ativo', true)
            ->orderBy('a.codigo', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return AlmasaPlanoContas[]
     */
    public function findByNivel(int $nivel): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.nivel = :nivel')
            ->andWhere('a.ativo = :ativo')
            ->setParameter('nivel', $nivel)
            ->setParameter('ativo', true)
            ->orderBy('a.codigo', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return AlmasaPlanoContas[]
     */
    public function findHierarquiaCompleta(): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.ativo = :ativo')
            ->setParameter('ativo', true)
            ->orderBy('a.codigo', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
