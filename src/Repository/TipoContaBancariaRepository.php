<?php
namespace App\Repository;

use App\Entity\TiposContasBancarias;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TiposContasBancarias>
 *
 * @method TiposContasBancarias|null find($id, $lockMode = null, $lockVersion = null)
 * @method TiposContasBancarias|null findOneBy(array $criteria, array $orderBy = null)
 * @method TiposContasBancarias[]    findAll()
 * @method TiposContasBancarias[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TipoContaBancariaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TiposContasBancarias::class);
    }

    public function save(TiposContasBancarias $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(TiposContasBancarias $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByTipo(string $tipo): ?TiposContasBancarias
    {
        return $this->findOneBy(['tipo' => $tipo]);
    }
}