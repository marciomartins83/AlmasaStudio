<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ImoveisMedidores;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ImoveisMedidores>
 *
 * @method ImoveisMedidores|null find($id, $lockMode = null, $lockVersion = null)
 * @method ImoveisMedidores|null findOneBy(array $criteria, array $orderBy = null)
 * @method ImoveisMedidores[]    findAll()
 * @method ImoveisMedidores[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ImoveisMedidoresRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ImoveisMedidores::class);
    }
}
