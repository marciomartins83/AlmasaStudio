<?php
namespace App\Repository;

use App\Entity\Bancos;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Bancos>
 *
 * @method Bancos|null find($id, $lockMode = null, $lockVersion = null)
 * @method Bancos|null findOneBy(array $criteria, array $orderBy = null)
 * @method Bancos[]    findAll()
 * @method Bancos[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BancosRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Bancos::class);
    }

    public function save(Bancos $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Bancos $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
