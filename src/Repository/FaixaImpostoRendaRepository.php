<?php

namespace App\Repository;

use App\Entity\FaixaImpostoRenda;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FaixaImpostoRenda>
 *
 * @method FaixaImpostoRenda|null find($id, $lockMode = null, $lockVersion = null)
 * @method FaixaImpostoRenda|null findOneBy(array $criteria, array $orderBy = null)
 * @method FaixaImpostoRenda[]    findAll()
 * @method FaixaImpostoRenda[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FaixaImpostoRendaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FaixaImpostoRenda::class);
    }

    /**
     * Data de vigência da tabela ativa mais recente até a data de referência
     */
    public function findDataVigenteMaisRecente(\DateTimeInterface $referencia): ?\DateTimeInterface
    {
        $result = $this->createQueryBuilder('f')
            ->select('f.dataVigencia')
            ->where('f.dataVigencia <= :referencia')
            ->andWhere('f.ativo = true')
            ->setParameter('referencia', $referencia)
            ->orderBy('f.dataVigencia', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $result['dataVigencia'] ?? null;
    }

    /**
     * Todas as faixas de uma data de vigência específica, ordenadas por valor inicial
     */
    public function findByDataVigencia(\DateTimeInterface $dataVigencia): array
    {
        return $this->createQueryBuilder('f')
            ->where('f.dataVigencia = :dataVigencia')
            ->andWhere('f.ativo = true')
            ->setParameter('dataVigencia', $dataVigencia)
            ->orderBy('f.valorInicial', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
