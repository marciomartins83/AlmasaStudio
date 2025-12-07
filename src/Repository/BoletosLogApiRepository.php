<?php

namespace App\Repository;

use App\Entity\BoletosLogApi;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BoletosLogApi>
 *
 * @method BoletosLogApi|null find($id, $lockMode = null, $lockVersion = null)
 * @method BoletosLogApi|null findOneBy(array $criteria, array $orderBy = null)
 * @method BoletosLogApi[]    findAll()
 * @method BoletosLogApi[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BoletosLogApiRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BoletosLogApi::class);
    }

    /**
     * Busca logs por boleto
     */
    public function findByBoleto(int $boletoId): array
    {
        return $this->createQueryBuilder('l')
            ->where('l.boleto = :boletoId')
            ->setParameter('boletoId', $boletoId)
            ->orderBy('l.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Busca logs por operação
     */
    public function findByOperacao(string $operacao, ?int $limit = 100): array
    {
        $qb = $this->createQueryBuilder('l')
            ->leftJoin('l.boleto', 'b')
            ->addSelect('b')
            ->where('l.operacao = :operacao')
            ->setParameter('operacao', $operacao)
            ->orderBy('l.createdAt', 'DESC');

        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Busca logs com erro
     */
    public function findComErro(?int $limit = 100): array
    {
        $qb = $this->createQueryBuilder('l')
            ->leftJoin('l.boleto', 'b')
            ->addSelect('b')
            ->where('l.sucesso = false')
            ->orderBy('l.createdAt', 'DESC');

        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Busca logs por período
     */
    public function findByPeriodo(\DateTimeInterface $inicio, \DateTimeInterface $fim): array
    {
        return $this->createQueryBuilder('l')
            ->leftJoin('l.boleto', 'b')
            ->addSelect('b')
            ->where('l.createdAt BETWEEN :inicio AND :fim')
            ->setParameter('inicio', $inicio)
            ->setParameter('fim', $fim)
            ->orderBy('l.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Busca estatísticas de logs
     */
    public function getEstatisticas(?\DateTimeInterface $dataInicio = null): array
    {
        $qb = $this->createQueryBuilder('l')
            ->select(
                'COUNT(l.id) as total',
                'SUM(CASE WHEN l.sucesso = true THEN 1 ELSE 0 END) as sucesso',
                'SUM(CASE WHEN l.sucesso = false THEN 1 ELSE 0 END) as erro',
                'l.operacao'
            )
            ->groupBy('l.operacao');

        if ($dataInicio !== null) {
            $qb->where('l.createdAt >= :dataInicio')
                ->setParameter('dataInicio', $dataInicio);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Busca último log de sucesso para um boleto
     */
    public function findUltimoSucesso(int $boletoId, string $operacao): ?BoletosLogApi
    {
        return $this->createQueryBuilder('l')
            ->where('l.boleto = :boletoId')
            ->andWhere('l.operacao = :operacao')
            ->andWhere('l.sucesso = true')
            ->setParameter('boletoId', $boletoId)
            ->setParameter('operacao', $operacao)
            ->orderBy('l.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Limpa logs antigos
     */
    public function limparLogsAntigos(int $diasRetencao = 90): int
    {
        $dataLimite = (new \DateTime())->modify("-{$diasRetencao} days");

        return $this->createQueryBuilder('l')
            ->delete()
            ->where('l.createdAt < :dataLimite')
            ->setParameter('dataLimite', $dataLimite)
            ->getQuery()
            ->execute();
    }
}
