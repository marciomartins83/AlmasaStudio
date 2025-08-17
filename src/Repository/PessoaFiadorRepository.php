<?php

namespace App\Repository;

use App\Entity\PessoasFiadores;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PessoasFiadores>
 *
 * @method PessoasFiadores|null find($id, $lockMode = null, $lockVersion = null)
 * @method PessoasFiadores|null findOneBy(array $criteria, array $orderBy = null)
 * @method PessoasFiadores[]    findAll()
 * @method PessoasFiadores[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PessoaFiadorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PessoasFiadores::class);
    }
} 