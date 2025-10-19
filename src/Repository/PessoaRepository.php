<?php

namespace App\Repository;

use App\Entity\Pessoas;
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
            ->join('pd.idDocumento', 'd')
            ->andWhere('d.nome = :tipoCpf')
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
            ->createQuery('
                SELECT pd.numeroDocumento 
                FROM App\Entity\PessoasDocumentos pd
                INNER JOIN App\Entity\TiposDocumentos td WITH td.id = pd.idTipoDocumento
                WHERE pd.idPessoa = :pessoaId 
                AND td.tipo = :tipoCpf 
                AND pd.ativo = true
            ')
            ->setParameter('pessoaId', $pessoaId)
            ->setParameter('tipoCpf', 'CPF')
            ->getOneOrNullResult();

        return $result ? $result['numeroDocumento'] : null;
    }

    /**
     * Busca o CNPJ de uma pessoa específica
     */
    public function getCnpjByPessoa(int $pessoaId): ?string
    {
        $result = $this->getEntityManager()
            ->createQuery('
                SELECT pd.numeroDocumento 
                FROM App\Entity\PessoasDocumentos pd
                INNER JOIN App\Entity\TiposDocumentos td WITH td.id = pd.idTipoDocumento
                WHERE pd.idPessoa = :pessoaId 
                AND td.tipo = :tipoCnpj 
                AND pd.ativo = true
            ')
            ->setParameter('pessoaId', $pessoaId)
            ->setParameter('tipoCnpj', 'CNPJ')
            ->getOneOrNullResult();

        return $result ? $result['numeroDocumento'] : null;
    }

    /**
     * Busca documentos secundários de uma pessoa (exceto CPF/CNPJ)
     */
    public function buscarDocumentosSecundarios(int $pessoaId): array
    {
        $pessoasDocumentos = $this->getEntityManager()
            ->createQuery('
                SELECT pd
                FROM App\Entity\PessoasDocumentos pd
                INNER JOIN App\Entity\TiposDocumentos td WITH td.id = pd.idTipoDocumento
                WHERE pd.idPessoa = :pessoaId
                AND pd.ativo = true
            ')
            ->setParameter('pessoaId', $pessoaId)
            ->getResult();

        $documentos = [];

        foreach ($pessoasDocumentos as $doc) {
            $documentos[] = [
                'tipo'           => $doc->getIdTipoDocumento(),
                'numero'         => $doc->getNumeroDocumento(),
                'orgaoEmissor'   => $doc->getOrgaoEmissor(),
                'dataEmissao'    => $doc->getDataEmissao()?->format('Y-m-d'),
                'dataVencimento' => $doc->getDataVencimento()?->format('Y-m-d'),
                'observacoes'    => $doc->getObservacoes(),
            ];
        }

        return $documentos;
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
