<?php

namespace App\Repository;

use App\Entity\TiposImoveis;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TiposImoveis>
 *
 * @method TiposImoveis|null find($id, $lockMode = null, $lockVersion = null)
 * @method TiposImoveis|null findOneBy(array $criteria, array $orderBy = null)
 * @method TiposImoveis[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TiposImoveisRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TiposImoveis::class);
    }

    /**
     * @return TiposImoveis[] Returns an array of TiposImoveis objects ordered by tipo
     */
    public function findAll(): array
    {
        return $this->createQueryBuilder('t')
            ->orderBy('t.tipo', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
