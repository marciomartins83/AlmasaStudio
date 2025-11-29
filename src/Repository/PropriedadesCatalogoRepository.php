<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\PropriedadesCatalogo;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PropriedadesCatalogo>
 *
 * @method PropriedadesCatalogo|null find($id, $lockMode = null, $lockVersion = null)
 * @method PropriedadesCatalogo|null findOneBy(array $criteria, array $orderBy = null)
 * @method PropriedadesCatalogo[]    findAll()
 * @method PropriedadesCatalogo[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PropriedadesCatalogoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PropriedadesCatalogo::class);
    }
}
