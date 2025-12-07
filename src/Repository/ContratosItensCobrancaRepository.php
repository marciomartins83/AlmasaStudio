<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ContratosItensCobranca;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ContratosItensCobranca>
 */
class ContratosItensCobrancaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ContratosItensCobranca::class);
    }

    public function save(ContratosItensCobranca $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ContratosItensCobranca $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Busca itens ativos por contrato
     *
     * @return ContratosItensCobranca[]
     */
    public function findAtivosByContrato(int $contratoId): array
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.contrato = :contratoId')
            ->andWhere('i.ativo = true')
            ->setParameter('contratoId', $contratoId)
            ->orderBy('i.tipoItem', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Busca item por contrato e tipo
     */
    public function findByContratoTipo(int $contratoId, string $tipoItem): ?ContratosItensCobranca
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.contrato = :contratoId')
            ->andWhere('i.tipoItem = :tipoItem')
            ->setParameter('contratoId', $contratoId)
            ->setParameter('tipoItem', $tipoItem)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Calcula valor total dos itens ativos do contrato
     */
    public function calcularTotalContrato(int $contratoId, float $valorBase): float
    {
        $itens = $this->findAtivosByContrato($contratoId);
        $total = 0;

        foreach ($itens as $item) {
            $total += $item->calcularValorEfetivo($valorBase);
        }

        return $total;
    }
}
