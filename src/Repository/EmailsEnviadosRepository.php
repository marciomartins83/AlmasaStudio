<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\EmailsEnviados;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EmailsEnviados>
 */
class EmailsEnviadosRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EmailsEnviados::class);
    }

    public function save(EmailsEnviados $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(EmailsEnviados $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Busca emails por referência
     *
     * @return EmailsEnviados[]
     */
    public function findByReferencia(string $tipoReferencia, int $referenciaId): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.tipoReferencia = :tipo')
            ->andWhere('e.referenciaId = :id')
            ->setParameter('tipo', $tipoReferencia)
            ->setParameter('id', $referenciaId)
            ->orderBy('e.enviadoEm', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Busca emails por destinatário
     *
     * @return EmailsEnviados[]
     */
    public function findByDestinatario(string $destinatario, int $limit = 50): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.destinatario = :destinatario')
            ->setParameter('destinatario', $destinatario)
            ->orderBy('e.enviadoEm', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Busca emails com falha
     *
     * @return EmailsEnviados[]
     */
    public function findComFalha(int $limit = 100): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.status IN (:status)')
            ->setParameter('status', [
                EmailsEnviados::STATUS_FALHA,
                EmailsEnviados::STATUS_BOUNCE
            ])
            ->orderBy('e.enviadoEm', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Busca emails por período
     *
     * @return EmailsEnviados[]
     */
    public function findByPeriodo(\DateTime $inicio, \DateTime $fim): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.enviadoEm BETWEEN :inicio AND :fim')
            ->setParameter('inicio', $inicio)
            ->setParameter('fim', $fim)
            ->orderBy('e.enviadoEm', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retorna estatísticas de emails
     */
    public function getEstatisticas(?\DateTime $inicio = null, ?\DateTime $fim = null): array
    {
        $qb = $this->createQueryBuilder('e')
            ->select([
                'e.status',
                'e.tipoReferencia',
                'COUNT(e.id) as quantidade'
            ])
            ->groupBy('e.status, e.tipoReferencia');

        if ($inicio && $fim) {
            $qb->andWhere('e.enviadoEm BETWEEN :inicio AND :fim')
                ->setParameter('inicio', $inicio)
                ->setParameter('fim', $fim);
        }

        $resultados = $qb->getQuery()->getResult();

        $estatisticas = [
            'total' => 0,
            'enviados' => 0,
            'falhas' => 0,
            'por_tipo' => []
        ];

        foreach ($resultados as $row) {
            $qtd = (int) $row['quantidade'];
            $estatisticas['total'] += $qtd;

            if ($row['status'] === EmailsEnviados::STATUS_ENVIADO) {
                $estatisticas['enviados'] += $qtd;
            } else {
                $estatisticas['falhas'] += $qtd;
            }

            $tipo = $row['tipoReferencia'];
            if (!isset($estatisticas['por_tipo'][$tipo])) {
                $estatisticas['por_tipo'][$tipo] = 0;
            }
            $estatisticas['por_tipo'][$tipo] += $qtd;
        }

        return $estatisticas;
    }

    /**
     * Limpa emails antigos (para limpeza periódica)
     */
    public function limparAntigos(int $diasRetencao = 365): int
    {
        $dataLimite = new \DateTime();
        $dataLimite->modify("-{$diasRetencao} days");

        return $this->createQueryBuilder('e')
            ->delete()
            ->andWhere('e.enviadoEm < :dataLimite')
            ->setParameter('dataLimite', $dataLimite)
            ->getQuery()
            ->execute();
    }
}
