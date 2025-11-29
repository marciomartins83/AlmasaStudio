<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Imoveis;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Imoveis>
 *
 * @method Imoveis|null find($id, $lockMode = null, $lockVersion = null)
 * @method Imoveis|null findOneBy(array $criteria, array $orderBy = null)
 * @method Imoveis[]    findAll()
 * @method Imoveis[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ImoveisRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Imoveis::class);
    }
}
