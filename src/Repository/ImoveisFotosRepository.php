<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ImoveisFotos;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ImoveisFotos>
 *
 * @method ImoveisFotos|null find($id, $lockMode = null, $lockVersion = null)
 * @method ImoveisFotos|null findOneBy(array $criteria, array $orderBy = null)
 * @method ImoveisFotos[]    findAll()
 * @method ImoveisFotos[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ImoveisFotosRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ImoveisFotos::class);
    }
}
