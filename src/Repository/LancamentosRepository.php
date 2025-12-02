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
        $qb = $this->createQueryBuilder('l')
            ->select([
                'IDENTITY(l.proprietario) as id_proprietario',
                'IDENTITY(l.imovel) as id_imovel',
                'IDENTITY(l.inquilino) as id_inquilino',
                'IDENTITY(l.planoConta) as id_plano_conta',
                'MONTH(l.competencia) as mes',
                'SUM(CASE WHEN l.tipoSinal = \'C\' THEN CAST(l.valor AS float) ELSE -CAST(l.valor AS float) END) as total'
            ])
            ->join('l.planoConta', 'pc')
            ->where('YEAR(l.competencia) = :ano')
            ->andWhere('pc.entraInforme = :entraInforme')
            ->andWhere('l.proprietario IS NOT NULL')
            ->andWhere('l.imovel IS NOT NULL')
            ->andWhere('l.inquilino IS NOT NULL')
            ->setParameter('ano', $ano)
            ->setParameter('entraInforme', true)
            ->groupBy('l.proprietario, l.imovel, l.inquilino, l.planoConta, MONTH(l.competencia)')
            ->orderBy('l.proprietario', 'ASC')
            ->addOrderBy('l.imovel', 'ASC')
            ->addOrderBy('l.inquilino', 'ASC');

        if ($proprietarioInicial !== null) {
            $qb->andWhere('l.proprietario >= :propInicial')
                ->setParameter('propInicial', $proprietarioInicial);
        }

        if ($proprietarioFinal !== null) {
            $qb->andWhere('l.proprietario <= :propFinal')
                ->setParameter('propFinal', $proprietarioFinal);
        }

        return $qb->getQuery()->getResult();
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
        $result = $this->createQueryBuilder('l')
            ->select('DISTINCT YEAR(l.competencia) as ano')
            ->orderBy('ano', 'DESC')
            ->getQuery()
            ->getResult();

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
