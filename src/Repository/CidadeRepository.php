<?php
namespace App\Repository;

use App\Entity\Cidades;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Cidades>
 *
 * @method Cidades|null find($id, $lockMode = null, $lockVersion = null)
 * @method Cidades|null findOneBy(array $criteria, array $orderBy = null)
 * @method Cidades[]    findAll()
 * @method Cidades[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CidadeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Cidades::class);
    }

    public function save(Cidades $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Cidades $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByEstado(int $estadoId): array
    {
        return $this->findBy(['idEstado' => $estadoId], ['nome' => 'ASC']);
    }

    public function findByNome(string $nome): ?Cidades
    {
        return $this->findOneBy(['nome' => $nome]);
    }

    public function findByCodigo(string $codigo): ?Cidades
    {
        return $this->findOneBy(['codigo' => $codigo]);
    }
}