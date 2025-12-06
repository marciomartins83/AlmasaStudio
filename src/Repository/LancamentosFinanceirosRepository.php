<?php

namespace App\Repository;

use App\Entity\LancamentosFinanceiros;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LancamentosFinanceiros>
 */
class LancamentosFinanceirosRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LancamentosFinanceiros::class);
    }

    /**
     * Busca lançamentos com filtros
     *
     * @param array $filtros
     * @return array
     */
    public function findByFiltros(array $filtros): array
    {
        $qb = $this->createQueryBuilder('l')
            ->leftJoin('l.contrato', 'c')
            ->leftJoin('l.imovel', 'i')
            ->leftJoin('l.inquilino', 'inq')
            ->leftJoin('l.proprietario', 'prop')
            ->addSelect('c', 'i', 'inq', 'prop');

        if (!empty($filtros['inquilino'])) {
            $qb->andWhere('l.inquilino = :inquilino')
               ->setParameter('inquilino', $filtros['inquilino']);
        }

        if (!empty($filtros['proprietario'])) {
            $qb->andWhere('l.proprietario = :proprietario')
               ->setParameter('proprietario', $filtros['proprietario']);
        }

        if (!empty($filtros['imovel'])) {
            $qb->andWhere('l.imovel = :imovel')
               ->setParameter('imovel', $filtros['imovel']);
        }

        if (!empty($filtros['contrato'])) {
            $qb->andWhere('l.contrato = :contrato')
               ->setParameter('contrato', $filtros['contrato']);
        }

        if (!empty($filtros['situacao'])) {
            $qb->andWhere('l.situacao = :situacao')
               ->setParameter('situacao', $filtros['situacao']);
        }

        if (!empty($filtros['competenciaInicio'])) {
            $qb->andWhere('l.competencia >= :compInicio')
               ->setParameter('compInicio', $filtros['competenciaInicio']);
        }

        if (!empty($filtros['competenciaFim'])) {
            $qb->andWhere('l.competencia <= :compFim')
               ->setParameter('compFim', $filtros['competenciaFim']);
        }

        if (!empty($filtros['vencimentoInicio'])) {
            $qb->andWhere('l.dataVencimento >= :vencInicio')
               ->setParameter('vencInicio', $filtros['vencimentoInicio']);
        }

        if (!empty($filtros['vencimentoFim'])) {
            $qb->andWhere('l.dataVencimento <= :vencFim')
               ->setParameter('vencFim', $filtros['vencimentoFim']);
        }

        if (isset($filtros['emAtraso']) && $filtros['emAtraso']) {
            $qb->andWhere('l.dataVencimento < :hoje')
               ->andWhere('l.situacao != :pago')
               ->setParameter('hoje', new \DateTime())
               ->setParameter('pago', 'pago');
        }

        $qb->andWhere('l.ativo = true');

        return $qb->orderBy('l.dataVencimento', 'ASC')
                  ->getQuery()
                  ->getResult();
    }

    /**
     * Busca lançamentos em aberto de um inquilino
     *
     * @param int $inquilinoId
     * @return array
     */
    public function findAbertosInquilino(int $inquilinoId): array
    {
        return $this->createQueryBuilder('l')
            ->leftJoin('l.imovel', 'i')
            ->addSelect('i')
            ->where('l.inquilino = :inquilino')
            ->andWhere('l.situacao IN (:situacoes)')
            ->andWhere('l.ativo = true')
            ->setParameter('inquilino', $inquilinoId)
            ->setParameter('situacoes', ['aberto', 'parcial', 'atrasado'])
            ->orderBy('l.dataVencimento', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Busca lançamentos em atraso
     *
     * @return array
     */
    public function findEmAtraso(): array
    {
        $hoje = new \DateTime();

        return $this->createQueryBuilder('l')
            ->leftJoin('l.inquilino', 'inq')
            ->leftJoin('l.imovel', 'i')
            ->addSelect('inq', 'i')
            ->where('l.dataVencimento < :hoje')
            ->andWhere('l.situacao IN (:situacoes)')
            ->andWhere('l.ativo = true')
            ->setParameter('hoje', $hoje)
            ->setParameter('situacoes', ['aberto', 'parcial'])
            ->orderBy('l.dataVencimento', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Busca ficha financeira do inquilino
     *
     * @param int $inquilinoId
     * @param int|null $ano
     * @return array
     */
    public function findFichaFinanceira(int $inquilinoId, ?int $ano = null): array
    {
        $qb = $this->createQueryBuilder('l')
            ->leftJoin('l.imovel', 'i')
            ->leftJoin('l.baixas', 'b')
            ->addSelect('i', 'b')
            ->where('l.inquilino = :inquilino')
            ->andWhere('l.ativo = true')
            ->setParameter('inquilino', $inquilinoId);

        if ($ano) {
            $inicio = new \DateTime("$ano-01-01");
            $fim = new \DateTime("$ano-12-31");
            $qb->andWhere('l.competencia BETWEEN :inicio AND :fim')
               ->setParameter('inicio', $inicio)
               ->setParameter('fim', $fim);
        }

        return $qb->orderBy('l.competencia', 'ASC')
                  ->addOrderBy('l.numeroParcela', 'ASC')
                  ->getQuery()
                  ->getResult();
    }

    /**
     * Calcula totais de um inquilino
     *
     * @param int $inquilinoId
     * @return array
     */
    public function calcularTotaisInquilino(int $inquilinoId): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = "
            SELECT
                COUNT(*) as total_lancamentos,
                COUNT(*) FILTER (WHERE situacao = 'aberto') as em_aberto,
                COUNT(*) FILTER (WHERE situacao = 'pago') as pagos,
                COUNT(*) FILTER (WHERE situacao = 'parcial') as parciais,
                COUNT(*) FILTER (WHERE data_vencimento < CURRENT_DATE AND situacao != 'pago') as em_atraso,
                COALESCE(SUM(valor_total) FILTER (WHERE ativo = true), 0) as valor_total,
                COALESCE(SUM(valor_pago) FILTER (WHERE ativo = true), 0) as valor_pago,
                COALESCE(SUM(valor_saldo) FILTER (WHERE ativo = true AND situacao != 'pago'), 0) as valor_saldo
            FROM lancamentos_financeiros
            WHERE id_inquilino = :inquilino
        ";

        return $conn->fetchAssociative($sql, ['inquilino' => $inquilinoId]);
    }

    /**
     * Estatísticas gerais
     *
     * @param array|null $filtros
     * @return array
     */
    public function getEstatisticas(?array $filtros = null): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $where = "WHERE ativo = true";
        $params = [];

        if (!empty($filtros['competenciaInicio'])) {
            $where .= " AND competencia >= :compInicio";
            $params['compInicio'] = $filtros['competenciaInicio'];
        }

        if (!empty($filtros['competenciaFim'])) {
            $where .= " AND competencia <= :compFim";
            $params['compFim'] = $filtros['competenciaFim'];
        }

        $sql = "
            SELECT
                COUNT(*) as total,
                COUNT(*) FILTER (WHERE situacao = 'aberto') as abertos,
                COUNT(*) FILTER (WHERE situacao = 'pago') as pagos,
                COUNT(*) FILTER (WHERE situacao = 'parcial') as parciais,
                COUNT(*) FILTER (WHERE data_vencimento < CURRENT_DATE AND situacao IN ('aberto', 'parcial')) as em_atraso,
                COALESCE(SUM(valor_total), 0) as valor_total,
                COALESCE(SUM(valor_pago), 0) as valor_pago,
                COALESCE(SUM(valor_saldo) FILTER (WHERE situacao != 'pago'), 0) as valor_em_aberto
            FROM lancamentos_financeiros
            $where
        ";

        return $conn->fetchAssociative($sql, $params);
    }

    /**
     * Gera lançamentos a partir de contratos ativos
     *
     * @param \DateTime $competencia
     * @return array
     */
    public function getLancamentosParaGerar(\DateTime $competencia): array
    {
        $conn = $this->getEntityManager()->getConnection();

        // Buscar contratos ativos que ainda não têm lançamento para esta competência
        $sql = "
            SELECT c.id as contrato_id, c.id_imovel, c.id_pessoa_locatario,
                   c.valor_contrato, c.dia_vencimento, c.taxa_administracao,
                   i.id_pessoa_proprietario
            FROM imoveis_contratos c
            JOIN imoveis i ON i.id = c.id_imovel
            WHERE c.status = 'ativo'
            AND c.ativo = true
            AND c.data_inicio <= :competencia
            AND (c.data_fim IS NULL OR c.data_fim >= :competencia)
            AND NOT EXISTS (
                SELECT 1 FROM lancamentos_financeiros lf
                WHERE lf.id_contrato = c.id
                AND lf.competencia = :competencia
                AND lf.ativo = true
            )
        ";

        return $conn->fetchAllAssociative($sql, [
            'competencia' => $competencia->format('Y-m-d')
        ]);
    }

    /**
     * Busca lançamentos vencendo hoje
     *
     * @return array
     */
    public function findVencendoHoje(): array
    {
        $hoje = new \DateTime();

        return $this->createQueryBuilder('l')
            ->leftJoin('l.inquilino', 'inq')
            ->leftJoin('l.imovel', 'i')
            ->addSelect('inq', 'i')
            ->where('l.dataVencimento = :hoje')
            ->andWhere('l.situacao IN (:situacoes)')
            ->andWhere('l.ativo = true')
            ->setParameter('hoje', $hoje)
            ->setParameter('situacoes', ['aberto', 'parcial'])
            ->orderBy('l.inquilino', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Busca lançamentos por contrato
     *
     * @param int $contratoId
     * @return array
     */
    public function findByContrato(int $contratoId): array
    {
        return $this->createQueryBuilder('l')
            ->leftJoin('l.baixas', 'b')
            ->addSelect('b')
            ->where('l.contrato = :contrato')
            ->andWhere('l.ativo = true')
            ->setParameter('contrato', $contratoId)
            ->orderBy('l.competencia', 'ASC')
            ->addOrderBy('l.numeroParcela', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
