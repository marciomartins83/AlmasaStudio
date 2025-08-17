<?php
namespace App\Repository;

use App\Entity\TiposDocumentos;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TiposDocumentos>
 *
 * @method TiposDocumentos|null find($id, $lockMode = null, $lockVersion = null)
 * @method TiposDocumentos|null findOneBy(array $criteria, array $orderBy = null)
 * @method TiposDocumentos[]    findAll()
 * @method TiposDocumentos[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TipoDocumentoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TiposDocumentos::class);
    }

    public function save(TiposDocumentos $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(TiposDocumentos $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByTipo(string $tipo): ?TiposDocumentos
    {
        return $this->findOneBy(['tipo' => $tipo]);
    }
}