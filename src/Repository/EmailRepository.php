<?php

namespace App\Repository;

use App\Entity\Emails;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Emails>
 *
 * @method Emails|null find($id, $lockMode = null, $lockVersion = null)
 * @method Emails|null findOneBy(array $criteria, array $orderBy = null)
 * @method Emails[]    findAll()
 * @method Emails[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EmailRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Emails::class);
    }
} 