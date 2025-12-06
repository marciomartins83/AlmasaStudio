<?php

namespace App\Repository;

use App\Entity\AcordosFinanceiros;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AcordosFinanceiros>
 */
class AcordosFinanceirosRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AcordosFinanceiros::class);
    }

    /**
     * Busca acordos de um inquilino
     *
     * @param int $inquilinoId
     * @return array
     */
    public function findByInquilino(int $inquilinoId): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.inquilino = :inquilino')
            ->setParameter('inquilino', $inquilinoId)
            ->orderBy('a.dataAcordo', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Busca acordos ativos
     *
     * @return array
     */
    public function findAtivos(): array
    {
        return $this->createQueryBuilder('a')
            ->leftJoin('a.inquilino', 'inq')
            ->addSelect('inq')
            ->where('a.situacao = :situacao')
            ->setParameter('situacao', 'ativo')
            ->orderBy('a.dataAcordo', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Obtém próximo número de acordo
     *
     * @return int
     */
    public function getProximoNumeroAcordo(): int
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "SELECT nextval('seq_numero_acordo')";

        return (int) $conn->fetchOne($sql);
    }

    /**
     * Estatísticas de acordos
     *
     * @return array
     */
    public function getEstatisticas(): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = "
            SELECT
                COUNT(*) as total,
                COUNT(*) FILTER (WHERE situacao = 'ativo') as ativos,
                COUNT(*) FILTER (WHERE situacao = 'quitado') as quitados,
                COUNT(*) FILTER (WHERE situacao = 'cancelado') as cancelados,
                COALESCE(SUM(valor_total_acordo), 0) as valor_total,
                COALESCE(SUM(valor_total_acordo) FILTER (WHERE situacao = 'ativo'), 0) as valor_ativos
            FROM acordos_financeiros
        ";

        return $conn->fetchAssociative($sql);
    }
}
