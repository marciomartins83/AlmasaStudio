<?php
namespace App\Repository;

use App\Entity\Bairros;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Bairros>
 *
 * @method Bairros|null find($id, $lockMode = null, $lockVersion = null)
 * @method Bairros|null findOneBy(array $criteria, array $orderBy = null)
 * @method Bairros[]    findAll()
 * @method Bairros[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BairroRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Bairros::class);
    }

    public function save(Bairros $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Bairros $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByCidade(int $cidadeId): array
    {
        return $this->findBy(['idCidade' => $cidadeId], ['nome' => 'ASC']);
    }

    public function findByNome(string $nome): ?Bairros
    {
        return $this->findOneBy(['nome' => $nome]);
    }

    public function findByCodigo(string $codigo): ?Bairros
    {
        return $this->findOneBy(['codigo' => $codigo]);
    }
}