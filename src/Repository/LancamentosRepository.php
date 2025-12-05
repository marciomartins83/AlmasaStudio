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
     * Busca lançamentos por período
     *
     * @return Lancamentos[]
     */
    public function findByPeriodo(\DateTimeInterface $dataInicio, \DateTimeInterface $dataFim): array
    {
        return $this->createQueryBuilder('l')
            ->where('l.data >= :dataInicio')
            ->andWhere('l.data <= :dataFim')
            ->setParameter('dataInicio', $dataInicio)
            ->setParameter('dataFim', $dataFim)
            ->orderBy('l.data', 'ASC')
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
        $dataInicio = new \DateTime("$ano-01-01");
        $dataFim = new \DateTime("$ano-12-31");

        return $this->createQueryBuilder('l')
            ->where('l.competencia >= :dataInicio')
            ->andWhere('l.competencia <= :dataFim')
            ->setParameter('dataInicio', $dataInicio)
            ->setParameter('dataFim', $dataFim)
            ->orderBy('l.competencia', 'ASC')
            ->addOrderBy('l.data', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Busca lançamentos para processamento de informe de rendimentos
     * Agrupa por proprietário, imóvel, inquilino e conta
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
                    EXTRACT(MONTH FROM l.competencia)::integer as mes,
                    SUM(CASE WHEN l.tipo_sinal = 'C' THEN l.valor ELSE -l.valor END) as total
                FROM lancamentos l
                INNER JOIN plano_contas pc ON pc.id = l.id_plano_conta
                WHERE EXTRACT(YEAR FROM l.competencia) = :ano
                  AND pc.entra_informe = true
                  AND l.id_proprietario IS NOT NULL
                  AND l.id_imovel IS NOT NULL
                  AND l.id_inquilino IS NOT NULL";

        $params = ['ano' => $ano];

        if ($proprietarioInicial !== null) {
            $sql .= " AND l.id_proprietario >= :propInicial";
            $params['propInicial'] = $proprietarioInicial;
        }

        if ($proprietarioFinal !== null) {
            $sql .= " AND l.id_proprietario <= :propFinal";
            $params['propFinal'] = $proprietarioFinal;
        }

        $sql .= " GROUP BY l.id_proprietario, l.id_imovel, l.id_inquilino, l.id_plano_conta, EXTRACT(MONTH FROM l.competencia)
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
        $dataInicio = new \DateTime("$ano-01-01");
        $dataFim = new \DateTime("$ano-12-31");

        return $this->createQueryBuilder('l')
            ->where('l.proprietario = :proprietario')
            ->andWhere('l.competencia >= :dataInicio')
            ->andWhere('l.competencia <= :dataFim')
            ->setParameter('proprietario', $proprietarioId)
            ->setParameter('dataInicio', $dataInicio)
            ->setParameter('dataFim', $dataFim)
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
            ->andWhere('l.data >= :dataInicio')
            ->andWhere('l.data <= :dataFim')
            ->setParameter('imovel', $imovelId)
            ->setParameter('dataInicio', $dataInicio)
            ->setParameter('dataFim', $dataFim)
            ->orderBy('l.data', 'ASC')
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
            ->andWhere('l.data >= :dataInicio')
            ->andWhere('l.data <= :dataFim')
            ->setParameter('inquilino', $inquilinoId)
            ->setParameter('dataInicio', $dataInicio)
            ->setParameter('dataFim', $dataFim)
            ->orderBy('l.data', 'ASC')
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
        $sql = 'SELECT DISTINCT EXTRACT(YEAR FROM competencia)::integer as ano
                FROM lancamentos
                WHERE competencia IS NOT NULL
                ORDER BY ano DESC';

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
                'SUM(CASE WHEN l.tipoSinal = \'C\' THEN CAST(l.valor AS float) ELSE -CAST(l.valor AS float) END) as total'
            ])
            ->join('l.planoConta', 'pc')
            ->where('l.data >= :dataInicio')
            ->andWhere('l.data <= :dataFim')
            ->setParameter('dataInicio', $dataInicio)
            ->setParameter('dataFim', $dataFim)
            ->groupBy('pc.codigo, pc.descricao')
            ->orderBy('pc.codigo', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
