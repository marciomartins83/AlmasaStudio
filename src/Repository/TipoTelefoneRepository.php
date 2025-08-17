<?php
namespace App\Repository;

use App\Entity\TiposTelefones;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TiposTelefones>
 *
 * @method TiposTelefones|null find($id, $lockMode = null, $lockVersion = null)
 * @method TiposTelefones|null findOneBy(array $criteria, array $orderBy = null)
 * @method TiposTelefones[]    findAll()
 * @method TiposTelefones[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TipoTelefoneRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TiposTelefones::class);
    }

    public function save(TiposTelefones $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(TiposTelefones $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByTipo(string $tipo): ?TiposTelefones
    {
        return $this->findOneBy(['tipo' => $tipo]);
    }
}