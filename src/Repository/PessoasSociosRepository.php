<?php

namespace App\Repository;

use App\Entity\PessoasSocios;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PessoasSocios>
 */
class PessoasSociosRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PessoasSocios::class);
    }

    public function findByPessoa(int $pessoaId): ?PessoasSocios
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.idPessoa = :pessoaId')
            ->andWhere('s.ativo = true')
            ->setParameter('pessoaId', $pessoaId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findAllAtivos(): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.ativo = true')
            ->orderBy('s.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
