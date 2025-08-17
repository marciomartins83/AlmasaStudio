<?php

namespace App\Repository;

use App\Entity\ContasBancarias;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ContasBancarias>
 *
 * @method ContasBancarias|null find($id, $lockMode = null, $lockVersion = null)
 * @method ContasBancarias|null findOneBy(array $criteria, array $orderBy = null)
 * @method ContasBancarias[]    findAll()
 * @method ContasBancarias[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ContasBancariasRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ContasBancarias::class);
    }
} 