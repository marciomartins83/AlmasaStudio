<?php

namespace App\Repository;

use App\Entity\ConfiguracoesApiBanco;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ConfiguracoesApiBanco>
 *
 * @method ConfiguracoesApiBanco|null find($id, $lockMode = null, $lockVersion = null)
 * @method ConfiguracoesApiBanco|null findOneBy(array $criteria, array $orderBy = null)
 * @method ConfiguracoesApiBanco[]    findAll()
 * @method ConfiguracoesApiBanco[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ConfiguracoesApiBancoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ConfiguracoesApiBanco::class);
    }

    /**
     * Busca configuração por conta bancária
     */
    public function findByContaBancaria(int $contaBancariaId): ?ConfiguracoesApiBanco
    {
        return $this->createQueryBuilder('c')
            ->where('c.contaBancaria = :contaBancariaId')
            ->andWhere('c.ativo = true')
            ->setParameter('contaBancariaId', $contaBancariaId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Busca configuração por conta bancária e ambiente
     */
    public function findByContaBancariaEAmbiente(int $contaBancariaId, string $ambiente): ?ConfiguracoesApiBanco
    {
        return $this->createQueryBuilder('c')
            ->where('c.contaBancaria = :contaBancariaId')
            ->andWhere('c.ambiente = :ambiente')
            ->setParameter('contaBancariaId', $contaBancariaId)
            ->setParameter('ambiente', $ambiente)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Busca todas as configurações ativas
     */
    public function findAtivas(): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.ativo = true')
            ->orderBy('c.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Busca configurações por ambiente
     */
    public function findByAmbiente(string $ambiente): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.ambiente = :ambiente')
            ->andWhere('c.ativo = true')
            ->setParameter('ambiente', $ambiente)
            ->orderBy('c.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Busca todas as configurações com dados relacionados (banco e conta)
     */
    public function findAllWithRelations(): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.banco', 'b')
            ->leftJoin('c.contaBancaria', 'cb')
            ->addSelect('b', 'cb')
            ->orderBy('c.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Busca configurações por banco
     */
    public function findByBanco(int $bancoId): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.banco = :bancoId')
            ->andWhere('c.ativo = true')
            ->setParameter('bancoId', $bancoId)
            ->orderBy('c.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Busca configurações com certificado próximo de expirar (30 dias)
     */
    public function findCertificadosExpirando(int $dias = 30): array
    {
        $dataLimite = (new \DateTime())->modify("+{$dias} days");

        return $this->createQueryBuilder('c')
            ->where('c.certificadoValidade IS NOT NULL')
            ->andWhere('c.certificadoValidade <= :dataLimite')
            ->andWhere('c.certificadoValidade >= :hoje')
            ->andWhere('c.ativo = true')
            ->setParameter('dataLimite', $dataLimite)
            ->setParameter('hoje', new \DateTime())
            ->orderBy('c.certificadoValidade', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
