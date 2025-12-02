<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\PlanoContas;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PlanoContas>
 */
class PlanoContasRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PlanoContas::class);
    }

    /**
     * Busca planos de conta ativos ordenados por código
     *
     * @return PlanoContas[]
     */
    public function findAtivos(): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.ativo = :ativo')
            ->setParameter('ativo', true)
            ->orderBy('p.codigo', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Busca planos de conta por tipo
     *
     * @return PlanoContas[]
     */
    public function findByTipo(int $tipo): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.tipo = :tipo')
            ->andWhere('p.ativo = :ativo')
            ->setParameter('tipo', $tipo)
            ->setParameter('ativo', true)
            ->orderBy('p.codigo', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Busca planos de conta que entram no informe de rendimentos
     *
     * @return PlanoContas[]
     */
    public function findQueEntramNoInforme(): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.entraInforme = :entraInforme')
            ->andWhere('p.ativo = :ativo')
            ->setParameter('entraInforme', true)
            ->setParameter('ativo', true)
            ->orderBy('p.codigo', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Busca planos de conta que incidem taxa de administração
     *
     * @return PlanoContas[]
     */
    public function findQueIncideTaxaAdmin(): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.incideTaxaAdmin = :incide')
            ->andWhere('p.ativo = :ativo')
            ->setParameter('incide', true)
            ->setParameter('ativo', true)
            ->orderBy('p.codigo', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Busca plano por código
     */
    public function findByCodigo(string $codigo): ?PlanoContas
    {
        return $this->createQueryBuilder('p')
            ->where('p.codigo = :codigo')
            ->setParameter('codigo', $codigo)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Busca receitas (tipo = 0)
     *
     * @return PlanoContas[]
     */
    public function findReceitas(): array
    {
        return $this->findByTipo(0);
    }

    /**
     * Busca despesas (tipo = 1)
     *
     * @return PlanoContas[]
     */
    public function findDespesas(): array
    {
        return $this->findByTipo(1);
    }

    /**
     * Busca para select de formulário
     *
     * @return array<int, string>
     */
    public function findParaSelect(): array
    {
        $planos = $this->findAtivos();
        $resultado = [];

        foreach ($planos as $plano) {
            $resultado[$plano->getId()] = $plano->getCodigo() . ' - ' . $plano->getDescricao();
        }

        return $resultado;
    }
}
