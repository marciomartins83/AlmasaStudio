<?php

namespace App\Repository;

use App\Entity\Telefones;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Telefones>
 *
 * @method Telefones|null find($id, $lockMode = null, $lockVersion = null)
 * @method Telefones|null findOneBy(array $criteria, array $orderBy = null)
 * @method Telefones[]    findAll()
 * @method Telefones[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TelefoneRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Telefones::class);
    }
} 