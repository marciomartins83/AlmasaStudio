<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Lancamentos;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Lancamentos>
 */
class LancamentosRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Lancamentos::class);
    }

    /**
     * Cria QueryBuilder base com joins necessarios para paginacao
     */
    public function createBaseQueryBuilder(): \Doctrine\ORM\QueryBuilder
    {
        return $this->createQueryBuilder('l')
            ->leftJoin('l.planoConta', 'pc')
            ->leftJoin('l.pessoaCredor', 'credor')
            ->leftJoin('l.pessoaPagador', 'pagador')
            ->leftJoin('l.contrato', 'c')
            ->leftJoin('l.imovel', 'i')
            ->addSelect('pc', 'credor', 'pagador', 'c', 'i');
    }

    /**
     * Lista lançamentos com filtros
     *
     * @param array $filtros
     * @return Lancamentos[]
     */
    public function findByFiltros(array $filtros = []): array
    {
        $qb = $this->createQueryBuilder('l')
            ->leftJoin('l.planoConta', 'pc')
            ->leftJoin('l.pessoaCredor', 'credor')
            ->leftJoin('l.pessoaPagador', 'pagador')
            ->leftJoin('l.contrato', 'c')
            ->leftJoin('l.imovel', 'i')
            ->addSelect('pc', 'credor', 'pagador', 'c', 'i');

        if (!empty($filtros['tipo'])) {
            $qb->andWhere('l.tipo = :tipo')
               ->setParameter('tipo', $filtros['tipo']);
        }

        if (!empty($filtros['status'])) {
            $qb->andWhere('l.status = :status')
               ->setParameter('status', $filtros['status']);
        }

        if (!empty($filtros['data_vencimento_de'])) {
            $qb->andWhere('l.dataVencimento >= :vencDe')
               ->setParameter('vencDe', new \DateTime($filtros['data_vencimento_de']));
        }

        if (!empty($filtros['data_vencimento_ate'])) {
            $qb->andWhere('l.dataVencimento <= :vencAte')
               ->setParameter('vencAte', new \DateTime($filtros['data_vencimento_ate']));
        }

        if (!empty($filtros['competencia'])) {
            $qb->andWhere('l.competencia = :competencia')
               ->setParameter('competencia', $filtros['competencia']);
        }

        if (!empty($filtros['id_plano_conta'])) {
            $qb->andWhere('l.planoConta = :planoConta')
               ->setParameter('planoConta', $filtros['id_plano_conta']);
        }

        if (!empty($filtros['id_pessoa_credor'])) {
            $qb->andWhere('l.pessoaCredor = :credor')
               ->setParameter('credor', $filtros['id_pessoa_credor']);
        }

        if (!empty($filtros['id_pessoa_pagador'])) {
            $qb->andWhere('l.pessoaPagador = :pagador')
               ->setParameter('pagador', $filtros['id_pessoa_pagador']);
        }

        if (!empty($filtros['id_contrato'])) {
            $qb->andWhere('l.contrato = :contrato')
               ->setParameter('contrato', $filtros['id_contrato']);
        }

        if (!empty($filtros['id_imovel'])) {
            $qb->andWhere('l.imovel = :imovel')
               ->setParameter('imovel', $filtros['id_imovel']);
        }

        $qb->orderBy('l.dataVencimento', 'DESC')
           ->addOrderBy('l.id', 'DESC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Busca lançamentos vencidos
     *
     * @param string|null $tipo
     * @return Lancamentos[]
     */
    public function findVencidos(?string $tipo = null): array
    {
        $qb = $this->createQueryBuilder('l')
            ->leftJoin('l.planoConta', 'pc')
            ->leftJoin('l.pessoaCredor', 'credor')
            ->leftJoin('l.pessoaPagador', 'pagador')
            ->addSelect('pc', 'credor', 'pagador')
            ->where('l.dataVencimento < :hoje')
            ->andWhere('l.status IN (:statusAbertos)')
            ->setParameter('hoje', new \DateTime('today'))
            ->setParameter('statusAbertos', [Lancamentos::STATUS_ABERTO, Lancamentos::STATUS_PAGO_PARCIAL]);

        if ($tipo !== null) {
            $qb->andWhere('l.tipo = :tipo')
               ->setParameter('tipo', $tipo);
        }

        return $qb->orderBy('l.dataVencimento', 'ASC')
                  ->getQuery()
                  ->getResult();
    }

    /**
     * Busca lançamentos por competência
     *
     * @param string $competencia Formato YYYY-MM
     * @param string|null $tipo
     * @return Lancamentos[]
     */
    public function findByCompetencia(string $competencia, ?string $tipo = null): array
    {
        $qb = $this->createQueryBuilder('l')
            ->leftJoin('l.planoConta', 'pc')
            ->addSelect('pc')
            ->where('l.competencia = :competencia')
            ->setParameter('competencia', $competencia);

        if ($tipo !== null) {
            $qb->andWhere('l.tipo = :tipo')
               ->setParameter('tipo', $tipo);
        }

        return $qb->orderBy('l.dataVencimento', 'ASC')
                  ->getQuery()
                  ->getResult();
    }

    /**
     * Retorna próximo número sequencial por tipo
     */
    public function getProximoNumero(string $tipo): int
    {
        $result = $this->createQueryBuilder('l')
            ->select('MAX(l.numero)')
            ->where('l.tipo = :tipo')
            ->setParameter('tipo', $tipo)
            ->getQuery()
            ->getSingleScalarResult();

        return ($result ?? 0) + 1;
    }

    /**
     * Retorna estatísticas de lançamentos
     */
    public function getEstatisticas(?string $competencia = null): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $params = [];
        $whereCompetencia = '';

        if ($competencia !== null) {
            $whereCompetencia = "AND competencia = :competencia";
            $params['competencia'] = $competencia;
        }

        // Total a Pagar
        $sqlPagar = "SELECT
            COUNT(*) as quantidade,
            COALESCE(SUM(valor), 0) as valor_total,
            COALESCE(SUM(valor_pago), 0) as valor_pago
        FROM lancamentos
        WHERE tipo = 'pagar'
          AND status NOT IN ('cancelado')
          $whereCompetencia";

        $pagar = $conn->executeQuery($sqlPagar, $params)->fetchAssociative();

        // Total a Receber
        $sqlReceber = "SELECT
            COUNT(*) as quantidade,
            COALESCE(SUM(valor), 0) as valor_total,
            COALESCE(SUM(valor_pago), 0) as valor_pago
        FROM lancamentos
        WHERE tipo = 'receber'
          AND status NOT IN ('cancelado')
          $whereCompetencia";

        $receber = $conn->executeQuery($sqlReceber, $params)->fetchAssociative();

        // Vencidos (a pagar e receber)
        $sqlVencidos = "SELECT
            tipo,
            COUNT(*) as quantidade,
            COALESCE(SUM(valor - COALESCE(valor_pago, 0)), 0) as saldo
        FROM lancamentos
        WHERE data_vencimento < CURRENT_DATE
          AND status IN ('aberto', 'pago_parcial')
          $whereCompetencia
        GROUP BY tipo";

        $vencidosResult = $conn->executeQuery($sqlVencidos, $params)->fetchAllAssociative();
        $vencidos = [
            'pagar' => ['quantidade' => 0, 'saldo' => 0],
            'receber' => ['quantidade' => 0, 'saldo' => 0],
        ];

        foreach ($vencidosResult as $row) {
            $vencidos[$row['tipo']] = [
                'quantidade' => (int) $row['quantidade'],
                'saldo' => (float) $row['saldo'],
            ];
        }

        return [
            'pagar' => [
                'quantidade' => (int) $pagar['quantidade'],
                'valor_total' => (float) $pagar['valor_total'],
                'valor_pago' => (float) $pagar['valor_pago'],
                'saldo' => (float) $pagar['valor_total'] - (float) $pagar['valor_pago'],
            ],
            'receber' => [
                'quantidade' => (int) $receber['quantidade'],
                'valor_total' => (float) $receber['valor_total'],
                'valor_pago' => (float) $receber['valor_pago'],
                'saldo' => (float) $receber['valor_total'] - (float) $receber['valor_pago'],
            ],
            'vencidos' => $vencidos,
        ];
    }

    /**
     * Busca competências com lançamentos
     *
     * @return string[]
     */
    public function findCompetencias(): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "SELECT DISTINCT competencia
                FROM lancamentos
                WHERE competencia IS NOT NULL
                ORDER BY competencia DESC";

        $result = $conn->executeQuery($sql)->fetchAllAssociative();

        return array_column($result, 'competencia');
    }

    // ========== MÉTODOS LEGADOS (mantidos para compatibilidade) ==========

    /**
     * Busca lançamentos por período
     *
     * @return Lancamentos[]
     */
    public function findByPeriodo(\DateTimeInterface $dataInicio, \DateTimeInterface $dataFim): array
    {
        return $this->createQueryBuilder('l')
            ->where('l.dataMovimento >= :dataInicio')
            ->andWhere('l.dataMovimento <= :dataFim')
            ->setParameter('dataInicio', $dataInicio)
            ->setParameter('dataFim', $dataFim)
            ->orderBy('l.dataMovimento', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Busca lançamentos por competência (ano)
     *
     * @return Lancamentos[]
     */
    public function findByCompetenciaAno(int $ano): array
    {
        return $this->createQueryBuilder('l')
            ->where('l.competencia LIKE :ano')
            ->setParameter('ano', $ano . '-%')
            ->orderBy('l.competencia', 'ASC')
            ->addOrderBy('l.dataMovimento', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Busca lançamentos para processamento de informe de rendimentos
     *
     * @return array
     */
    public function findParaProcessamentoInforme(
        int $ano,
        ?int $proprietarioInicial = null,
        ?int $proprietarioFinal = null
    ): array {
        $conn = $this->getEntityManager()->getConnection();

        $sql = "SELECT
                    l.id_proprietario,
                    l.id_imovel,
                    l.id_inquilino,
                    l.id_plano_conta,
                    SUBSTRING(l.competencia, 6, 2)::integer as mes,
                    SUM(CASE WHEN l.tipo = 'receber' THEN l.valor ELSE -l.valor END) as total
                FROM lancamentos l
                INNER JOIN plano_contas pc ON pc.id = l.id_plano_conta
                WHERE SUBSTRING(l.competencia, 1, 4) = :ano
                  AND pc.entra_informe = true
                  AND l.id_proprietario IS NOT NULL
                  AND l.id_imovel IS NOT NULL
                  AND l.id_inquilino IS NOT NULL";

        $params = ['ano' => (string) $ano];

        if ($proprietarioInicial !== null) {
            $sql .= " AND l.id_proprietario >= :propInicial";
            $params['propInicial'] = $proprietarioInicial;
        }

        if ($proprietarioFinal !== null) {
            $sql .= " AND l.id_proprietario <= :propFinal";
            $params['propFinal'] = $proprietarioFinal;
        }

        $sql .= " GROUP BY l.id_proprietario, l.id_imovel, l.id_inquilino, l.id_plano_conta, SUBSTRING(l.competencia, 6, 2)
                  ORDER BY l.id_proprietario ASC, l.id_imovel ASC, l.id_inquilino ASC";

        return $conn->executeQuery($sql, $params)->fetchAllAssociative();
    }

    /**
     * Busca lançamentos por proprietário e ano
     *
     * @return Lancamentos[]
     */
    public function findByProprietarioEAno(int $proprietarioId, int $ano): array
    {
        return $this->createQueryBuilder('l')
            ->where('l.proprietario = :proprietario')
            ->andWhere('l.competencia LIKE :ano')
            ->setParameter('proprietario', $proprietarioId)
            ->setParameter('ano', $ano . '-%')
            ->orderBy('l.competencia', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Busca lançamentos por imóvel e período
     *
     * @return Lancamentos[]
     */
    public function findByImovelEPeriodo(int $imovelId, \DateTimeInterface $dataInicio, \DateTimeInterface $dataFim): array
    {
        return $this->createQueryBuilder('l')
            ->where('l.imovel = :imovel')
            ->andWhere('l.dataMovimento >= :dataInicio')
            ->andWhere('l.dataMovimento <= :dataFim')
            ->setParameter('imovel', $imovelId)
            ->setParameter('dataInicio', $dataInicio)
            ->setParameter('dataFim', $dataFim)
            ->orderBy('l.dataMovimento', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Busca lançamentos por inquilino e período
     *
     * @return Lancamentos[]
     */
    public function findByInquilinoEPeriodo(int $inquilinoId, \DateTimeInterface $dataInicio, \DateTimeInterface $dataFim): array
    {
        return $this->createQueryBuilder('l')
            ->where('l.inquilino = :inquilino')
            ->andWhere('l.dataMovimento >= :dataInicio')
            ->andWhere('l.dataMovimento <= :dataFim')
            ->setParameter('inquilino', $inquilinoId)
            ->setParameter('dataInicio', $dataInicio)
            ->setParameter('dataFim', $dataFim)
            ->orderBy('l.dataMovimento', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Lista anos com lançamentos
     *
     * @return int[]
     */
    public function findAnosComLancamentos(): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "SELECT DISTINCT SUBSTRING(competencia, 1, 4)::integer as ano
                FROM lancamentos
                WHERE competencia IS NOT NULL
                ORDER BY ano DESC";

        $result = $conn->executeQuery($sql)->fetchAllAssociative();

        return array_column($result, 'ano');
    }

    /**
     * Soma valores por plano de conta em um período
     *
     * @return array
     */
    public function somarPorPlanoContaPeriodo(\DateTimeInterface $dataInicio, \DateTimeInterface $dataFim): array
    {
        return $this->createQueryBuilder('l')
            ->select([
                'pc.codigo',
                'pc.descricao',
                "SUM(CASE WHEN l.tipo = 'receber' THEN l.valor ELSE -l.valor END) as total"
            ])
            ->join('l.planoConta', 'pc')
            ->where('l.dataMovimento >= :dataInicio')
            ->andWhere('l.dataMovimento <= :dataFim')
            ->setParameter('dataInicio', $dataInicio)
            ->setParameter('dataFim', $dataFim)
            ->groupBy('pc.codigo, pc.descricao')
            ->orderBy('pc.codigo', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
