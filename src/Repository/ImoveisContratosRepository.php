<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ImoveisContratos;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ImoveisContratos>
 *
 * @method ImoveisContratos|null find($id, $lockMode = null, $lockVersion = null)
 * @method ImoveisContratos|null findOneBy(array $criteria, array $orderBy = null)
 * @method ImoveisContratos[]    findAll()
 * @method ImoveisContratos[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ImoveisContratosRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ImoveisContratos::class);
    }

    public function findByFiltros(array $filtros): array
    {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.imovel', 'i')
            ->leftJoin('c.pessoaLocatario', 'loc')
            ->leftJoin('c.pessoaFiador', 'fia')
            ->addSelect('i', 'loc', 'fia');

        if (!empty($filtros['status'])) {
            $qb->andWhere('c.status = :status')
               ->setParameter('status', $filtros['status']);
        }

        if (!empty($filtros['tipoContrato'])) {
            $qb->andWhere('c.tipoContrato = :tipo')
               ->setParameter('tipo', $filtros['tipoContrato']);
        }

        if (!empty($filtros['idImovel'])) {
            $qb->andWhere('c.imovel = :imovel')
               ->setParameter('imovel', $filtros['idImovel']);
        }

        if (!empty($filtros['idLocatario'])) {
            $qb->andWhere('c.pessoaLocatario = :locatario')
               ->setParameter('locatario', $filtros['idLocatario']);
        }

        if (isset($filtros['ativo'])) {
            $ativo = $filtros['ativo'] === 'true' || $filtros['ativo'] === true;
            $qb->andWhere('c.ativo = :ativo')
               ->setParameter('ativo', $ativo);
        }

        return $qb->orderBy('c.dataInicio', 'DESC')
                  ->getQuery()
                  ->getResult();
    }

    public function findContratosAtivosImovel(int $imovelId): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.imovel = :imovel')
            ->andWhere('c.status = :status')
            ->andWhere('c.ativo = true')
            ->setParameter('imovel', $imovelId)
            ->setParameter('status', 'ativo')
            ->orderBy('c.dataInicio', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findContratoVigenteImovel(int $imovelId): ?ImoveisContratos
    {
        $hoje = new \DateTime();

        return $this->createQueryBuilder('c')
            ->where('c.imovel = :imovel')
            ->andWhere('c.status = :status')
            ->andWhere('c.ativo = true')
            ->andWhere('c.dataInicio <= :hoje')
            ->andWhere('c.dataFim IS NULL OR c.dataFim >= :hoje')
            ->setParameter('imovel', $imovelId)
            ->setParameter('status', 'ativo')
            ->setParameter('hoje', $hoje)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findContratosVencimentoProximo(int $dias = 30): array
    {
        $hoje = new \DateTime();
        $dataLimite = new \DateTime("+{$dias} days");

        return $this->createQueryBuilder('c')
            ->leftJoin('c.imovel', 'i')
            ->leftJoin('c.pessoaLocatario', 'loc')
            ->addSelect('i', 'loc')
            ->where('c.dataFim BETWEEN :hoje AND :limite')
            ->andWhere('c.status = :status')
            ->andWhere('c.ativo = true')
            ->setParameter('hoje', $hoje)
            ->setParameter('limite', $dataLimite)
            ->setParameter('status', 'ativo')
            ->orderBy('c.dataFim', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findContratosParaReajuste(): array
    {
        $hoje = new \DateTime();

        return $this->createQueryBuilder('c')
            ->leftJoin('c.imovel', 'i')
            ->addSelect('i')
            ->where('c.dataProximoReajuste <= :hoje')
            ->andWhere('c.status = :status')
            ->andWhere('c.ativo = true')
            ->setParameter('hoje', $hoje)
            ->setParameter('status', 'ativo')
            ->orderBy('c.dataProximoReajuste', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getEstatisticas(): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = "
            SELECT
                COUNT(*) as total,
                COUNT(*) FILTER (WHERE status = 'ativo' AND ativo = true) as ativos,
                COUNT(*) FILTER (WHERE status = 'encerrado') as encerrados,
                COUNT(*) FILTER (WHERE status = 'rescindido') as rescindidos,
                COALESCE(SUM(valor_contrato) FILTER (WHERE status = 'ativo' AND ativo = true), 0) as valor_total_ativos
            FROM imoveis_contratos
        ";

        return $conn->fetchAssociative($sql) ?: [];
    }

    /**
     * Busca contratos configurados para envio automático de boletos
     *
     * Critérios:
     * - Status 'ativo'
     * - ativo = true
     * - gera_boleto = true
     * - envia_email = true
     * - Vigente (data_inicio <= hoje <= data_fim ou data_fim null)
     *
     * @return ImoveisContratos[]
     */
    public function findContratosParaEnvioAutomatico(): array
    {
        $hoje = new \DateTime();

        return $this->createQueryBuilder('c')
            ->innerJoin('c.imovel', 'i')
            ->innerJoin('c.pessoaLocatario', 'loc')
            ->addSelect('i', 'loc')
            ->andWhere('c.status = :status')
            ->andWhere('c.ativo = true')
            ->andWhere('c.geraBoleto = true')
            ->andWhere('c.enviaEmail = true')
            ->andWhere('c.dataInicio <= :hoje')
            ->andWhere('c.dataFim IS NULL OR c.dataFim >= :hoje')
            ->setParameter('status', 'ativo')
            ->setParameter('hoje', $hoje)
            ->orderBy('c.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Busca contratos para envio manual (pode gerar boleto, mas não necessariamente email automático)
     *
     * @return ImoveisContratos[]
     */
    public function findContratosParaEnvioManual(): array
    {
        $hoje = new \DateTime();

        return $this->createQueryBuilder('c')
            ->innerJoin('c.imovel', 'i')
            ->innerJoin('c.pessoaLocatario', 'loc')
            ->addSelect('i', 'loc')
            ->andWhere('c.status = :status')
            ->andWhere('c.ativo = true')
            ->andWhere('c.geraBoleto = true')
            ->andWhere('c.dataInicio <= :hoje')
            ->andWhere('c.dataFim IS NULL OR c.dataFim >= :hoje')
            ->setParameter('status', 'ativo')
            ->setParameter('hoje', $hoje)
            ->orderBy('c.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Busca contratos com vencimento em determinado dia do mês
     *
     * @return ImoveisContratos[]
     */
    public function findByDiaVencimento(int $diaVencimento): array
    {
        $hoje = new \DateTime();

        return $this->createQueryBuilder('c')
            ->innerJoin('c.imovel', 'i')
            ->innerJoin('c.pessoaLocatario', 'loc')
            ->addSelect('i', 'loc')
            ->andWhere('c.diaVencimento = :dia')
            ->andWhere('c.status = :status')
            ->andWhere('c.ativo = true')
            ->andWhere('c.dataInicio <= :hoje')
            ->andWhere('c.dataFim IS NULL OR c.dataFim >= :hoje')
            ->setParameter('dia', $diaVencimento)
            ->setParameter('status', 'ativo')
            ->setParameter('hoje', $hoje)
            ->orderBy('c.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Conta contratos ativos configurados para cobrança automática
     */
    public function contarContratosCobrancaAutomatica(): int
    {
        $hoje = new \DateTime();

        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->andWhere('c.status = :status')
            ->andWhere('c.ativo = true')
            ->andWhere('c.geraBoleto = true')
            ->andWhere('c.enviaEmail = true')
            ->andWhere('c.dataInicio <= :hoje')
            ->andWhere('c.dataFim IS NULL OR c.dataFim >= :hoje')
            ->setParameter('status', 'ativo')
            ->setParameter('hoje', $hoje)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
