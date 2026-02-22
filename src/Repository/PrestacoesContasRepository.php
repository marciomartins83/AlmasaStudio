<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\PrestacoesContas;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PrestacoesContas>
 */
class PrestacoesContasRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PrestacoesContas::class);
    }

    /**
     * Cria QueryBuilder base com joins necessarios para paginacao
     */
    public function createBaseQueryBuilder(): \Doctrine\ORM\QueryBuilder
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.proprietario', 'prop')
            ->leftJoin('p.imovel', 'i')
            ->addSelect('prop', 'i');
    }

    /**
     * Busca prestações com filtros diversos
     *
     * @param array $filtros [proprietario, imovel, status, ano, dataInicio, dataFim]
     * @return PrestacoesContas[]
     */
    public function findByFiltros(array $filtros): array
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.proprietario', 'prop')
            ->leftJoin('p.imovel', 'i')
            ->addSelect('prop', 'i')
            ->orderBy('p.ano', 'DESC')
            ->addOrderBy('p.numero', 'DESC');

        if (!empty($filtros['proprietario'])) {
            $qb->andWhere('p.proprietario = :proprietario')
               ->setParameter('proprietario', $filtros['proprietario']);
        }

        if (!empty($filtros['imovel'])) {
            $qb->andWhere('p.imovel = :imovel')
               ->setParameter('imovel', $filtros['imovel']);
        }

        if (!empty($filtros['status'])) {
            if (is_array($filtros['status'])) {
                $qb->andWhere('p.status IN (:status)')
                   ->setParameter('status', $filtros['status']);
            } else {
                $qb->andWhere('p.status = :status')
                   ->setParameter('status', $filtros['status']);
            }
        }

        if (!empty($filtros['ano'])) {
            $qb->andWhere('p.ano = :ano')
               ->setParameter('ano', $filtros['ano']);
        }

        if (!empty($filtros['dataInicio'])) {
            $qb->andWhere('p.dataInicio >= :dataInicio')
               ->setParameter('dataInicio', $filtros['dataInicio']);
        }

        if (!empty($filtros['dataFim'])) {
            $qb->andWhere('p.dataFim <= :dataFim')
               ->setParameter('dataFim', $filtros['dataFim']);
        }

        if (!empty($filtros['competencia'])) {
            $qb->andWhere('p.competencia = :competencia')
               ->setParameter('competencia', $filtros['competencia']);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Busca histórico de prestações por proprietário
     *
     * @return PrestacoesContas[]
     */
    public function findByProprietario(int $idProprietario, ?int $limit = null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.imovel', 'i')
            ->addSelect('i')
            ->where('p.proprietario = :proprietario')
            ->setParameter('proprietario', $idProprietario)
            ->orderBy('p.ano', 'DESC')
            ->addOrderBy('p.numero', 'DESC');

        if ($limit) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Gera próximo número sequencial para o ano
     */
    public function getProximoNumero(int $ano): int
    {
        $result = $this->createQueryBuilder('p')
            ->select('MAX(p.numero)')
            ->where('p.ano = :ano')
            ->setParameter('ano', $ano)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? ((int) $result + 1) : 1;
    }

    /**
     * Verifica se existe prestação para o mesmo proprietário/imóvel/período
     */
    public function existePrestacaoDuplicada(
        int $idProprietario,
        \DateTimeInterface $dataInicio,
        \DateTimeInterface $dataFim,
        ?int $idImovel = null,
        ?int $idExcluir = null
    ): bool {
        $qb = $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.proprietario = :proprietario')
            ->andWhere('p.dataInicio = :dataInicio')
            ->andWhere('p.dataFim = :dataFim')
            ->andWhere('p.status != :cancelado')
            ->setParameter('proprietario', $idProprietario)
            ->setParameter('dataInicio', $dataInicio)
            ->setParameter('dataFim', $dataFim)
            ->setParameter('cancelado', PrestacoesContas::STATUS_CANCELADO);

        if ($idImovel) {
            $qb->andWhere('p.imovel = :imovel')
               ->setParameter('imovel', $idImovel);
        } else {
            $qb->andWhere('p.imovel IS NULL');
        }

        if ($idExcluir) {
            $qb->andWhere('p.id != :idExcluir')
               ->setParameter('idExcluir', $idExcluir);
        }

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }

    /**
     * Retorna estatísticas de prestações
     */
    public function getEstatisticas(?int $ano = null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->select([
                'COUNT(p.id) as total',
                "SUM(CASE WHEN p.status = 'gerado' THEN 1 ELSE 0 END) as geradas",
                "SUM(CASE WHEN p.status = 'aprovado' THEN 1 ELSE 0 END) as aguardando_repasse",
                "SUM(CASE WHEN p.status = 'pago' THEN 1 ELSE 0 END) as pagas",
                "SUM(CASE WHEN p.status = 'cancelado' THEN 1 ELSE 0 END) as canceladas",
                'SUM(p.valorRepasse) as valor_total_repasse',
            ]);

        if ($ano) {
            $qb->where('p.ano = :ano')
               ->setParameter('ano', $ano);
        }

        $result = $qb->getQuery()->getSingleResult();

        return [
            'total' => (int) ($result['total'] ?? 0),
            'geradas' => (int) ($result['geradas'] ?? 0),
            'aguardando_repasse' => (int) ($result['aguardando_repasse'] ?? 0),
            'pagas' => (int) ($result['pagas'] ?? 0),
            'canceladas' => (int) ($result['canceladas'] ?? 0),
            'valor_total_repasse' => (float) ($result['valor_total_repasse'] ?? 0),
        ];
    }

    /**
     * Retorna estatísticas do mês atual
     */
    public function getEstatisticasMesAtual(): array
    {
        $inicioMes = new \DateTime('first day of this month');
        $fimMes = new \DateTime('last day of this month');

        $qb = $this->createQueryBuilder('p')
            ->select([
                'COUNT(p.id) as total',
                "SUM(CASE WHEN p.status = 'pago' THEN 1 ELSE 0 END) as pagas",
                "SUM(CASE WHEN p.status = 'pago' THEN p.valorRepasse ELSE 0 END) as valor_pago",
            ])
            ->where('p.createdAt >= :inicio')
            ->andWhere('p.createdAt <= :fim')
            ->setParameter('inicio', $inicioMes)
            ->setParameter('fim', $fimMes);

        $result = $qb->getQuery()->getSingleResult();

        return [
            'total' => (int) ($result['total'] ?? 0),
            'pagas' => (int) ($result['pagas'] ?? 0),
            'valor_pago' => (float) ($result['valor_pago'] ?? 0),
        ];
    }

    /**
     * Busca prestação por ID com itens carregados
     */
    public function findByIdComItens(int $id): ?PrestacoesContas
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.proprietario', 'prop')
            ->leftJoin('p.imovel', 'i')
            ->leftJoin('p.itens', 'it')
            ->leftJoin('it.planoConta', 'pc')
            ->leftJoin('it.imovel', 'ii')
            ->addSelect('prop', 'i', 'it', 'pc', 'ii')
            ->where('p.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Retorna anos com prestações (para filtro)
     */
    public function getAnosDisponiveis(): array
    {
        $result = $this->createQueryBuilder('p')
            ->select('DISTINCT p.ano')
            ->orderBy('p.ano', 'DESC')
            ->getQuery()
            ->getScalarResult();

        return array_column($result, 'ano');
    }
}
