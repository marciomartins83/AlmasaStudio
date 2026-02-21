<?php

namespace App\Repository;

use App\Entity\Pessoas;
use App\Entity\PessoasContratantes;
use App\Entity\PessoasFiadores;
use App\Entity\PessoasLocadores;
use App\Entity\PessoasCorretores;
use App\Entity\PessoasCorretoras;
use App\Entity\PessoasPretendentes;
use App\Entity\PessoasSocios;
use App\Entity\PessoasAdvogados;
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
            'socio'       => in_array(7, $ids),
            'advogado'    => in_array(8, $ids),
        ];
    }


    public function findTiposComDados(int $pessoaId): array
    {
        $em = $this->getEntityManager();

        // 1) Buscar tipos da tabela pessoas_tipos (fonte de verdade para TODOS os tipos)
        $pessoasTipos = $em->getRepository(PessoasTipos::class)
            ->findBy(['idPessoa' => $pessoaId, 'ativo' => true]);

        // Mapa id_tipo_pessoa -> nome do tipo
        $tipoIdParaNome = [
            1  => 'fiador',
            2  => 'corretor',
            3  => 'corretora',
            4  => 'locador',
            5  => 'pretendente',
            6  => 'contratante',
            7  => 'socio',
            8  => 'advogado',
            12 => 'inquilino',
        ];

        // Inicializar todos como false
        $tipos = [];
        foreach ($tipoIdParaNome as $nome) {
            $tipos[$nome] = false;
        }

        // Marcar os ativos vindos de pessoas_tipos
        foreach ($pessoasTipos as $pt) {
            $nome = $tipoIdParaNome[$pt->getIdTipoPessoa()] ?? null;
            if ($nome) {
                $tipos[$nome] = true;
            }
        }

        // 2) Buscar objetos de dados específicos (tabelas dedicadas)
        // IMPORTANTE: Usar setMaxResults(1) para evitar erro de NonUniqueResult
        $contratanteObj = $em->createQueryBuilder()
            ->select('c')->from(PessoasContratantes::class, 'c')
            ->where('c.pessoa = :id')->setParameter('id', $pessoaId)
            ->orderBy('c.id', 'DESC')->setMaxResults(1)
            ->getQuery()->getOneOrNullResult();

        $fiadorObj = $em->createQueryBuilder()
            ->select('f')->from(PessoasFiadores::class, 'f')
            ->where('f.idPessoa = :id')->setParameter('id', $pessoaId)
            ->orderBy('f.id', 'DESC')->setMaxResults(1)
            ->getQuery()->getOneOrNullResult();

        $locadorObj = $em->createQueryBuilder()
            ->select('l')->from(PessoasLocadores::class, 'l')
            ->where('l.pessoa = :id')->setParameter('id', $pessoaId)
            ->orderBy('l.id', 'DESC')->setMaxResults(1)
            ->getQuery()->getOneOrNullResult();

        $corretorObj = $em->createQueryBuilder()
            ->select('r')->from(PessoasCorretores::class, 'r')
            ->where('r.pessoa = :id')->setParameter('id', $pessoaId)
            ->orderBy('r.id', 'DESC')->setMaxResults(1)
            ->getQuery()->getOneOrNullResult();

        $corretoraObj = $em->createQueryBuilder()
            ->select('rr')->from(PessoasCorretoras::class, 'rr')
            ->where('rr.pessoa = :id')->setParameter('id', $pessoaId)
            ->orderBy('rr.id', 'DESC')->setMaxResults(1)
            ->getQuery()->getOneOrNullResult();

        $pretendenteObj = $em->createQueryBuilder()
            ->select('p')->from(PessoasPretendentes::class, 'p')
            ->where('p.pessoa = :id')->setParameter('id', $pessoaId)
            ->orderBy('p.id', 'DESC')->setMaxResults(1)
            ->getQuery()->getOneOrNullResult();

        $socioObj = $em->createQueryBuilder()
            ->select('s')->from(PessoasSocios::class, 's')
            ->where('s.idPessoa = :id')->setParameter('id', $pessoaId)
            ->orderBy('s.id', 'DESC')->setMaxResults(1)
            ->getQuery()->getOneOrNullResult();

        $advogadoObj = $em->createQueryBuilder()
            ->select('a')->from(PessoasAdvogados::class, 'a')
            ->where('a.idPessoa = :id')->setParameter('id', $pessoaId)
            ->orderBy('a.id', 'DESC')->setMaxResults(1)
            ->getQuery()->getOneOrNullResult();

        // 3) Também marcar como true se tem dados na tabela específica
        // (garante consistência mesmo se pessoas_tipos estiver incompleta)
        if ($contratanteObj) $tipos['contratante'] = true;
        if ($fiadorObj) $tipos['fiador'] = true;
        if ($locadorObj) $tipos['locador'] = true;
        if ($corretorObj) $tipos['corretor'] = true;
        if ($corretoraObj) $tipos['corretora'] = true;
        if ($pretendenteObj) $tipos['pretendente'] = true;
        if ($socioObj) $tipos['socio'] = true;
        if ($advogadoObj) $tipos['advogado'] = true;

        // Array de dados (objetos ou null) — inquilino não tem tabela dedicada
        $tiposDados = [
            'contratante' => $contratanteObj,
            'fiador'      => $fiadorObj,
            'locador'     => $locadorObj,
            'corretor'    => $corretorObj,
            'corretora'   => $corretoraObj,
            'pretendente' => $pretendenteObj,
            'socio'       => $socioObj,
            'advogado'    => $advogadoObj,
        ];

        return [
            'tipos'      => $tipos,
            'tiposDados' => $tiposDados,
        ];
    }

    /** Array vazio para quando a pessoa não existe */
    private function emptyTiposArray(): array
    {
        return [
            'contratante' => null, 'fiador' => null, 'locador' => null,
            'corretor'    => null, 'corretora' => null, 'pretendente' => null,
            'socio'       => null, 'advogado' => null,
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
            ->orderBy('pd.id', 'DESC') // Pega o mais recente em caso de duplicata
            ->setMaxResults(1) // Garante apenas um resultado
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
            ->orderBy('pd.id', 'DESC') // Pega o mais recente em caso de duplicata
            ->setMaxResults(1) // Garante apenas um resultado
            ->getQuery()
            ->getOneOrNullResult();

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
                'id'               => $d->getId(),
                'tipo'           => $d->getTipoDocumento()->getId(), // ID do tipo para o select
                'tipoNome'       => $d->getTipoDocumento()->getTipo(), // Nome do tipo para exibição
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

    /**
     * Busca cônjuge por CPF, Nome ou ID
     * Retorna apenas pessoas físicas
     *
     * @param string $criteria Critério de busca (cpf, nome, id)
     * @param string $value Valor para buscar
     * @return array Array de pessoas encontradas
     */
    public function searchConjuge(string $criteria, string $value): array
    {
        $qb = $this->createQueryBuilder('p')
            ->andWhere('p.fisicaJuridica = :fisica')
            ->setParameter('fisica', 'fisica');

        switch (strtolower($criteria)) {
            case 'cpf':
            case 'cpf (pessoa física)':
                // Busca por CPF através da tabela de documentos
                $pessoa = $this->findByCpfDocumento($value);
                return $pessoa ? [$pessoa] : [];

            case 'id':
            case 'id da pessoa':
                // Busca por ID
                $qb->andWhere('p.idpessoa = :id')
                   ->setParameter('id', (int)$value);
                break;

            case 'nome':
            case 'nome completo':
                // Busca parcial por nome
                $qb->andWhere('p.nome LIKE :nome')
                   ->setParameter('nome', '%' . $value . '%')
                   ->orderBy('p.nome', 'ASC');
                break;

            default:
                return [];
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Busca pessoas físicas por nome (para seleção de cônjuge)
     * @param string $nome Nome parcial ou completo
     * @return array<Pessoas>
     */
    public function findPessoasFisicasByNome(string $nome): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.nome LIKE :nome')
            ->andWhere('p.fisicaJuridica = :fisica')
            ->setParameter('nome', '%' . $nome . '%')
            ->setParameter('fisica', 'fisica')
            ->orderBy('p.nome', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
