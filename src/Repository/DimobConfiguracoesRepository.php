<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\DimobConfiguracoes;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DimobConfiguracoes>
 */
class DimobConfiguracoesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DimobConfiguracoes::class);
    }

    /**
     * Busca configuração por ano
     */
    public function findByAno(int $ano): ?DimobConfiguracoes
    {
        return $this->findOneBy(['ano' => $ano]);
    }

    /**
     * Lista anos com configurações
     *
     * @return int[]
     */
    public function findAnosComConfiguracao(): array
    {
        $result = $this->createQueryBuilder('d')
            ->select('d.ano')
            ->orderBy('d.ano', 'DESC')
            ->getQuery()
            ->getResult();

        return array_column($result, 'ano');
    }

    /**
     * Busca última configuração cadastrada
     */
    public function findUltima(): ?DimobConfiguracoes
    {
        return $this->createQueryBuilder('d')
            ->orderBy('d.ano', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Cria ou atualiza configuração de um ano
     */
    public function upsert(int $ano, array $dados): DimobConfiguracoes
    {
        $config = $this->findByAno($ano);

        if ($config === null) {
            $config = new DimobConfiguracoes();
            $config->setAno($ano);
        }

        if (isset($dados['cnpjDeclarante'])) {
            $config->setCnpjDeclarante($dados['cnpjDeclarante']);
        }

        if (isset($dados['cpfResponsavel'])) {
            $config->setCpfResponsavel($dados['cpfResponsavel']);
        }

        if (isset($dados['codigoCidade'])) {
            $config->setCodigoCidade($dados['codigoCidade']);
        }

        if (isset($dados['declaracaoRetificadora'])) {
            $config->setDeclaracaoRetificadora($dados['declaracaoRetificadora']);
        }

        if (isset($dados['situacaoEspecial'])) {
            $config->setSituacaoEspecial($dados['situacaoEspecial']);
        }

        $config->setUpdatedAt(new \DateTime());

        $this->getEntityManager()->persist($config);
        $this->getEntityManager()->flush();

        return $config;
    }
}
