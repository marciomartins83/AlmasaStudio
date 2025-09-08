<?php

namespace App\Repository;

use App\Entity\Logradouros;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Logradouros>
 *
 * @method Logradouros|null find($id, $lockMode = null, $lockVersion = null)
 * @method Logradouros|null findOneBy(array $criteria, array $orderBy = null)
 * @method Logradouros[]    findAll()
 * @method Logradouros[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LogradourosRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Logradouros::class);
    }

    public function save(Logradouros $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Logradouros $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByBairro(int $bairroId): array
    {
        return $this->findBy(['bairro' => $bairroId], ['logradouro' => 'ASC']);
    }

    public function findByLogradouro(string $nomeLogradouro): ?Logradouros
    {
        return $this->findOneBy(['logradouro' => $nomeLogradouro]);
    }

    public function findOneByCep(string $cep): ?Logradouros
    {
        return $this->findOneBy(['cep' => $cep]);
    }
}

