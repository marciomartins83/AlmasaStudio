<?php
namespace App\Repository;

use App\Entity\Logradouro;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Logradouro>
 *
 * @method Logradouro|null find($id, $lockMode = null, $lockVersion = null)
 * @method Logradouro|null findOneBy(array $criteria, array $orderBy = null)
 * @method Logradouro[]    findAll()
 * @method Logradouro[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LogradouroRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Logradouro::class);
    }

    public function save(Logradouro $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Logradouro $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByBairro(int $bairroId): array
    {
        return $this->findBy(['bairro' => $bairroId], ['nome' => 'ASC']);
    }

    public function findByLogradouro(string $logradouro): ?Logradouro
    {
        return $this->findOneBy(['nome' => $logradouro]);
    }

    public function findByCep(string $cep): array
    {
        return $this->findBy(['cep' => $cep]);
    }
}
