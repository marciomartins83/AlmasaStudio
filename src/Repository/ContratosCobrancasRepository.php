<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ContratosCobrancas;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ContratosCobrancas>
 */
class ContratosCobrancasRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ContratosCobrancas::class);
    }

    public function save(ContratosCobrancas $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ContratosCobrancas $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Busca cobrança por contrato e competência
     * Usado para verificar duplicidade
     */
    public function findByContratoCompetencia(int $contratoId, string $competencia): ?ContratosCobrancas
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.contrato = :contratoId')
            ->andWhere('c.competencia = :competencia')
            ->setParameter('contratoId', $contratoId)
            ->setParameter('competencia', $competencia)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Busca cobranças pendentes para uma data de vencimento
     *
     * @return ContratosCobrancas[]
     */
    public function findPendentesPorVencimento(
        \DateTime $dataVencimento,
        bool $incluirAutomaticos = false
    ): array {
        $qb = $this->createQueryBuilder('c')
            ->innerJoin('c.contrato', 'ct')
            ->andWhere('c.dataVencimento = :dataVencimento')
            ->andWhere('c.status IN (:status)')
            ->setParameter('dataVencimento', $dataVencimento->format('Y-m-d'))
            ->setParameter('status', [
                ContratosCobrancas::STATUS_PENDENTE,
                ContratosCobrancas::STATUS_BOLETO_GERADO
            ]);

        if (!$incluirAutomaticos) {
            $qb->andWhere('c.bloqueadoRotinaAuto = false');
        }

        return $qb->orderBy('ct.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Busca cobranças por contrato
     *
     * @return ContratosCobrancas[]
     */
    public function findByContrato(int $contratoId): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.contrato = :contratoId')
            ->setParameter('contratoId', $contratoId)
            ->orderBy('c.competencia', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Busca cobranças por período de vencimento
     *
     * @return ContratosCobrancas[]
     */
    public function findByPeriodoVencimento(\DateTime $inicio, \DateTime $fim): array
    {
        return $this->createQueryBuilder('c')
            ->innerJoin('c.contrato', 'ct')
            ->innerJoin('ct.imovel', 'i')
            ->innerJoin('ct.pessoaLocatario', 'p')
            ->andWhere('c.dataVencimento BETWEEN :inicio AND :fim')
            ->setParameter('inicio', $inicio->format('Y-m-d'))
            ->setParameter('fim', $fim->format('Y-m-d'))
            ->orderBy('c.dataVencimento', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Busca cobranças por status
     *
     * @return ContratosCobrancas[]
     */
    public function findByStatus(string $status, int $limit = 100): array
    {
        return $this->createQueryBuilder('c')
            ->innerJoin('c.contrato', 'ct')
            ->andWhere('c.status = :status')
            ->setParameter('status', $status)
            ->orderBy('c.dataVencimento', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Busca cobranças com filtros para listagem
     *
     * @return array{cobrancas: ContratosCobrancas[], total: int}
     */
    public function findByFiltros(
        array $filtros = [],
        int $limit = 20,
        int $offset = 0
    ): array {
        $qb = $this->createQueryBuilder('c')
            ->innerJoin('c.contrato', 'ct')
            ->innerJoin('ct.imovel', 'i')
            ->leftJoin('ct.pessoaLocatario', 'p');

        $this->aplicarFiltrosCobranca($qb, $filtros);

        // Contar total
        $countQb = clone $qb;
        $total = (int) $countQb->select('COUNT(c.id)')
            ->getQuery()
            ->getSingleScalarResult();

        // Buscar resultados paginados
        $cobrancas = $qb
            ->orderBy('c.dataVencimento', 'ASC')
            ->addOrderBy('ct.id', 'ASC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return [
            'cobrancas' => $cobrancas,
            'total' => $total
        ];
    }

    /**
     * Retorna estatísticas de cobranças
     */
    public function getEstatisticas(?\DateTime $dataVencimento = null): array
    {
        $qb = $this->createQueryBuilder('c')
            ->select([
                'c.status',
                'COUNT(c.id) as quantidade',
                'SUM(c.valorTotal) as valor_total'
            ])
            ->groupBy('c.status');

        if ($dataVencimento) {
            $qb->andWhere('c.dataVencimento = :dataVencimento')
                ->setParameter('dataVencimento', $dataVencimento->format('Y-m-d'));
        }

        $resultados = $qb->getQuery()->getResult();

        $estatisticas = [
            'pendentes' => ['quantidade' => 0, 'valor' => 0],
            'boleto_gerado' => ['quantidade' => 0, 'valor' => 0],
            'enviados' => ['quantidade' => 0, 'valor' => 0],
            'pagos' => ['quantidade' => 0, 'valor' => 0],
            'cancelados' => ['quantidade' => 0, 'valor' => 0],
            'total' => ['quantidade' => 0, 'valor' => 0]
        ];

        foreach ($resultados as $row) {
            $key = match ($row['status']) {
                ContratosCobrancas::STATUS_PENDENTE => 'pendentes',
                ContratosCobrancas::STATUS_BOLETO_GERADO => 'boleto_gerado',
                ContratosCobrancas::STATUS_ENVIADO => 'enviados',
                ContratosCobrancas::STATUS_PAGO => 'pagos',
                ContratosCobrancas::STATUS_CANCELADO => 'cancelados',
                default => null
            };

            if ($key) {
                $estatisticas[$key]['quantidade'] = (int) $row['quantidade'];
                $estatisticas[$key]['valor'] = (float) ($row['valor_total'] ?? 0);
            }

            $estatisticas['total']['quantidade'] += (int) $row['quantidade'];
            $estatisticas['total']['valor'] += (float) ($row['valor_total'] ?? 0);
        }

        return $estatisticas;
    }

    /**
     * Conta cobranças por tipo de envio
     */
    public function contarPorTipoEnvio(\DateTime $dataVencimento): array
    {
        $result = $this->createQueryBuilder('c')
            ->select([
                'COALESCE(c.tipoEnvio, \'PENDENTE\') as tipo',
                'COUNT(c.id) as quantidade'
            ])
            ->andWhere('c.dataVencimento = :dataVencimento')
            ->setParameter('dataVencimento', $dataVencimento->format('Y-m-d'))
            ->groupBy('c.tipoEnvio')
            ->getQuery()
            ->getResult();

        $contagem = [
            'AUTOMATICO' => 0,
            'MANUAL' => 0,
            'PENDENTE' => 0
        ];

        foreach ($result as $row) {
            $contagem[$row['tipo']] = (int) $row['quantidade'];
        }

        return $contagem;
    }

    /**
     * Aplica os filtros de listagem a um QueryBuilder (alias 'c').
     * Centralizado para que a lista e os cards de resumo usem EXATAMENTE os mesmos filtros.
     */
    private function aplicarFiltrosCobranca(\Doctrine\ORM\QueryBuilder $qb, array $filtros): void
    {
        if (!empty($filtros['data_vencimento'])) {
            $qb->andWhere('c.dataVencimento = :dataVencimento')
                ->setParameter('dataVencimento', $filtros['data_vencimento']->format('Y-m-d'));
        }
        if (!empty($filtros['vencimento_inicio'])) {
            $qb->andWhere('c.dataVencimento >= :vencimentoInicio')
                ->setParameter('vencimentoInicio', $filtros['vencimento_inicio']->format('Y-m-d'));
        }
        if (!empty($filtros['vencimento_fim'])) {
            $qb->andWhere('c.dataVencimento <= :vencimentoFim')
                ->setParameter('vencimentoFim', $filtros['vencimento_fim']->format('Y-m-d'));
        }
        if (!empty($filtros['status'])) {
            if (is_array($filtros['status'])) {
                $qb->andWhere('c.status IN (:status)')->setParameter('status', $filtros['status']);
            } else {
                $qb->andWhere('c.status = :status')->setParameter('status', $filtros['status']);
            }
        }
        if (!empty($filtros['competencia'])) {
            $qb->andWhere('c.competencia = :competencia')->setParameter('competencia', $filtros['competencia']);
        }
        if (!empty($filtros['contrato_id'])) {
            $qb->andWhere('c.contrato = :contratoId')->setParameter('contratoId', $filtros['contrato_id']);
        }
        if (isset($filtros['tipo_envio'])) {
            if ($filtros['tipo_envio'] === null) {
                $qb->andWhere('c.tipoEnvio IS NULL');
            } else {
                $qb->andWhere('c.tipoEnvio = :tipoEnvio')->setParameter('tipoEnvio', $filtros['tipo_envio']);
            }
        }
        if (isset($filtros['bloqueado_rotina_auto'])) {
            $qb->andWhere('c.bloqueadoRotinaAuto = :bloqueadoRotinaAuto')
                ->setParameter('bloqueadoRotinaAuto', $filtros['bloqueado_rotina_auto']);
        }
        if (!empty($filtros['excluir_automaticos'])) {
            $qb->andWhere('(c.tipoEnvio IS NULL OR c.tipoEnvio != :tipoAuto)')
                ->setParameter('tipoAuto', ContratosCobrancas::TIPO_ENVIO_AUTOMATICO);
        }
    }

    /**
     * Resumo (contagem + valor + quebra por tipo de envio) usando EXATAMENTE os
     * mesmos filtros da listagem — para os cards baterem com a lista (antes os cards
     * usavam só a data de início do período, mostrando menos cobranças que a lista).
     *
     * @return array{total: array{quantidade:int, valor:float}, tipo: array{automatico:int, manual:int}}
     */
    public function getResumoPorFiltros(array $filtros): array
    {
        $qb = $this->createQueryBuilder('c')
            ->select('COUNT(c.id) as quantidade', 'COALESCE(SUM(c.valorTotal), 0) as valor');
        $this->aplicarFiltrosCobranca($qb, $filtros);
        $row = $qb->getQuery()->getSingleResult();

        $qb2 = $this->createQueryBuilder('c')
            ->select(
                'SUM(CASE WHEN c.tipoEnvio = :tEnvAuto THEN 1 ELSE 0 END) as automatico',
                'SUM(CASE WHEN c.tipoEnvio IS NULL OR c.tipoEnvio != :tEnvAuto THEN 1 ELSE 0 END) as manual'
            )
            ->setParameter('tEnvAuto', ContratosCobrancas::TIPO_ENVIO_AUTOMATICO);
        $this->aplicarFiltrosCobranca($qb2, $filtros);
        $row2 = $qb2->getQuery()->getSingleResult();

        return [
            'total' => [
                'quantidade' => (int) $row['quantidade'],
                'valor' => (float) $row['valor'],
            ],
            'tipo' => [
                'automatico' => (int) ($row2['automatico'] ?? 0),
                'manual' => (int) ($row2['manual'] ?? 0),
            ],
        ];
    }

    /**
     * IDs das cobranças que casam com os MESMOS filtros da listagem (todas as páginas),
     * para gerar o relatório de conferência / emitir sobre o filtro inteiro sem o
     * usuário ter que marcar uma a uma numa tela paginada.
     *
     * Com $apenasEmitiveis (default), restringe às cobranças que podem ser enviadas
     * manualmente (PENDENTE / BOLETO_GERADO) — o relatório de conferência só faz
     * sentido para essas.
     *
     * @return int[]
     */
    public function getIdsPorFiltros(array $filtros, bool $apenasEmitiveis = true): array
    {
        $qb = $this->createQueryBuilder('c')->select('c.id');
        $this->aplicarFiltrosCobranca($qb, $filtros);

        if ($apenasEmitiveis) {
            $qb->andWhere('c.status IN (:statusEmitiveis)')
                ->setParameter('statusEmitiveis', [
                    ContratosCobrancas::STATUS_PENDENTE,
                    ContratosCobrancas::STATUS_BOLETO_GERADO,
                ]);
        }

        $qb->orderBy('c.dataVencimento', 'ASC')->addOrderBy('c.id', 'ASC');

        return array_map(static fn ($r) => (int) $r['id'], $qb->getQuery()->getScalarResult());
    }
}
