<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\PrestacoesContasItens;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PrestacoesContasItens>
 */
class PrestacoesContasItensRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PrestacoesContasItens::class);
    }

    /**
     * Busca itens por prestação e tipo
     *
     * @return PrestacoesContasItens[]
     */
    public function findByPrestacaoETipo(int $idPrestacao, ?string $tipo = null): array
    {
        $qb = $this->createQueryBuilder('i')
            ->leftJoin('i.planoConta', 'pc')
            ->leftJoin('i.imovel', 'im')
            ->addSelect('pc', 'im')
            ->where('i.prestacaoConta = :prestacao')
            ->setParameter('prestacao', $idPrestacao)
            ->orderBy('i.dataMovimento', 'ASC');

        if ($tipo) {
            $qb->andWhere('i.tipo = :tipo')
               ->setParameter('tipo', $tipo);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Retorna totais por tipo de uma prestação
     */
    public function getTotaisPorTipo(int $idPrestacao): array
    {
        $result = $this->createQueryBuilder('i')
            ->select([
                'i.tipo',
                'SUM(CAST(i.valorBruto AS float)) as total_bruto',
                'SUM(CAST(i.valorTaxaAdmin AS float)) as total_taxa',
                'SUM(CAST(i.valorRetencaoIr AS float)) as total_ir',
                'SUM(CAST(i.valorLiquido AS float)) as total_liquido',
                'COUNT(i.id) as quantidade',
            ])
            ->where('i.prestacaoConta = :prestacao')
            ->setParameter('prestacao', $idPrestacao)
            ->groupBy('i.tipo')
            ->getQuery()
            ->getResult();

        $totais = [
            'receita' => [
                'total_bruto' => 0,
                'total_taxa' => 0,
                'total_ir' => 0,
                'total_liquido' => 0,
                'quantidade' => 0,
            ],
            'despesa' => [
                'total_bruto' => 0,
                'total_taxa' => 0,
                'total_ir' => 0,
                'total_liquido' => 0,
                'quantidade' => 0,
            ],
        ];

        foreach ($result as $row) {
            $tipo = $row['tipo'];
            $totais[$tipo] = [
                'total_bruto' => (float) $row['total_bruto'],
                'total_taxa' => (float) $row['total_taxa'],
                'total_ir' => (float) $row['total_ir'],
                'total_liquido' => (float) $row['total_liquido'],
                'quantidade' => (int) $row['quantidade'],
            ];
        }

        return $totais;
    }

    /**
     * Agrupa itens por imóvel
     */
    public function agruparPorImovel(int $idPrestacao): array
    {
        return $this->createQueryBuilder('i')
            ->select([
                'IDENTITY(i.imovel) as id_imovel',
                'i.tipo',
                'SUM(CAST(i.valorBruto AS float)) as total_bruto',
                'SUM(CAST(i.valorLiquido AS float)) as total_liquido',
                'COUNT(i.id) as quantidade',
            ])
            ->where('i.prestacaoConta = :prestacao')
            ->setParameter('prestacao', $idPrestacao)
            ->groupBy('i.imovel', 'i.tipo')
            ->getQuery()
            ->getResult();
    }

    /**
     * Remove itens de uma prestação
     */
    public function removerItensDaPrestacao(int $idPrestacao): int
    {
        return $this->createQueryBuilder('i')
            ->delete()
            ->where('i.prestacaoConta = :prestacao')
            ->setParameter('prestacao', $idPrestacao)
            ->getQuery()
            ->execute();
    }
}
