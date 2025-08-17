<?php

namespace App\Repository;

use App\Entity\PessoasCorretores;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PessoasCorretores>
 *
 * @method PessoasCorretores|null find($id, $lockMode = null, $lockVersion = null)
 * @method PessoasCorretores|null findOneBy(array $criteria, array $orderBy = null)
 * @method PessoasCorretores[]    findAll()
 * @method PessoasCorretores[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PessoaCorretorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PessoasCorretores::class);
    }
} 