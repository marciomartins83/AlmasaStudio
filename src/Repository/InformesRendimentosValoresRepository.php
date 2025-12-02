<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\InformesRendimentosValores;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<InformesRendimentosValores>
 */
class InformesRendimentosValoresRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, InformesRendimentosValores::class);
    }

    /**
     * Busca valores por informe
     *
     * @return InformesRendimentosValores[]
     */
    public function findByInforme(int $informeId): array
    {
        return $this->createQueryBuilder('v')
            ->where('v.informe = :informe')
            ->setParameter('informe', $informeId)
            ->orderBy('v.mes', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Busca valor específico de um mês
     */
    public function findByInformeEMes(int $informeId, int $mes): ?InformesRendimentosValores
    {
        return $this->createQueryBuilder('v')
            ->where('v.informe = :informe')
            ->andWhere('v.mes = :mes')
            ->setParameter('informe', $informeId)
            ->setParameter('mes', $mes)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Soma total por informe
     */
    public function somarPorInforme(int $informeId): float
    {
        $result = $this->createQueryBuilder('v')
            ->select('SUM(CAST(v.valor AS float)) as total')
            ->where('v.informe = :informe')
            ->setParameter('informe', $informeId)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0);
    }

    /**
     * Retorna valores como array [mes => valor]
     *
     * @return array<int, float>
     */
    public function getValoresArrayPorInforme(int $informeId): array
    {
        $valores = $this->findByInforme($informeId);
        $resultado = [];

        for ($mes = 1; $mes <= 12; $mes++) {
            $resultado[$mes] = 0.0;
        }

        foreach ($valores as $valor) {
            $resultado[$valor->getMes()] = (float) $valor->getValor();
        }

        return $resultado;
    }

    /**
     * Remove todos os valores de um informe
     */
    public function removeByInforme(int $informeId): int
    {
        return $this->createQueryBuilder('v')
            ->delete()
            ->where('v.informe = :informe')
            ->setParameter('informe', $informeId)
            ->getQuery()
            ->execute();
    }
}
