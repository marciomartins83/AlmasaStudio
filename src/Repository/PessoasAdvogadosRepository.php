<?php

namespace App\Repository;

use App\Entity\PessoasAdvogados;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PessoasAdvogados>
 */
class PessoasAdvogadosRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PessoasAdvogados::class);
    }

    public function findByPessoa(int $pessoaId): ?PessoasAdvogados
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.idPessoa = :pessoaId')
            ->andWhere('a.ativo = true')
            ->setParameter('pessoaId', $pessoaId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByOab(string $numeroOab, string $seccional): ?PessoasAdvogados
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.numeroOab = :numero')
            ->andWhere('a.seccionalOab = :seccional')
            ->andWhere('a.ativo = true')
            ->setParameter('numero', $numeroOab)
            ->setParameter('seccional', $seccional)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findAllAtivos(): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.ativo = true')
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
