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
}
