<?php

namespace App\Repository;

use App\Entity\Boletos;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Boletos>
 *
 * @method Boletos|null find($id, $lockMode = null, $lockVersion = null)
 * @method Boletos|null findOneBy(array $criteria, array $orderBy = null)
 * @method Boletos[]    findAll()
 * @method Boletos[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BoletosRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Boletos::class);
    }

    /**
     * Busca último nosso número usado para uma configuração
     */
    public function findUltimoNossoNumero(int $configId): ?string
    {
        $result = $this->createQueryBuilder('b')
            ->select('b.nossoNumero')
            ->where('b.configuracaoApi = :configId')
            ->setParameter('configId', $configId)
            ->orderBy('b.nossoNumero', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $result ? $result['nossoNumero'] : null;
    }

    /**
     * Cria QueryBuilder base com joins necessarios para paginacao
     */
    public function createBaseQueryBuilder(): \Doctrine\ORM\QueryBuilder
    {
        return $this->createQueryBuilder('b')
            ->leftJoin('b.pessoaPagador', 'p')
            ->leftJoin('b.imovel', 'i')
            ->leftJoin('b.configuracaoApi', 'c')
            ->addSelect('p', 'i', 'c');
    }

    /**
     * Busca boletos com filtros
     */
    public function findByFiltros(array $filtros): array
    {
        $qb = $this->createQueryBuilder('b')
            ->leftJoin('b.pessoaPagador', 'p')
            ->leftJoin('b.imovel', 'i')
            ->leftJoin('b.configuracaoApi', 'c')
            ->addSelect('p', 'i', 'c');

        if (!empty($filtros['status'])) {
            $qb->andWhere('b.status = :status')
                ->setParameter('status', $filtros['status']);
        }

        if (!empty($filtros['pagador_id'])) {
            $qb->andWhere('b.pessoaPagador = :pagadorId')
                ->setParameter('pagadorId', $filtros['pagador_id']);
        }

        if (!empty($filtros['imovel_id'])) {
            $qb->andWhere('b.imovel = :imovelId')
                ->setParameter('imovelId', $filtros['imovel_id']);
        }

        if (!empty($filtros['configuracao_id'])) {
            $qb->andWhere('b.configuracaoApi = :configId')
                ->setParameter('configId', $filtros['configuracao_id']);
        }

        if (!empty($filtros['data_vencimento_inicio'])) {
            $qb->andWhere('b.dataVencimento >= :dataInicio')
                ->setParameter('dataInicio', $filtros['data_vencimento_inicio']);
        }

        if (!empty($filtros['data_vencimento_fim'])) {
            $qb->andWhere('b.dataVencimento <= :dataFim')
                ->setParameter('dataFim', $filtros['data_vencimento_fim']);
        }

        if (!empty($filtros['nosso_numero'])) {
            $qb->andWhere('b.nossoNumero LIKE :nossoNumero')
                ->setParameter('nossoNumero', '%' . $filtros['nosso_numero'] . '%');
        }

        $orderBy = $filtros['order_by'] ?? 'b.dataVencimento';
        $orderDir = $filtros['order_dir'] ?? 'DESC';
        $qb->orderBy($orderBy, $orderDir);

        if (!empty($filtros['limit'])) {
            $qb->setMaxResults($filtros['limit']);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Busca boletos pendentes de registro
     */
    public function findPendentesRegistro(): array
    {
        return $this->createQueryBuilder('b')
            ->leftJoin('b.pessoaPagador', 'p')
            ->leftJoin('b.configuracaoApi', 'c')
            ->addSelect('p', 'c')
            ->where('b.status = :status')
            ->setParameter('status', Boletos::STATUS_PENDENTE)
            ->orderBy('b.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Busca boletos vencidos não pagos
     */
    public function findVencidos(): array
    {
        return $this->createQueryBuilder('b')
            ->leftJoin('b.pessoaPagador', 'p')
            ->leftJoin('b.imovel', 'i')
            ->addSelect('p', 'i')
            ->where('b.dataVencimento < :hoje')
            ->andWhere('b.status IN (:statusAbertos)')
            ->setParameter('hoje', new \DateTime('today'))
            ->setParameter('statusAbertos', [Boletos::STATUS_REGISTRADO, Boletos::STATUS_PENDENTE])
            ->orderBy('b.dataVencimento', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Busca boletos por pagador
     */
    public function findByPagador(int $pessoaId): array
    {
        return $this->createQueryBuilder('b')
            ->leftJoin('b.imovel', 'i')
            ->leftJoin('b.configuracaoApi', 'c')
            ->addSelect('i', 'c')
            ->where('b.pessoaPagador = :pessoaId')
            ->setParameter('pessoaId', $pessoaId)
            ->orderBy('b.dataVencimento', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Busca boletos por lançamento financeiro
     */
    public function findByLancamento(int $lancamentoId): array
    {
        return $this->createQueryBuilder('b')
            ->where('b.lancamentoFinanceiro = :lancamentoId')
            ->setParameter('lancamentoId', $lancamentoId)
            ->orderBy('b.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Busca boletos para consulta de status (registrados há mais de X horas)
     */
    public function findParaConsultaStatus(int $horasDesdeRegistro = 1): array
    {
        $dataLimite = (new \DateTime())->modify("-{$horasDesdeRegistro} hours");

        return $this->createQueryBuilder('b')
            ->leftJoin('b.configuracaoApi', 'c')
            ->addSelect('c')
            ->where('b.status = :status')
            ->andWhere('b.dataRegistro IS NOT NULL')
            ->andWhere('b.dataRegistro <= :dataLimite')
            ->setParameter('status', Boletos::STATUS_REGISTRADO)
            ->setParameter('dataLimite', $dataLimite)
            ->orderBy('b.dataRegistro', 'ASC')
            ->setMaxResults(100)
            ->getQuery()
            ->getResult();
    }

    /**
     * Busca estatísticas de boletos
     */
    public function getEstatisticas(?int $configId = null): array
    {
        $qb = $this->createQueryBuilder('b')
            ->select(
                'COUNT(b.id) as total',
                "SUM(CASE WHEN b.status = 'PENDENTE' THEN 1 ELSE 0 END) as pendentes",
                "SUM(CASE WHEN b.status = 'REGISTRADO' THEN 1 ELSE 0 END) as registrados",
                "SUM(CASE WHEN b.status = 'PAGO' THEN 1 ELSE 0 END) as pagos",
                "SUM(CASE WHEN b.status = 'VENCIDO' THEN 1 ELSE 0 END) as vencidos",
                "SUM(CASE WHEN b.status = 'BAIXADO' THEN 1 ELSE 0 END) as baixados",
                "SUM(CASE WHEN b.status = 'ERRO' THEN 1 ELSE 0 END) as erros",
                'SUM(CAST(b.valorNominal AS DECIMAL)) as valor_total',
                "SUM(CASE WHEN b.status = 'PAGO' THEN CAST(b.valorPago AS DECIMAL) ELSE 0 END) as valor_recebido"
            );

        if ($configId !== null) {
            $qb->where('b.configuracaoApi = :configId')
                ->setParameter('configId', $configId);
        }

        return $qb->getQuery()->getSingleResult();
    }

    /**
     * Busca boletos com erro para reprocessamento
     */
    public function findComErroParaReprocessar(int $maxTentativas = 3): array
    {
        return $this->createQueryBuilder('b')
            ->leftJoin('b.configuracaoApi', 'c')
            ->addSelect('c')
            ->where('b.status = :status')
            ->andWhere('b.tentativasRegistro < :maxTentativas')
            ->setParameter('status', Boletos::STATUS_ERRO)
            ->setParameter('maxTentativas', $maxTentativas)
            ->orderBy('b.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Busca boletos por período de vencimento
     */
    public function findByPeriodoVencimento(\DateTimeInterface $inicio, \DateTimeInterface $fim): array
    {
        return $this->createQueryBuilder('b')
            ->leftJoin('b.pessoaPagador', 'p')
            ->leftJoin('b.imovel', 'i')
            ->addSelect('p', 'i')
            ->where('b.dataVencimento BETWEEN :inicio AND :fim')
            ->setParameter('inicio', $inicio)
            ->setParameter('fim', $fim)
            ->orderBy('b.dataVencimento', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
