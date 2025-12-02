<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\InformesRendimentos;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<InformesRendimentos>
 */
class InformesRendimentosRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, InformesRendimentos::class);
    }

    /**
     * Busca informes por filtros
     *
     * @return InformesRendimentos[]
     */
    public function findByFiltros(array $filtros): array
    {
        $qb = $this->createQueryBuilder('i')
            ->leftJoin('i.proprietario', 'prop')
            ->leftJoin('i.imovel', 'im')
            ->leftJoin('i.inquilino', 'inq')
            ->leftJoin('i.planoConta', 'pc')
            ->leftJoin('i.valores', 'v');

        if (!empty($filtros['ano'])) {
            $qb->andWhere('i.ano = :ano')
                ->setParameter('ano', $filtros['ano']);
        }

        if (!empty($filtros['idProprietario'])) {
            $qb->andWhere('i.proprietario = :proprietario')
                ->setParameter('proprietario', $filtros['idProprietario']);
        }

        if (!empty($filtros['idImovel'])) {
            $qb->andWhere('i.imovel = :imovel')
                ->setParameter('imovel', $filtros['idImovel']);
        }

        if (!empty($filtros['codigoImovel'])) {
            $qb->andWhere('im.codigoInterno LIKE :codigoImovel')
                ->setParameter('codigoImovel', '%' . $filtros['codigoImovel'] . '%');
        }

        if (!empty($filtros['idInquilino'])) {
            $qb->andWhere('i.inquilino = :inquilino')
                ->setParameter('inquilino', $filtros['idInquilino']);
        }

        if (!empty($filtros['nomeInquilino'])) {
            $qb->andWhere('inq.nome LIKE :nomeInquilino')
                ->setParameter('nomeInquilino', '%' . $filtros['nomeInquilino'] . '%');
        }

        if (!empty($filtros['status'])) {
            $qb->andWhere('i.status = :status')
                ->setParameter('status', $filtros['status']);
        }

        if (!empty($filtros['idPlanoConta'])) {
            $qb->andWhere('i.planoConta = :planoConta')
                ->setParameter('planoConta', $filtros['idPlanoConta']);
        }

        $qb->orderBy('prop.nome', 'ASC')
            ->addOrderBy('im.codigoInterno', 'ASC')
            ->addOrderBy('inq.nome', 'ASC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Busca informes por ano
     *
     * @return InformesRendimentos[]
     */
    public function findByAno(int $ano): array
    {
        return $this->findByFiltros(['ano' => $ano]);
    }

    /**
     * Busca informes por proprietário e ano
     *
     * @return InformesRendimentos[]
     */
    public function findByProprietarioEAno(int $proprietarioId, int $ano): array
    {
        return $this->findByFiltros([
            'ano' => $ano,
            'idProprietario' => $proprietarioId
        ]);
    }

    /**
     * Busca informe existente por chave única
     */
    public function findByChaveUnica(
        int $ano,
        int $proprietarioId,
        int $imovelId,
        int $inquilinoId,
        int $planoContaId
    ): ?InformesRendimentos {
        return $this->createQueryBuilder('i')
            ->where('i.ano = :ano')
            ->andWhere('i.proprietario = :proprietario')
            ->andWhere('i.imovel = :imovel')
            ->andWhere('i.inquilino = :inquilino')
            ->andWhere('i.planoConta = :planoConta')
            ->setParameter('ano', $ano)
            ->setParameter('proprietario', $proprietarioId)
            ->setParameter('imovel', $imovelId)
            ->setParameter('inquilino', $inquilinoId)
            ->setParameter('planoConta', $planoContaId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Lista anos com informes
     *
     * @return int[]
     */
    public function findAnosComInformes(): array
    {
        $result = $this->createQueryBuilder('i')
            ->select('DISTINCT i.ano')
            ->orderBy('i.ano', 'DESC')
            ->getQuery()
            ->getResult();

        return array_column($result, 'ano');
    }

    /**
     * Busca informes com valores para impressão
     *
     * @return array
     */
    public function findParaImpressao(int $ano, ?int $proprietarioId = null): array
    {
        $qb = $this->createQueryBuilder('i')
            ->select([
                'i.id',
                'i.ano',
                'prop.idpessoa as proprietarioId',
                'prop.nome as proprietarioNome',
                'im.id as imovelId',
                'im.codigoInterno as imovelCodigo',
                'inq.idpessoa as inquilinoId',
                'inq.nome as inquilinoNome',
                'pc.id as planoContaId',
                'pc.codigo as planoContaCodigo',
                'pc.descricao as planoContaDescricao'
            ])
            ->join('i.proprietario', 'prop')
            ->join('i.imovel', 'im')
            ->join('i.inquilino', 'inq')
            ->join('i.planoConta', 'pc')
            ->where('i.ano = :ano')
            ->setParameter('ano', $ano);

        if ($proprietarioId !== null) {
            $qb->andWhere('i.proprietario = :proprietario')
                ->setParameter('proprietario', $proprietarioId);
        }

        $qb->orderBy('prop.nome', 'ASC')
            ->addOrderBy('im.codigoInterno', 'ASC')
            ->addOrderBy('inq.nome', 'ASC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Conta informes por status
     *
     * @return array<string, int>
     */
    public function contarPorStatus(int $ano): array
    {
        $result = $this->createQueryBuilder('i')
            ->select('i.status, COUNT(i.id) as total')
            ->where('i.ano = :ano')
            ->setParameter('ano', $ano)
            ->groupBy('i.status')
            ->getQuery()
            ->getResult();

        $contagem = [
            InformesRendimentos::STATUS_PENDENTE => 0,
            InformesRendimentos::STATUS_PROCESSADO => 0,
            InformesRendimentos::STATUS_REVISADO => 0,
            InformesRendimentos::STATUS_FINALIZADO => 0,
        ];

        foreach ($result as $row) {
            $contagem[$row['status']] = (int) $row['total'];
        }

        return $contagem;
    }

    /**
     * Busca informes para geração DIMOB
     *
     * @return InformesRendimentos[]
     */
    public function findParaDimob(
        int $ano,
        ?int $proprietarioInicial = null,
        ?int $proprietarioFinal = null
    ): array {
        $qb = $this->createQueryBuilder('i')
            ->join('i.proprietario', 'prop')
            ->join('i.imovel', 'im')
            ->join('i.inquilino', 'inq')
            ->join('i.planoConta', 'pc')
            ->leftJoin('i.valores', 'v')
            ->where('i.ano = :ano')
            ->setParameter('ano', $ano);

        if ($proprietarioInicial !== null) {
            $qb->andWhere('prop.idpessoa >= :propInicial')
                ->setParameter('propInicial', $proprietarioInicial);
        }

        if ($proprietarioFinal !== null) {
            $qb->andWhere('prop.idpessoa <= :propFinal')
                ->setParameter('propFinal', $proprietarioFinal);
        }

        $qb->orderBy('prop.idpessoa', 'ASC')
            ->addOrderBy('im.id', 'ASC')
            ->addOrderBy('inq.idpessoa', 'ASC');

        return $qb->getQuery()->getResult();
    }
}
