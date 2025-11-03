<?php

namespace App\Repository;

use App\Entity\TiposEnderecos;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TiposEnderecos>
 *
 * @method TiposEnderecos|null find($id, $lockMode = null, $lockVersion = null)
 * @method TiposEnderecos|null findOneBy(array $criteria, array $orderBy = null)
 * @method TiposEnderecos[]    findAll()
 * @method TiposEnderecos[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TiposEnderecosRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TiposEnderecos::class);
    }
}
