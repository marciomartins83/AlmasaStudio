<?php

namespace App\Repository;

use App\Entity\TipoEndereco;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TipoEndereco>
 *
 * @method TipoEndereco|null find($id, $lockMode = null, $lockVersion = null)
 * @method TipoEndereco|null findOneBy(array $criteria, array $orderBy = null)
 * @method TipoEndereco[]    findAll()
 * @method TipoEndereco[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TipoEnderecoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TipoEndereco::class);
    }

    // If you have custom methods, add them here
    // public function save(TipoEndereco $entity, bool $flush = false): void
    // {
    //     $this->getEntityManager()->persist($entity);
    //
    //     if ($flush) {
    //         $this->getEntityManager()->flush();
    //     }
    // }
    //
    // public function remove(TipoEndereco $entity, bool $flush = false): void
    // {
    //     $this->getEntityManager()->remove($entity);
    //
    //     if ($flush) {
    //         $this->getEntityManager()->flush();
    //     }
    // }
}
