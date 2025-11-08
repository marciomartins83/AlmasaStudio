<?php
namespace App\Repository;

use App\Entity\Pessoas;
use App\Entity\PessoasDocumentos;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PessoasDocumentosRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PessoasDocumentos::class);
    }

    /**
     * Retorna TODOS os documentos não-principais (≠ CPF/CNPJ) da pessoa.
     */
    public function findSecundariosByPessoa(Pessoas $pessoa): array
    {
        return $this->createQueryBuilder('pd')
            ->innerJoin('pd.tipoDocumento', 'td')
            ->andWhere('pd.pessoa = :pessoa')
            ->andWhere('td.tipo NOT IN (:principais)')
            ->setParameter('pessoa', $pessoa)
            ->setParameter('principais', ['CPF', 'CNPJ'])
            ->getQuery()
            ->getResult();
    }
}
