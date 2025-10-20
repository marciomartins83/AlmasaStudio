<?php

namespace App\Repository;

use App\Entity\Pessoas;
use App\Entity\PessoasDocumentos;
use App\Entity\TiposDocumentos;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Pessoas>
 */
class PessoaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Pessoas::class);
    }

    /**
     * Busca pessoa por CPF através da tabela de documentos
     */
    public function findByCpfDocumento(string $numeroCpf): ?Pessoas
    {
        return $this->createQueryBuilder('p')
            ->join('p.pessoasDocumentos', 'pd')
            ->join('pd.tipoDocumento', 'td')
            ->andWhere('td.tipo = :tipoCpf')
            ->andWhere('pd.numeroDocumento = :numero')
            ->andWhere('pd.ativo = true')
            ->setParameter('tipoCpf', 'CPF')
            ->setParameter('numero', $numeroCpf)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Busca pessoa por CNPJ através da tabela de documentos
     */
    public function findByCnpj(string $cnpj): ?Pessoas
    {
        return $this->createQueryBuilder('p')
            ->innerJoin('App\Entity\PessoasDocumentos', 'pd', 'WITH', 'pd.idPessoa = p.idpessoa')
            ->innerJoin('App\Entity\TiposDocumentos', 'td', 'WITH', 'td.id = pd.idTipoDocumento')
            ->andWhere('td.tipo = :tipoCnpj')
            ->andWhere('pd.numeroDocumento = :cnpj')
            ->andWhere('pd.ativo = true')
            ->setParameter('tipoCnpj', 'CNPJ')
            ->setParameter('cnpj', $cnpj)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Busca pessoas por nome (busca parcial) - CORRIGIDO
     */
    public function findByNome(string $nome): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.nome LIKE :nome') // CORRIGIDO: era :tipo
            ->setParameter('nome', '%' . $nome . '%')
            ->orderBy('p.nome', 'ASC') // CORRIGIDO: era p.tipo
            ->getQuery()
            ->getResult();
    }

    /**
     * Busca inteligente por CPF, CNPJ, nome ou ID
     */
    public function searchPessoa(string $searchTerm): array
    {
        $qb = $this->createQueryBuilder('p');

        // Se for numérico e tem 11 dígitos, busca por CPF
        if (ctype_digit($searchTerm) && strlen($searchTerm) === 11) {
            $qb->andWhere('p.cpf = :searchTerm')
                ->setParameter('searchTerm', $searchTerm);
        }
        // Se for numérico e tem 14 dígitos, busca por CNPJ
        elseif (ctype_digit($searchTerm) && strlen($searchTerm) === 14) {
            $qb->andWhere('p.cnpj = :searchTerm')
                ->setParameter('searchTerm', $searchTerm);
        }
        // Se for numérico e menor que 11 dígitos, busca por ID
        elseif (ctype_digit($searchTerm) && strlen($searchTerm) < 11) {
            $qb->andWhere('p.idpessoa = :searchTerm')
                ->setParameter('searchTerm', (int)$searchTerm);
        }
        // Caso contrário, busca por nome
        else {
            $qb->andWhere('p.nome LIKE :searchTerm')
                ->setParameter('searchTerm', '%' . $searchTerm . '%');
        }

        return $qb->orderBy('p.nome', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Verifica se já existe uma pessoa com o CPF/CNPJ informado através da tabela de documentos
     */
    public function existsByCpfOrCnpj(?string $cpf, ?string $cnpj): ?Pessoas
    {
        if ($cpf) {
            return $this->findByCpf($cpf);
        } elseif ($cnpj) {
            return $this->findByCnpj($cnpj);
        }

        return null;
    }

    /**
     * Busca o CPF de uma pessoa específica
     */
    public function getCpfByPessoa(int $pessoaId): ?string
    {
        $result = $this->getEntityManager()
            ->createQueryBuilder()
            ->select('pd.numeroDocumento')
            ->from(PessoasDocumentos::class, 'pd')
            ->join('pd.tipoDocumento', 'td')
            ->andWhere('pd.pessoa = :pessoaId')
            ->andWhere('td.tipo = :tipoCpf')
            ->andWhere('pd.ativo = true')
            ->setParameter('pessoaId', $pessoaId)
            ->setParameter('tipoCpf', 'CPF')
            ->getQuery()
            ->getOneOrNullResult();

        return $result['numeroDocumento'] ?? null;
    }

    /**
     * Busca o CNPJ de uma pessoa específica
     */
    public function getCnpjByPessoa(int $pessoaId): ?string
    {
        $result = $this->getEntityManager()
            ->createQueryBuilder()
            ->select('pd.numeroDocumento')
            ->from(PessoasDocumentos::class, 'pd')
            ->join('pd.tipoDocumento', 'td')
            ->andWhere('pd.pessoa = :pessoaId')
            ->andWhere('td.tipo = :tipoCnpj')
            ->andWhere('pd.ativo = true')
            ->setParameter('pessoaId', $pessoaId)
            ->setParameter('tipoCnpj', 'CNPJ')
            ->getQuery()
            ->getOneOrNullResult()['numeroDocumento'] ?? null;

            return $result['numeroDocumento'] ?? null;

    }

    /**
     * Busca documentos secundários de uma pessoa (exceto CPF/CNPJ)
     */
    public function buscarDocumentosSecundarios(int $pessoaId): array
    {
        $docs = $this->getEntityManager()
            ->createQueryBuilder()
            ->select('pd, td')
            ->from(PessoasDocumentos::class, 'pd')
            ->join('pd.tipoDocumento', 'td')
            ->andWhere('pd.pessoa = :pessoaId')
            ->andWhere('pd.ativo = true')
            ->setParameter('pessoaId', $pessoaId)
            ->getQuery()
            ->getResult();

        $ret = [];
        foreach ($docs as $d) {
            $ret[] = [
                'tipo'           => $d->getTipoDocumento()->getTipo(),
                'numero'         => $d->getNumeroDocumento(),
                'orgaoEmissor'   => $d->getOrgaoEmissor(),
                'dataEmissao'    => $d->getDataEmissao()?->format('Y-m-d'),
                'dataVencimento' => $d->getDataVencimento()?->format('Y-m-d'),
                'observacoes'    => $d->getObservacoes(),
            ];
        }
        return $ret;
    }

    /**
     * Busca profissões ativas de uma pessoa
     */
    public function buscarProfissoesAtivas(int $pessoaId): array
    {
        return $this->getEntityManager()
            ->createQuery('
                SELECT 
                    pp.idProfissao as profissao,
                    pp.empresa,
                    pp.renda,
                    pp.dataAdmissao,
                    pp.dataDemissao,
                    pp.observacoes
                FROM App\Entity\PessoasProfissoes pp
                WHERE pp.idPessoa = :pessoaId
                AND pp.ativo = true
            ')
            ->setParameter('pessoaId', $pessoaId)
            ->getResult();
    }
}
