<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\AlmasaVinculoBancario;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AlmasaVinculoBancario>
 */
class AlmasaVinculoBancarioRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AlmasaVinculoBancario::class);
    }
}
