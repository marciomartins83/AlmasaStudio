<?php
namespace App\Repository;

use App\Entity\TiposChavesPix;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TiposChavesPix>
 *
 * @method TiposChavesPix|null find($id, $lockMode = null, $lockVersion = null)
 * @method TiposChavesPix|null findOneBy(array $criteria, array $orderBy = null)
 * @method TiposChavesPix[]    findAll()
 * @method TiposChavesPix[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TipoChavePixRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TiposChavesPix::class);
    }

    public function save(TiposChavesPix $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(TiposChavesPix $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByTipo(string $tipo): ?TiposChavesPix
    {
        return $this->findOneBy(['tipo' => $tipo]);
    }
}