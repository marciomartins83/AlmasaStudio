<?php

namespace App\Repository;

use App\Entity\Pessoas;
use App\Entity\PessoasContratantes;
use App\Entity\PessoasFiadores;
use App\Entity\PessoasLocadores;
use App\Entity\PessoasCorretores;
use App\Entity\PessoasCorretoras;
use App\Entity\PessoasPretendentes;
use App\Entity\PessoasDocumentos;
use App\Entity\TiposDocumentos;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\PessoasTipos;

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

    public function findTiposByPessoaId(int $pessoaId): array
    {
        $ids = $this->getEntityManager()
            ->createQueryBuilder()
            ->select('pt.idTipoPessoa')
            ->from(PessoasTipos::class, 'pt')
            ->where('pt.idPessoa = :id')
            ->andWhere('pt.ativo = true')
            ->setParameter('id', $pessoaId)
            ->getQuery()
            ->getScalarResult(); // devolve [[idTipoPessoa => 6], ...]

        $ids = array_column($ids, 'idTipoPessoa'); // [6, 2, ...]

        return [
            'contratante' => in_array(6, $ids),
            'fiador'      => in_array(1, $ids),
            'locador'     => in_array(4, $ids),
            'corretor'    => in_array(2, $ids),
            'corretora'   => in_array(3, $ids),
            'pretendente' => in_array(5, $ids),
        ];
    }


    public function findTiposComDados(int $pessoaId): array
    {
        $em = $this->getEntityManager();

        // 1) Tipos ativos (boolean)
        $tipos = $this->findTiposByPessoaId($pessoaId);

        // 2) Objetos completos (null se não existe)
        $tipos['contratanteObj'] = $em->createQueryBuilder()
            ->select('c')->from(PessoasContratantes::class, 'c')
            ->where('c.pessoa = :id')->setParameter('id', $pessoaId)
            ->getQuery()->getOneOrNullResult();

        $tipos['fiadorObj'] = $em->createQueryBuilder()
            ->select('f')->from(PessoasFiadores::class, 'f')
            ->where('f.idPessoa = :id')->setParameter('id', $pessoaId)
            ->getQuery()->getOneOrNullResult();

        $tipos['locadorObj'] = $em->createQueryBuilder()
            ->select('l')->from(PessoasLocadores::class, 'l')
            ->where('l.pessoa = :id')->setParameter('id', $pessoaId)
            ->getQuery()->getOneOrNullResult();

        $tipos['corretorObj'] = $em->createQueryBuilder()
            ->select('r')->from(PessoasCorretores::class, 'r')
            ->where('r.pessoa = :id')->setParameter('id', $pessoaId)
            ->getQuery()->getOneOrNullResult();

        $tipos['corretoraObj'] = $em->createQueryBuilder()
            ->select('rr')->from(PessoasCorretoras::class, 'rr')
            ->where('rr.pessoa = :id')->setParameter('id', $pessoaId)
            ->getQuery()->getOneOrNullResult();

        $tipos['pretendenteObj'] = $em->createQueryBuilder()
            ->select('p')->from(PessoasPretendentes::class, 'p')
            ->where('p.pessoa = :id')->setParameter('id', $pessoaId)
            ->getQuery()->getOneOrNullResult();

        return $tipos;
    }

    /** Array vazio para quando a pessoa não existe */
    private function emptyTiposArray(): array
    {
        return [
            'contratante' => null, 'fiador' => null, 'locador' => null,
            'corretor'    => null, 'corretora' => null, 'pretendente' => null,
        ];
    }

    /**
     * Converte qualquer entidade em array associativo simples.
     * Ignora propriedades que não tenham getter.
     */
    private function entityToArray(?object $entity): ?array
    {
        if (!$entity) {
            return null;
        }

        $refl  = new \ReflectionObject($entity);
        $props = [];

        foreach ($refl->getProperties() as $prop) {
            $getter = 'get' . ucfirst($prop->getName());
            if (!method_exists($entity, $getter)) {
                continue;
            }

            $value = $entity->$getter();

            $props[$prop->getName()] = match (true) {
                $value instanceof \DateTimeInterface => $value->format('Y-m-d H:i:s'),
                is_object($value) && method_exists($value, 'getId') => $value->getId(),
                default => $value,
            };
        }

        return $props;
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
