<?php

namespace App\Repository;

use App\Entity\TipoImovel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TipoImovel>
 */
class TipoImovelRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TipoImovel::class);
    }

    public function findAll(): array
    {
        return $this->createQueryBuilder('t')
            ->orderBy('t.tipo', 'ASC')
            ->getQuery()
            ->getResult();
    }

    // Add custom repository methods here if needed in the future.
    // For example, to find a TipoImovel by its name:
    /*
    public function findByName(string $name): ?TipoImovel
    {
        return $this->createQueryBuilder('t')
            ->where('t.name = :name')
            ->setParameter('name', $name)
            ->getQuery()
            ->getOneOrNullResult();
    }
    */
}
