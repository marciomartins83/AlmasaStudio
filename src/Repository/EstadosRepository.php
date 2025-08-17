<?php
namespace App\Repository;

use App\Entity\Estados;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Estados>
 *
 * @method Estados|null find($id, $lockMode = null, $lockVersion = null)
 * @method Estados|null findOneBy(array $criteria, array $orderBy = null)
 * @method Estados[]    findAll()
 * @method Estados[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EstadosRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Estados::class);
    }

    public function save(Estados $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Estados $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByUf(string $uf): ?Estados
    {
        return $this->findOneBy(['uf' => strtoupper($uf)]);
    }
} 