<?php

namespace App\Repository;

use App\Entity\PessoasLocadores;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PessoasLocadores>
 *
 * @method PessoasLocadores|null find($id, $lockMode = null, $lockVersion = null)
 * @method PessoasLocadores|null findOneBy(array $criteria, array $orderBy = null)
 * @method PessoasLocadores[]    findAll()
 * @method PessoasLocadores[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PessoaLocadorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PessoasLocadores::class);
    }
} 