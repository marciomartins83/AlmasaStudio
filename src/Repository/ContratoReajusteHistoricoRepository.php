<?php

namespace App\Repository;

use App\Entity\ContratoReajusteHistorico;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ContratoReajusteHistorico>
 *
 * @method ContratoReajusteHistorico|null find($id, $lockMode = null, $lockVersion = null)
 * @method ContratoReajusteHistorico|null findOneBy(array $criteria, array $orderBy = null)
 * @method ContratoReajusteHistorico[]    findAll()
 * @method ContratoReajusteHistorico[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ContratoReajusteHistoricoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ContratoReajusteHistorico::class);
    }

    public function findByContrato(int $contratoId): array
    {
        return $this->createQueryBuilder('h')
            ->where('h.contrato = :contratoId')
            ->setParameter('contratoId', $contratoId)
            ->orderBy('h.dataReajuste', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
