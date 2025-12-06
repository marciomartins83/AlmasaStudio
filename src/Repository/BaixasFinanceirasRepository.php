<?php

namespace App\Repository;

use App\Entity\BaixasFinanceiras;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BaixasFinanceiras>
 */
class BaixasFinanceirasRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BaixasFinanceiras::class);
    }

    /**
     * Busca baixas por período
     *
     * @param \DateTime $inicio
     * @param \DateTime $fim
     * @return array
     */
    public function findByPeriodo(\DateTime $inicio, \DateTime $fim): array
    {
        return $this->createQueryBuilder('b')
            ->leftJoin('b.lancamento', 'l')
            ->leftJoin('l.inquilino', 'inq')
            ->addSelect('l', 'inq')
            ->where('b.dataPagamento BETWEEN :inicio AND :fim')
            ->andWhere('b.estornada = false')
            ->setParameter('inicio', $inicio)
            ->setParameter('fim', $fim)
            ->orderBy('b.dataPagamento', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Busca baixas de um lançamento
     *
     * @param int $lancamentoId
     * @return array
     */
    public function findByLancamento(int $lancamentoId): array
    {
        return $this->createQueryBuilder('b')
            ->where('b.lancamento = :lancamento')
            ->andWhere('b.estornada = false')
            ->setParameter('lancamento', $lancamentoId)
            ->orderBy('b.dataPagamento', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Total recebido por período
     *
     * @param \DateTime $inicio
     * @param \DateTime $fim
     * @return array
     */
    public function getTotalRecebidoPeriodo(\DateTime $inicio, \DateTime $fim): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = "
            SELECT
                COUNT(*) as quantidade,
                COALESCE(SUM(valor_total_pago), 0) as total_recebido,
                COALESCE(SUM(valor_multa_paga), 0) as total_multa,
                COALESCE(SUM(valor_juros_pago), 0) as total_juros,
                COALESCE(SUM(valor_desconto), 0) as total_desconto
            FROM baixas_financeiras
            WHERE data_pagamento BETWEEN :inicio AND :fim
            AND estornada = false
        ";

        return $conn->fetchAssociative($sql, [
            'inicio' => $inicio->format('Y-m-d'),
            'fim' => $fim->format('Y-m-d')
        ]);
    }

    /**
     * Busca baixas recentes
     *
     * @param int $limite
     * @return array
     */
    public function findRecentes(int $limite = 10): array
    {
        return $this->createQueryBuilder('b')
            ->leftJoin('b.lancamento', 'l')
            ->leftJoin('l.inquilino', 'inq')
            ->addSelect('l', 'inq')
            ->where('b.estornada = false')
            ->orderBy('b.dataPagamento', 'DESC')
            ->setMaxResults($limite)
            ->getQuery()
            ->getResult();
    }

    /**
     * Busca baixas por forma de pagamento
     *
     * @param string $formaPagamento
     * @param \DateTime|null $inicio
     * @param \DateTime|null $fim
     * @return array
     */
    public function findByFormaPagamento(string $formaPagamento, ?\DateTime $inicio = null, ?\DateTime $fim = null): array
    {
        $qb = $this->createQueryBuilder('b')
            ->leftJoin('b.lancamento', 'l')
            ->addSelect('l')
            ->where('b.formaPagamento = :forma')
            ->andWhere('b.estornada = false')
            ->setParameter('forma', $formaPagamento);

        if ($inicio) {
            $qb->andWhere('b.dataPagamento >= :inicio')
               ->setParameter('inicio', $inicio);
        }

        if ($fim) {
            $qb->andWhere('b.dataPagamento <= :fim')
               ->setParameter('fim', $fim);
        }

        return $qb->orderBy('b.dataPagamento', 'DESC')
                  ->getQuery()
                  ->getResult();
    }
}
