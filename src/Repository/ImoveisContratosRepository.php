<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ImoveisContratos;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ImoveisContratos>
 *
 * @method ImoveisContratos|null find($id, $lockMode = null, $lockVersion = null)
 * @method ImoveisContratos|null findOneBy(array $criteria, array $orderBy = null)
 * @method ImoveisContratos[]    findAll()
 * @method ImoveisContratos[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ImoveisContratosRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ImoveisContratos::class);
    }
}
