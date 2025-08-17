<?php

namespace App\Repository;

use App\Entity\TiposEmails;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TiposEmails>
 *
 * @method TiposEmails|null find($id, $lockMode = null, $lockVersion = null)
 * @method TiposEmails|null findOneBy(array $criteria, array $orderBy = null)
 * @method TiposEmails[]    findAll()
 * @method TiposEmails[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TipoEmailRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TiposEmails::class);
    }
} 