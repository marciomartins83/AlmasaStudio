<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\TiposCarteiras;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class TiposCarteirasRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TiposCarteiras::class);
    }
}
