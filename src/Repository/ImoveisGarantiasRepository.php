<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ImoveisGarantias;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ImoveisGarantias>
 *
 * @method ImoveisGarantias|null find($id, $lockMode = null, $lockVersion = null)
 * @method ImoveisGarantias|null findOneBy(array $criteria, array $orderBy = null)
 * @method ImoveisGarantias[]    findAll()
 * @method ImoveisGarantias[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ImoveisGarantiasRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ImoveisGarantias::class);
    }
}
