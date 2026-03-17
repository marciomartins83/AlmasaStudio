<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\AlmasaVinculoBancario;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AlmasaVinculoBancario>
 */
class AlmasaVinculoBancarioRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AlmasaVinculoBancario::class);
    }

    public function findByPlanoContaId(int $planoContaId): array
    {
        $query = $this->getEntityManager()->createQuery(
            'SELECT v FROM App\Entity\AlmasaVinculoBancario v JOIN v.contaBancaria cb WHERE v.almasaPlanoConta = :planoId AND v.ativo = true AND cb.ativo = true ORDER BY v.padrao DESC, cb.descricao ASC'
        );
        $query->setParameter('planoId', $planoContaId);
        return $query->getResult();
    }
}
