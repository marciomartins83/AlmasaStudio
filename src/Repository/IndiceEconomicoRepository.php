<?php

namespace App\Repository;

use App\Entity\IndiceEconomico;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<IndiceEconomico>
 *
 * @method IndiceEconomico|null find($id, $lockMode = null, $lockVersion = null)
 * @method IndiceEconomico|null findOneBy(array $criteria, array $orderBy = null)
 * @method IndiceEconomico[]    findAll()
 * @method IndiceEconomico[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class IndiceEconomicoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, IndiceEconomico::class);
    }

    public function findVigente(string $tipo, string $competencia): ?IndiceEconomico
    {
        return $this->createQueryBuilder('i')
            ->where('i.tipo = :tipo')
            ->andWhere('i.competencia = :competencia')
            ->setParameter('tipo', $tipo)
            ->setParameter('competencia', $competencia)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
