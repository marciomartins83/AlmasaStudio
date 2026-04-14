<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\ContasBancarias;
use App\Entity\ImoveisContratos;
use App\Entity\Imoveis;
use App\Entity\Lancamentos;
use App\Entity\LancamentosFinanceiros;
use App\Entity\Pessoas;
use App\Entity\PlanoContas;
use App\Repository\LancamentosRepository;
use App\Repository\LancamentosFinanceirosRepository;
use App\Repository\PlanoContasRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Twig\Environment;

/**
 * RelatorioService - Fat Service para geração de relatórios
 *
 * Contém toda a lógica de negócio para:
 * - Relatório de Inadimplentes
 * - Relatório de Despesas
 * - Relatório de Receitas
 * - Relatório Comparativo Despesas x Receitas
 * - Relatório de Contas Bancárias
 * - Relatório de Plano de Contas
 */
class RelatorioService
{
    private string $projectDir;

    public function __construct(
        private EntityManagerInterface $em,
        private LancamentosRepository $lancamentosRepository,
        private LancamentosFinanceirosRepository $lancamentosFinanceirosRepository,
        private PlanoContasRepository $planoContasRepository,
        private Environment $twig,
        ParameterBagInterface $params
    ) {
        $this->projectDir = $params->get('kernel.project_dir');
    }

    // =========================================================================
    // RELATÓRIO DE INADIMPLENTES
    // =========================================================================

    /**
     * Busca lançamentos em atraso (inadimplentes)
     */
    public function getInadimplentes(array $filtros): array
    {
        $dataReferencia = $filtros['data_referencia'] ?? new \DateTime();
        $diasAtrasoMinimo = $filtros['dias_atraso_minimo'] ?? 1;

        $qb = $this->em->createQueryBuilder();
        $limite = (int) ($filtros['limite'] ?? 500);

        $qb->select('lf', 'inq', 'im', 'prop', 'cont')
            ->from(LancamentosFinanceiros::class, 'lf')
            ->leftJoin('lf.inquilino', 'inq')
            ->leftJoin('lf.imovel', 'im')
            ->leftJoin('lf.proprietario', 'prop')
            ->leftJoin('lf.contrato', 'cont')
            ->where('lf.situacao = :situacao')
            ->andWhere('lf.dataVencimento < :dataRef')
            ->setParameter('situacao', 'aberto')
            ->setParameter('dataRef', $dataReferencia)
            ->setMaxResults($limite);

        // Filtros opcionais
        if (!empty($filtros['id_proprietario'])) {
            $qb->andWhere('lf.proprietario = :idProprietario')
                ->setParameter('idProprietario', $filtros['id_proprietario']);
        }

        if (!empty($filtros['id_imovel'])) {
            $qb->andWhere('lf.imovel = :idImovel')
                ->setParameter('idImovel', $filtros['id_imovel']);
        }

        if (!empty($filtros['id_inquilino'])) {
            $qb->andWhere('lf.inquilino = :idInquilino')
                ->setParameter('idInquilino', $filtros['id_inquilino']);
        }

        // Ordenação
        $ordenarPor = $filtros['ordenar_por'] ?? 'dias_atraso';
        switch ($ordenarPor) {
            case 'valor':
                $qb->orderBy('lf.valorTotal', 'DESC');
                break;
            case 'nome':
                $qb->orderBy('inq.nome', 'ASC');
                break;
            case 'dias_atraso':
            default:
                $qb->orderBy('lf.dataVencimento', 'ASC');
                break;
        }

        $lancamentos = $qb->getQuery()->getResult();

        // Filtrar por dias de atraso mínimo e calcular valores
        $resultado = [];
        $hoje = $dataReferencia instanceof \DateTime ? $dataReferencia : new \DateTime($dataReferencia);

        foreach ($lancamentos as $lancamento) {
            $diasAtraso = $lancamento->getDiasAtraso();

            if ($diasAtraso >= $diasAtrasoMinimo) {
                $jurosMulta = $this->calcularJurosMulta(
                    (float) $lancamento->getValorTotal(),
                    $diasAtraso,
                    $lancamento->getContrato()?->getId()
                );

                $resultado[] = [
                    'lancamento' => $lancamento,
                    'dias_atraso' => $diasAtraso,
                    'valor_original' => (float) $lancamento->getValorTotal(),
                    'valor_juros' => $jurosMulta['juros'],
                    'valor_multa' => $jurosMulta['multa'],
                    'valor_atualizado' => $jurosMulta['valor_atualizado'],
                ];
            }
        }

        // Agrupar se solicitado
        if (!empty($filtros['agrupar_por'])) {
            $resultado = $this->agruparInadimplentes($resultado, $filtros['agrupar_por']);
        }

        return $resultado;
    }

    /**
     * Calcula juros e multa de atraso
     */
    public function calcularJurosMulta(float $valor, int $diasAtraso, ?int $contratoId): array
    {
        // Default: Multa 2% + Juros 1% a.m. (pro-rata)
        $percMulta = 0.02;
        $percJurosMensal = 0.01;

        // TODO: Buscar configuração do contrato se existir
        // if ($contratoId) { ... }

        $multa = $valor * $percMulta;
        $juros = $valor * ($percJurosMensal / 30 * $diasAtraso);
        $valorAtualizado = $valor + $multa + $juros;

        return [
            'multa' => round($multa, 2),
            'juros' => round($juros, 2),
            'valor_atualizado' => round($valorAtualizado, 2),
        ];
    }

    /**
     * Agrupa inadimplentes por critério
     */
    private function agruparInadimplentes(array $dados, string $criterio): array
    {
        $grupos = [];

        foreach ($dados as $item) {
            $lancamento = $item['lancamento'];

            switch ($criterio) {
                case 'inquilino':
                    $chave = $lancamento->getInquilino()?->getIdpessoa() ?? 0;
                    $nome = $lancamento->getInquilino()?->getNome() ?? 'Sem Inquilino';
                    break;
                case 'proprietario':
                    $chave = $lancamento->getProprietario()?->getIdpessoa() ?? 0;
                    $nome = $lancamento->getProprietario()?->getNome() ?? 'Sem Proprietário';
                    break;
                case 'imovel':
                    $chave = $lancamento->getImovel()?->getId() ?? 0;
                    $nome = $lancamento->getImovel()?->getId() ?? 'Sem Imóvel';
                    break;
                default:
                    $chave = 0;
                    $nome = 'Todos';
            }

            if (!isset($grupos[$chave])) {
                $grupos[$chave] = [
                    'nome' => $nome,
                    'itens' => [],
                    'total_original' => 0,
                    'total_juros' => 0,
                    'total_multa' => 0,
                    'total_atualizado' => 0,
                ];
            }

            $grupos[$chave]['itens'][] = $item;
            $grupos[$chave]['total_original'] += $item['valor_original'];
            $grupos[$chave]['total_juros'] += $item['valor_juros'];
            $grupos[$chave]['total_multa'] += $item['valor_multa'];
            $grupos[$chave]['total_atualizado'] += $item['valor_atualizado'];
        }

        return $grupos;
    }

    /**
     * Calcula totais dos inadimplentes
     */
    public function getTotaisInadimplentes(array $dados): array
    {
        $totalOriginal = 0;
        $totalJuros = 0;
        $totalMulta = 0;
        $totalAtualizado = 0;
        $quantidade = 0;

        // Verificar se está agrupado
        $primeiroItem = reset($dados);
        if (isset($primeiroItem['itens'])) {
            // Dados agrupados
            foreach ($dados as $grupo) {
                $quantidade += count($grupo['itens']);
                $totalOriginal += $grupo['total_original'];
                $totalJuros += $grupo['total_juros'];
                $totalMulta += $grupo['total_multa'];
                $totalAtualizado += $grupo['total_atualizado'];
            }
        } else {
            // Dados não agrupados
            $quantidade = count($dados);
            foreach ($dados as $item) {
                $totalOriginal += $item['valor_original'];
                $totalJuros += $item['valor_juros'];
                $totalMulta += $item['valor_multa'];
                $totalAtualizado += $item['valor_atualizado'];
            }
        }

        return [
            'quantidade' => $quantidade,
            'total_original' => round($totalOriginal, 2),
            'total_juros' => round($totalJuros, 2),
            'total_multa' => round($totalMulta, 2),
            'total_atualizado' => round($totalAtualizado, 2),
        ];
    }

    // =========================================================================
    // RELATÓRIO DE DESPESAS
    // =========================================================================

    /**
     * Busca despesas em lancamentos_financeiros (tipo_lancamento = 'despesa')
     */
    public function getDespesas(array $filtros): array
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('lf', 'pc', 'inq', 'prop', 'im')
            ->from(LancamentosFinanceiros::class, 'lf')
            ->leftJoin('lf.conta', 'pc')
            ->leftJoin('lf.inquilino', 'inq')
            ->leftJoin('lf.proprietario', 'prop')
            ->leftJoin('lf.imovel', 'im')
            ->where('lf.tipoLancamento = :tipo')
            ->setParameter('tipo', 'despesa')
            ->setMaxResults(500);

        $this->aplicarFiltrosDataFinanceiro($qb, $filtros, 'lf');

        if (!empty($filtros['status']) && $filtros['status'] !== 'todos') {
            $qb->andWhere('lf.situacao = :situacao')
                ->setParameter('situacao', $filtros['status']);
        }

        if (!empty($filtros['id_imovel'])) {
            $qb->andWhere('lf.imovel = :idImovel')
                ->setParameter('idImovel', $filtros['id_imovel']);
        }

        if (!empty($filtros['id_proprietario'])) {
            $qb->andWhere('lf.proprietario = :idProprietario')
                ->setParameter('idProprietario', $filtros['id_proprietario']);
        }

        $qb->orderBy('lf.dataVencimento', 'ASC');
        $lancamentos = $qb->getQuery()->getResult();

        $dados = [];
        foreach ($lancamentos as $lf) {
            $dados[] = [
                'entidade' => $lf,
                'dataVencimento' => $lf->getDataVencimento(),
                'numeroDocumento' => $lf->getNumeroBoleto() ?? $lf->getNumeroRecibo(),
                'pessoaCredor' => ['nome' => $lf->getInquilino()?->getNome() ?? $lf->getProprietario()?->getNome()],
                'historico' => $lf->getHistorico() ?? $lf->getDescricao(),
                'planoConta' => ['descricao' => $lf->getConta()?->getDescricao() ?? 'Despesa'],
                'valorFloat' => (float) $lf->getValorTotal(),
                'statusBadgeClass' => $this->getSituacaoBadge($lf->getSituacao() ?? 'aberto'),
                'statusLabel' => $lf->getSituacaoLabel(),
                '_planoContaId' => (string) ($lf->getConta()?->getId() ?? '0'),
                '_planoContaDescricao' => $lf->getConta()?->getDescricao() ?? 'Despesa',
                '_credorNome' => $lf->getInquilino()?->getNome() ?? $lf->getProprietario()?->getNome() ?? 'Sem Fornecedor',
                '_imovelId' => (string) ($lf->getImovel()?->getId() ?? '0'),
                '_mes' => $lf->getDataVencimento()->format('Y-m'),
            ];
        }

        // lancamentos CRUD via plano de contas: DEBITO em conta tipo passivo (saida da CC do proprietario)
        $conn = $this->em->getConnection();
        $where2 = ["pc.tipo = 'passivo'"];
        $params2 = [];

        if (!empty($filtros['data_inicio'])) {
            $where2[] = 'l.data_vencimento >= :di2';
            $params2['di2'] = $filtros['data_inicio'] instanceof \DateTimeInterface ? $filtros['data_inicio']->format('Y-m-d') : $filtros['data_inicio'];
        }
        if (!empty($filtros['data_fim'])) {
            $where2[] = 'l.data_vencimento <= :df2';
            $params2['df2'] = $filtros['data_fim'] instanceof \DateTimeInterface ? $filtros['data_fim']->format('Y-m-d') : $filtros['data_fim'];
        }
        if (!empty($filtros['status']) && $filtros['status'] !== 'todos') {
            $where2[] = 'l.status = :st2';
            $params2['st2'] = $filtros['status'] === 'efetivado' ? 'pago' : $filtros['status'];
        }
        if (!empty($filtros['id_imovel'])) {
            $where2[] = 'l.id_imovel = :im2';
            $params2['im2'] = (int) $filtros['id_imovel'];
        }
        if (!empty($filtros['id_proprietario'])) {
            $where2[] = "pc.codigo = '2.1.01.' || (SELECT cod::text FROM pessoas WHERE idpessoa = :pr2)";
            $params2['pr2'] = (int) $filtros['id_proprietario'];
        }

        // Excluir transferencias passivo→passivo (entre proprietarios)
        $where2[] = "(l.id_plano_conta_credito IS NULL OR NOT EXISTS (
            SELECT 1 FROM almasa_plano_contas pcr WHERE pcr.id = l.id_plano_conta_credito AND pcr.tipo = 'passivo'
        ))";

        $wc2 = implode(' AND ', $where2);
        $rows2 = $conn->fetchAllAssociative(
            "SELECT l.id, l.data_vencimento, l.historico, l.valor, l.valor_pago, l.status,
                    pc.id AS pc_id, pc.codigo AS pc_codigo, pc.descricao AS pc_descricao
             FROM lancamentos l
             JOIN almasa_plano_contas pc ON pc.id = l.id_plano_conta_debito
             WHERE {$wc2}
             ORDER BY l.data_vencimento ASC LIMIT 500", $params2
        );

        foreach ($rows2 as $r) {
            $dados[] = [
                'entidade' => null,
                'dataVencimento' => new \DateTime($r['data_vencimento']),
                'numeroDocumento' => null,
                'pessoaCredor' => ['nome' => $r['pc_descricao']],
                'historico' => $r['historico'] ?? '-',
                'planoConta' => ['descricao' => $r['pc_codigo'] . ' - ' . $r['pc_descricao']],
                'valorFloat' => (float) ($r['valor_pago'] ?: $r['valor']),
                'statusBadgeClass' => $this->getSituacaoBadge($r['status'] ?? 'aberto'),
                'statusLabel' => ucfirst($r['status'] ?? ''),
                '_planoContaId' => (string) $r['pc_id'],
                '_planoContaDescricao' => $r['pc_codigo'] . ' - ' . $r['pc_descricao'],
                '_credorNome' => $r['pc_descricao'],
                '_imovelId' => '0',
                '_mes' => substr($r['data_vencimento'], 0, 7),
            ];
        }

        usort($dados, fn($a, $b) => $a['dataVencimento'] <=> $b['dataVencimento']);

        if (!empty($filtros['agrupar_por']) && $filtros['agrupar_por'] !== 'nenhum') {
            return $this->agruparDespesas($dados, $filtros['agrupar_por']);
        }

        return $dados;
    }

    /**
     * Calcula totais das despesas (lancamentos_financeiros + CRUD via plano passivo)
     */
    public function getTotalDespesas(array $filtros): array
    {
        $conn = $this->em->getConnection();

        // 1. lancamentos_financeiros (historico)
        $where1 = ["tipo_lancamento = 'despesa'"];
        $params1 = [];
        $this->buildFiltrosDataHistorico($where1, $params1, $filtros);
        $wc1 = implode(' AND ', $where1);
        $r1 = $conn->executeQuery("SELECT COUNT(*) as q, COALESCE(SUM(valor_total::numeric),0) as t,
            COALESCE(SUM(CASE WHEN situacao='pago' THEN valor_total::numeric ELSE 0 END),0) as tp,
            COALESCE(SUM(CASE WHEN situacao!='pago' THEN valor_total::numeric ELSE 0 END),0) as ta
            FROM lancamentos_financeiros WHERE {$wc1}", $params1)->fetchAssociative();

        // 2. CRUD: debito em conta passivo (excluindo passivo→passivo)
        $where2 = ["pc.tipo = 'passivo'",
            "(l.id_plano_conta_credito IS NULL OR NOT EXISTS (SELECT 1 FROM almasa_plano_contas pcr WHERE pcr.id = l.id_plano_conta_credito AND pcr.tipo = 'passivo'))"];
        $params2 = [];
        $this->buildFiltrosDataCrud($where2, $params2, $filtros);
        $wc2 = implode(' AND ', $where2);
        $r2 = $conn->executeQuery("SELECT COUNT(*) as q, COALESCE(SUM(COALESCE(l.valor_pago,l.valor)::numeric),0) as t,
            COALESCE(SUM(CASE WHEN l.status='pago' THEN COALESCE(l.valor_pago,l.valor)::numeric ELSE 0 END),0) as tp,
            COALESCE(SUM(CASE WHEN l.status!='pago' THEN COALESCE(l.valor_pago,l.valor)::numeric ELSE 0 END),0) as ta
            FROM lancamentos l JOIN almasa_plano_contas pc ON pc.id = l.id_plano_conta_debito WHERE {$wc2}", $params2)->fetchAssociative();

        return [
            'quantidade' => (int)($r1['q']??0) + (int)($r2['q']??0),
            'total_aberto' => round((float)($r1['ta']??0) + (float)($r2['ta']??0), 2),
            'total_pago' => round((float)($r1['tp']??0) + (float)($r2['tp']??0), 2),
            'total_geral' => round((float)($r1['t']??0) + (float)($r2['t']??0), 2),
        ];
    }

    /**
     * Agrupa despesas (arrays normalizados) por critério
     */
    private function agruparDespesas(array $dados, string $criterio): array
    {
        $grupos = [];

        foreach ($dados as $item) {
            [$chave, $nome] = match ($criterio) {
                'plano_conta' => [$item['_planoContaId'], $item['_planoContaDescricao']],
                'fornecedor' => [$item['_credorNome'], $item['_credorNome']],
                'imovel' => [$item['_imovelId'], 'Imóvel ' . ($item['_imovelId'] !== '0' ? $item['_imovelId'] : 'Sem Imóvel')],
                'mes' => [$item['_mes'], \DateTime::createFromFormat('Y-m', $item['_mes'])->format('m/Y')],
                default => ['0', 'Todos'],
            };

            if (!isset($grupos[$chave])) {
                $grupos[$chave] = ['nome' => $nome, 'itens' => [], 'total' => 0];
            }

            $grupos[$chave]['itens'][] = $item;
            $grupos[$chave]['total'] += $item['valorFloat'];
        }

        return $grupos;
    }

    /**
     * Retorna classe CSS para badge de situação
     */
    private function getSituacaoBadge(string $situacao): string
    {
        return match ($situacao) {
            'pago' => 'success',
            'cancelado', 'estornado' => 'secondary',
            default => 'warning',
        };
    }

    // =========================================================================
    // RELATÓRIO DE RECEITAS
    // =========================================================================

    /**
     * Busca receitas em lancamentos_financeiros (tipo receita + aluguel)
     */
    public function getReceitas(array $filtros): array
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('lf', 'pc', 'inq', 'im')
            ->from(LancamentosFinanceiros::class, 'lf')
            ->leftJoin('lf.conta', 'pc')
            ->leftJoin('lf.inquilino', 'inq')
            ->leftJoin('lf.imovel', 'im')
            ->where('lf.tipoLancamento IN (:tipos)')
            ->setParameter('tipos', ['receita', 'aluguel'])
            ->setMaxResults(500);

        $this->aplicarFiltrosDataFinanceiro($qb, $filtros, 'lf');

        if (!empty($filtros['id_imovel'])) {
            $qb->andWhere('lf.imovel = :idImovel')
                ->setParameter('idImovel', $filtros['id_imovel']);
        }

        if (!empty($filtros['id_proprietario'])) {
            $qb->andWhere('lf.proprietario = :idProprietario')
                ->setParameter('idProprietario', $filtros['id_proprietario']);
        }

        if (!empty($filtros['status']) && $filtros['status'] !== 'todos') {
            $situacao = $filtros['status'] === 'efetivado' ? 'pago' : ($filtros['status'] === 'pago' ? 'pago' : 'aberto');
            $qb->andWhere('lf.situacao = :situacao')
                ->setParameter('situacao', $situacao);
        }

        $qb->orderBy('lf.dataVencimento', 'ASC');
        $lancamentos = $qb->getQuery()->getResult();

        $resultado = [];
        foreach ($lancamentos as $lf) {
            $resultado[] = [
                'tipo' => 'ficha_financeira',
                'entidade' => $lf,
                'data' => $lf->getDataVencimento(),
                'documento' => $lf->getNumeroBoleto() ?? $lf->getNumeroRecibo(),
                'pagador' => $lf->getInquilino()?->getNome() ?? '-',
                'historico' => $lf->getHistorico() ?? $lf->getDescricao(),
                'plano_conta' => $lf->getConta()?->getDescricao() ?? ($lf->getTipoLancamento() === 'aluguel' ? 'Aluguel' : 'Receita'),
                'imovel' => $lf->getImovel()?->getId(),
                'valor' => (float) $lf->getValorTotal(),
                'status' => $lf->getSituacao(),
            ];
        }

        // lancamentos CRUD via plano de contas: CREDITO em conta tipo passivo (entrada na CC do proprietario)
        $conn = $this->em->getConnection();
        $where2 = ["pc.tipo = 'passivo'"];
        $params2 = [];

        if (!empty($filtros['data_inicio'])) {
            $where2[] = 'l.data_vencimento >= :di2';
            $params2['di2'] = $filtros['data_inicio'] instanceof \DateTimeInterface ? $filtros['data_inicio']->format('Y-m-d') : $filtros['data_inicio'];
        }
        if (!empty($filtros['data_fim'])) {
            $where2[] = 'l.data_vencimento <= :df2';
            $params2['df2'] = $filtros['data_fim'] instanceof \DateTimeInterface ? $filtros['data_fim']->format('Y-m-d') : $filtros['data_fim'];
        }
        if (!empty($filtros['status']) && $filtros['status'] !== 'todos') {
            $where2[] = 'l.status = :st2';
            $params2['st2'] = $filtros['status'] === 'efetivado' ? 'pago' : $filtros['status'];
        }
        if (!empty($filtros['id_imovel'])) {
            $where2[] = 'l.id_imovel = :im2';
            $params2['im2'] = (int) $filtros['id_imovel'];
        }
        if (!empty($filtros['id_proprietario'])) {
            $where2[] = "pc.codigo = '2.1.01.' || (SELECT cod::text FROM pessoas WHERE idpessoa = :pr2)";
            $params2['pr2'] = (int) $filtros['id_proprietario'];
        }

        // Excluir transferencias passivo→passivo (entre proprietarios)
        $where2[] = "(l.id_plano_conta_debito IS NULL OR NOT EXISTS (
            SELECT 1 FROM almasa_plano_contas pd WHERE pd.id = l.id_plano_conta_debito AND pd.tipo = 'passivo'
        ))";

        $wc2 = implode(' AND ', $where2);
        $rows2 = $conn->fetchAllAssociative(
            "SELECT l.id, l.data_vencimento, l.historico, l.valor, l.valor_pago, l.status,
                    pc.id AS pc_id, pc.codigo AS pc_codigo, pc.descricao AS pc_descricao
             FROM lancamentos l
             JOIN almasa_plano_contas pc ON pc.id = l.id_plano_conta_credito
             WHERE {$wc2}
             ORDER BY l.data_vencimento ASC LIMIT 500", $params2
        );

        foreach ($rows2 as $r) {
            $resultado[] = [
                'tipo' => 'lancamento_crud',
                'entidade' => null,
                'data' => new \DateTime($r['data_vencimento']),
                'documento' => null,
                'pagador' => $r['pc_descricao'],
                'historico' => $r['historico'] ?? '-',
                'plano_conta' => $r['pc_codigo'] . ' - ' . $r['pc_descricao'],
                'imovel' => null,
                'valor' => (float) ($r['valor_pago'] ?: $r['valor']),
                'status' => $r['status'],
            ];
        }

        usort($resultado, fn($a, $b) => $a['data'] <=> $b['data']);

        if (!empty($filtros['agrupar_por']) && $filtros['agrupar_por'] !== 'nenhum') {
            return $this->agruparReceitas($resultado, $filtros['agrupar_por']);
        }

        return $resultado;
    }

    /**
     * Calcula totais das receitas (lancamentos_financeiros + CRUD via plano passivo)
     */
    public function getTotalReceitas(array $filtros): array
    {
        $conn = $this->em->getConnection();

        // 1. lancamentos_financeiros (historico)
        $where1 = ["tipo_lancamento IN ('receita', 'aluguel')"];
        $params1 = [];
        $this->buildFiltrosDataHistorico($where1, $params1, $filtros);
        $wc1 = implode(' AND ', $where1);
        $r1 = $conn->executeQuery("SELECT COUNT(*) as q, COALESCE(SUM(valor_total::numeric),0) as t,
            COALESCE(SUM(CASE WHEN situacao='pago' THEN valor_total::numeric ELSE 0 END),0) as tr,
            COALESCE(SUM(CASE WHEN situacao!='pago' THEN valor_total::numeric ELSE 0 END),0) as ta
            FROM lancamentos_financeiros WHERE {$wc1}", $params1)->fetchAssociative();

        // 2. CRUD: credito em conta passivo (excluindo passivo→passivo)
        $where2 = ["pc.tipo = 'passivo'",
            "(l.id_plano_conta_debito IS NULL OR NOT EXISTS (SELECT 1 FROM almasa_plano_contas pd WHERE pd.id = l.id_plano_conta_debito AND pd.tipo = 'passivo'))"];
        $params2 = [];
        $this->buildFiltrosDataCrud($where2, $params2, $filtros);
        $wc2 = implode(' AND ', $where2);
        $r2 = $conn->executeQuery("SELECT COUNT(*) as q, COALESCE(SUM(COALESCE(l.valor_pago,l.valor)::numeric),0) as t,
            COALESCE(SUM(CASE WHEN l.status='pago' THEN COALESCE(l.valor_pago,l.valor)::numeric ELSE 0 END),0) as tr,
            COALESCE(SUM(CASE WHEN l.status!='pago' THEN COALESCE(l.valor_pago,l.valor)::numeric ELSE 0 END),0) as ta
            FROM lancamentos l JOIN almasa_plano_contas pc ON pc.id = l.id_plano_conta_credito WHERE {$wc2}", $params2)->fetchAssociative();

        return [
            'quantidade' => (int)($r1['q']??0) + (int)($r2['q']??0),
            'total_aberto' => round((float)($r1['ta']??0) + (float)($r2['ta']??0), 2),
            'total_recebido' => round((float)($r1['tr']??0) + (float)($r2['tr']??0), 2),
            'total_geral' => round((float)($r1['t']??0) + (float)($r2['t']??0), 2),
        ];
    }

    // =========================================================================
    // RELATÓRIO DESPESAS x RECEITAS (COMPARATIVO)
    // =========================================================================

    /**
     * Busca dados comparativos de despesas e receitas
     */
    public function getDespesasReceitas(array $filtros): array
    {
        $visualizacao = $filtros['visualizacao'] ?? 'sintetico';
        $statusFiltro = $filtros['status'] ?? 'todos';
        $statusNormalizado = $statusFiltro === 'efetivado' ? 'pago' : $statusFiltro;

        // O comparativo sempre precisa de dados flat; o agrupamento sintético é
        // feito internamente para manter despesas e receitas no mesmo critério.
        $semAgrupamento = ['agrupar_por' => null];

        // Buscar despesas
        $filtrosDespesas = array_merge($filtros, ['status' => $statusNormalizado], $semAgrupamento);
        $despesas = $this->getDespesas($filtrosDespesas);

        // Buscar receitas
        $filtrosReceitas = array_merge($filtros, [
            'status' => $statusNormalizado,
            'origem' => 'todos',
        ], $semAgrupamento);
        $receitas = $this->getReceitas($filtrosReceitas);

        if ($visualizacao === 'sintetico') {
            return $this->gerarComparativoSintetico($despesas, $receitas, $filtros);
        }

        return $this->gerarComparativoAnalitico($despesas, $receitas, $filtros);
    }

    /**
     * Gera visão sintética do comparativo
     */
    private function gerarComparativoSintetico(array $despesas, array $receitas, array $filtros): array
    {
        $agruparPor = $filtros['agrupar_por'] ?? 'plano_conta';
        $grupos = [];

        foreach ($despesas as $item) {
            if (!is_array($item) || !isset($item['valorFloat'])) {
                continue;
            }

            [$chave, $nome] = $this->extrairAgrupamentoComparativoDespesa($item, $agruparPor);
            if (!isset($grupos[$chave])) {
                $grupos[$chave] = [
                    'nome' => $nome,
                    'receitas' => 0,
                    'despesas' => 0,
                ];
            }
            $grupos[$chave]['despesas'] += (float) $item['valorFloat'];
        }

        foreach ($receitas as $item) {
            if (!is_array($item) || !isset($item['valor'])) {
                continue;
            }

            [$chave, $nome] = $this->extrairAgrupamentoComparativoReceita($item, $agruparPor);
            if (!isset($grupos[$chave])) {
                $grupos[$chave] = [
                    'nome' => $nome,
                    'receitas' => 0,
                    'despesas' => 0,
                ];
            }
            $grupos[$chave]['receitas'] += (float) $item['valor'];
        }

        // Calcular saldos e percentuais
        $totalReceitas = array_sum(array_column($grupos, 'receitas'));
        $totalDespesas = array_sum(array_column($grupos, 'despesas'));

        foreach ($grupos as &$grupo) {
            $grupo['saldo'] = $grupo['receitas'] - $grupo['despesas'];
            $grupo['percentual_receitas'] = $totalReceitas > 0 ? round($grupo['receitas'] / $totalReceitas * 100, 2) : 0;
            $grupo['percentual_despesas'] = $totalDespesas > 0 ? round($grupo['despesas'] / $totalDespesas * 100, 2) : 0;
        }

        return $grupos;
    }

    private function extrairAgrupamentoComparativoDespesa(array $item, string $agruparPor): array
    {
        return match ($agruparPor) {
            'plano_conta' => [
                (string) ($item['_planoContaId'] ?? '0'),
                $item['planoConta']['descricao'] ?? '-',
            ],
            'imovel' => $this->extrairAgrupamentoComparativoImovel($item['_imovelId'] ?? null),
            'mes' => $this->extrairAgrupamentoComparativoMes($item['_mes'] ?? null),
            default => ['0', 'Todos'],
        };
    }

    private function extrairAgrupamentoComparativoReceita(array $item, string $agruparPor): array
    {
        return match ($agruparPor) {
            'plano_conta' => [
                (string) ($item['plano_conta'] ?? '0'),
                $item['plano_conta'] ?? '-',
            ],
            'imovel' => $this->extrairAgrupamentoComparativoImovel($item['imovel'] ?? null),
            'mes' => $this->extrairAgrupamentoComparativoMes(
                isset($item['data']) && $item['data'] instanceof \DateTimeInterface
                    ? $item['data']->format('Y-m')
                    : null
            ),
            default => ['0', 'Todos'],
        };
    }

    private function extrairAgrupamentoComparativoImovel(mixed $imovelId): array
    {
        $id = (string) ($imovelId ?? '0');

        return [
            $id,
            $id !== '0' && $id !== '' ? 'Imóvel ' . $id : 'Imóvel Sem Imóvel',
        ];
    }

    private function extrairAgrupamentoComparativoMes(?string $mes): array
    {
        if ($mes && preg_match('/^\d{4}-\d{2}$/', $mes)) {
            return [$mes, \DateTime::createFromFormat('Y-m', $mes)->format('m/Y')];
        }

        return ['sem_data', 'Sem data'];
    }

    /**
     * Gera visão analítica do comparativo
     */
    private function gerarComparativoAnalitico(array $despesas, array $receitas, array $filtros): array
    {
        $resultado = [];

        // Processar despesas (getDespesas retorna arrays normalizados com chaves valorFloat/dataVencimento/etc)
        foreach ($despesas as $item) {
            if (!is_array($item) || !isset($item['valorFloat'])) continue;

            $resultado[] = [
                'data' => $item['dataVencimento'],
                'tipo' => 'D',
                'historico' => $item['historico'] ?? '-',
                'plano_conta' => $item['planoConta']['descricao'] ?? '-',
                'valor_receita' => 0,
                'valor_despesa' => (float) $item['valorFloat'],
            ];
        }

        // Processar receitas (getReceitas retorna arrays normalizados com chaves data/historico/plano_conta/valor)
        foreach ($receitas as $item) {
            if (!is_array($item) || !isset($item['valor'])) continue;

            $resultado[] = [
                'data' => $item['data'],
                'tipo' => 'R',
                'historico' => $item['historico'] ?? '-',
                'plano_conta' => $item['plano_conta'] ?? '-',
                'valor_receita' => (float) $item['valor'],
                'valor_despesa' => 0,
            ];
        }

        // Ordenar por data
        usort($resultado, fn($a, $b) => $a['data'] <=> $b['data']);

        // Calcular saldo acumulado
        $saldoAcumulado = 0;
        foreach ($resultado as &$item) {
            $saldoAcumulado += $item['valor_receita'] - $item['valor_despesa'];
            $item['saldo_acumulado'] = round($saldoAcumulado, 2);
        }

        return $resultado;
    }

    /**
     * Calcula saldo do período
     */
    public function getSaldoPeriodo(array $filtros): float
    {
        $totaisReceitas = $this->getTotalReceitas($filtros);
        $totaisDespesas = $this->getTotalDespesas($filtros);

        return round($totaisReceitas['total_geral'] - $totaisDespesas['total_geral'], 2);
    }

    /**
     * Calcula saldo anterior: soma de (receitas - despesas) antes da data_inicio.
     * Usa APENAS lancamentos (CRUD novo). O historico migrado (lancamentos_financeiros)
     * nao tem repasses, gerando saldos absurdos.
     */
    public function calcularSaldoAnterior(array $filtros): float
    {
        if (empty($filtros['data_inicio'])) {
            return 0.0;
        }

        $conn = $this->em->getConnection();
        $dataInicio = $filtros['data_inicio'] instanceof \DateTimeInterface
            ? $filtros['data_inicio']->format('Y-m-d') : $filtros['data_inicio'];

        $where = ['data_vencimento < :data_inicio'];
        $params = ['data_inicio' => $dataInicio];

        if (!empty($filtros['status']) && $filtros['status'] !== 'todos') {
            $where[] = 'status = :status';
            $params['status'] = $filtros['status'] === 'efetivado' ? 'pago' : $filtros['status'];
        }
        if (!empty($filtros['id_imovel'])) {
            $where[] = 'id_imovel = :id_imovel';
            $params['id_imovel'] = (int) $filtros['id_imovel'];
        }
        if (!empty($filtros['id_proprietario'])) {
            $where[] = 'id_proprietario = :id_prop';
            $params['id_prop'] = (int) $filtros['id_proprietario'];
        }

        $whereClause = implode(' AND ', $where);
        $sql = "SELECT
                    COALESCE(SUM(CASE WHEN tipo = 'receber' THEN valor::numeric ELSE 0 END), 0)
                    - COALESCE(SUM(CASE WHEN tipo = 'pagar' THEN valor::numeric ELSE 0 END), 0)
                    AS saldo
                FROM lancamentos WHERE {$whereClause}";

        return round((float) ($conn->executeQuery($sql, $params)->fetchAssociative()['saldo'] ?? 0), 2);
    }

    // =========================================================================
    // RELATÓRIO DE CONTAS BANCÁRIAS
    // =========================================================================

    /**
     * Busca movimentos de conta bancária em lancamentos_financeiros
     * Retorna arrays normalizados com as chaves esperadas pelo template
     */
    public function getMovimentosContaBancaria(array $filtros): array
    {
        // --- lancamentos_financeiros (histórico migrado) ---
        $qb = $this->em->createQueryBuilder();
        $qb->select('lf', 'cb')
            ->from(LancamentosFinanceiros::class, 'lf')
            ->leftJoin('lf.contaBancaria', 'cb')
            ->where('lf.contaBancaria IS NOT NULL')
            ->andWhere('lf.situacao = :situacao')
            ->setParameter('situacao', 'pago');

        if (!empty($filtros['id_conta_bancaria'])) {
            $qb->andWhere('lf.contaBancaria = :idConta')
                ->setParameter('idConta', $filtros['id_conta_bancaria']);
        }
        if (!empty($filtros['data_inicio'])) {
            $qb->andWhere('lf.dataVencimento >= :dataInicio')
                ->setParameter('dataInicio', $filtros['data_inicio']);
        }
        if (!empty($filtros['data_fim'])) {
            $qb->andWhere('lf.dataVencimento <= :dataFim')
                ->setParameter('dataFim', $filtros['data_fim']);
        }
        $qb->orderBy('lf.dataVencimento', 'ASC');

        $movimentos = array_map(fn(LancamentosFinanceiros $lf) => [
            'dataPagamento'   => $lf->getDataVencimento(),
            'receber'         => in_array($lf->getTipoLancamento(), ['receita', 'aluguel']),
            'historico'       => $lf->getHistorico() ?? $lf->getDescricao(),
            'numeroDocumento' => $lf->getNumeroBoleto() ?? $lf->getNumeroRecibo(),
            'valorFloat'      => (float) $lf->getValorTotal(),
            '_contaBancaria'  => $lf->getContaBancaria(),
            '_valor'          => (float) $lf->getValorTotal(),
            '_isReceber'      => in_array($lf->getTipoLancamento(), ['receita', 'aluguel']),
        ], $qb->getQuery()->getResult());

        // --- lancamentos CRUD ---
        $qb2 = $this->em->createQueryBuilder();
        $qb2->select('l', 'cb2')
            ->from(Lancamentos::class, 'l')
            ->leftJoin('l.contaBancaria', 'cb2')
            ->where('l.contaBancaria IS NOT NULL')
            ->andWhere('l.status IN (:statuses)')
            ->setParameter('statuses', ['pago', 'pago_parcial']);

        if (!empty($filtros['id_conta_bancaria'])) {
            $qb2->andWhere('l.contaBancaria = :idConta2')
                ->setParameter('idConta2', $filtros['id_conta_bancaria']);
        }
        $this->aplicarFiltroDataMovimentoContaBancariaCrud($qb2, 'l', 'dataInicio2', 'dataFim2', $filtros);

        $movimentosCrud = array_map(function(Lancamentos $l) {
            // Transferência (débito+crédito): contaBancaria é a do débito = SAÍDA
            $isTransferencia = $l->getPlanoContaDebito() && $l->getPlanoContaCredito();
            $isReceber = $isTransferencia ? false : ($l->getTipo() === 'receber');

            return [
                'dataPagamento'   => $this->getDataMovimentoContaBancariaCrud($l),
                'receber'         => $isReceber,
                'historico'       => $l->getHistorico(),
                'numeroDocumento' => $l->getNumeroDocumento(),
                'valorFloat'      => (float) ($l->getValorPago() ?: $l->getValor()),
                '_contaBancaria'  => $l->getContaBancaria(),
                '_valor'          => (float) ($l->getValorPago() ?: $l->getValor()),
                '_isReceber'      => $isReceber,
            ];
        }, $qb2->getQuery()->getResult());

        // --- Transferências (partida dobrada): gerar movimento na conta do crédito ---
        // A query acima pega o débito (contaBancaria). Aqui geramos a entrada na conta vinculada ao crédito.
        $qb3 = $this->em->createQueryBuilder();
        $qb3->select('l')
            ->from(Lancamentos::class, 'l')
            ->where('l.planoContaDebito IS NOT NULL')
            ->andWhere('l.planoContaCredito IS NOT NULL')
            ->andWhere('l.status IN (:st3)')
            ->setParameter('st3', ['pago', 'pago_parcial']);
        $this->aplicarFiltroDataMovimentoContaBancariaCrud($qb3, 'l', 'di3', 'df3', $filtros);

        $vinculoRepo = $this->em->getRepository(\App\Entity\AlmasaVinculoBancario::class);
        foreach ($qb3->getQuery()->getResult() as $l) {
            // Conta do crédito: buscar via vínculo bancário do plano crédito
            $pcCredito = $l->getPlanoContaCredito();
            if (!$pcCredito) continue;

            $vinculos = $vinculoRepo->findBy(['almasaPlanoConta' => $pcCredito, 'ativo' => true], ['padrao' => 'DESC'], 1);
            if (empty($vinculos)) continue;

            $contaCredito = $vinculos[0]->getContaBancaria();
            if (!$contaCredito) continue;

            // Se filtrando por conta específica, verificar
            if (!empty($filtros['id_conta_bancaria']) && $contaCredito->getId() != $filtros['id_conta_bancaria']) continue;

            // Não duplicar se a conta do crédito é a mesma do débito
            if ($l->getContaBancaria() && $contaCredito->getId() === $l->getContaBancaria()->getId()) continue;

            $valor = (float) ($l->getValorPago() ?: $l->getValor());
            $movimentosCrud[] = [
                'dataPagamento'   => $this->getDataMovimentoContaBancariaCrud($l),
                'receber'         => true, // entrada na conta crédito
                'historico'       => $l->getHistorico() . ' (transferência)',
                'numeroDocumento' => $l->getNumeroDocumento(),
                'valorFloat'      => $valor,
                '_contaBancaria'  => $contaCredito,
                '_valor'          => $valor,
                '_isReceber'      => true,
            ];

        }

        return array_merge($movimentos, $movimentosCrud);
    }

    private function getDataMovimentoContaBancariaCrud(Lancamentos $lancamento): \DateTimeInterface
    {
        return $lancamento->getDataPagamento() ?? $lancamento->getDataVencimento();
    }

    private function aplicarFiltroDataMovimentoContaBancariaCrud(
        QueryBuilder $qb,
        string $alias,
        string $paramInicio,
        string $paramFim,
        array $filtros
    ): void
    {
        if (!empty($filtros['data_inicio'])) {
            $qb->andWhere(sprintf(
                '((%1$s.dataPagamento IS NOT NULL AND %1$s.dataPagamento >= :%2$s) OR (%1$s.dataPagamento IS NULL AND %1$s.dataVencimento >= :%2$s))',
                $alias,
                $paramInicio
            ))->setParameter($paramInicio, $filtros['data_inicio']);
        }

        if (!empty($filtros['data_fim'])) {
            $qb->andWhere(sprintf(
                '((%1$s.dataPagamento IS NOT NULL AND %1$s.dataPagamento <= :%2$s) OR (%1$s.dataPagamento IS NULL AND %1$s.dataVencimento <= :%2$s))',
                $alias,
                $paramFim
            ))->setParameter($paramFim, $filtros['data_fim']);
        }
    }

    /**
     * Calcula saldo inicial de uma conta via SQL nativo em lancamentos_financeiros
     */
    public function getSaldoInicialConta(int $contaId, \DateTimeInterface $data): float
    {
        $conn = $this->em->getConnection();
        $dataStr = $data->format('Y-m-d');

        // 1. lancamentos_financeiros (histórico migrado)
        $sql1 = "SELECT COALESCE(SUM(
                    CASE WHEN tipo_lancamento IN ('receita', 'aluguel')
                         THEN valor_total::numeric
                         ELSE -valor_total::numeric
                    END
                ), 0)
                FROM lancamentos_financeiros
                WHERE id_conta_bancaria = :contaId
                  AND situacao = 'pago'
                  AND data_vencimento < :data";
        $saldo1 = (float) $conn->executeQuery($sql1, ['contaId' => $contaId, 'data' => $dataStr])->fetchOne();

        // 2. lancamentos CRUD (não-transferência)
        $sql2 = "SELECT COALESCE(SUM(
                    CASE WHEN tipo = 'receber'
                         THEN COALESCE(valor_pago, valor)::numeric
                         ELSE -COALESCE(valor_pago, valor)::numeric
                    END
                ), 0)
                FROM lancamentos
                WHERE id_conta_bancaria = :contaId
                  AND status IN ('pago', 'pago_parcial')
                  AND COALESCE(data_pagamento, data_vencimento) < :data
                  AND (id_plano_conta_debito IS NULL OR id_plano_conta_credito IS NULL)";
        $saldo2 = (float) $conn->executeQuery($sql2, ['contaId' => $contaId, 'data' => $dataStr])->fetchOne();

        // 3. Transferências — saída (conta do débito = contaBancaria)
        $sql3 = "SELECT COALESCE(SUM(COALESCE(valor_pago, valor)::numeric), 0)
                FROM lancamentos
                WHERE id_conta_bancaria = :contaId
                  AND id_plano_conta_debito IS NOT NULL
                  AND id_plano_conta_credito IS NOT NULL
                  AND status IN ('pago', 'pago_parcial')
                  AND COALESCE(data_pagamento, data_vencimento) < :data";
        $saldoTransfSaida = (float) $conn->executeQuery($sql3, ['contaId' => $contaId, 'data' => $dataStr])->fetchOne();

        // 4. Transferências — entrada (conta vinculada ao plano crédito)
        $sql4 = "SELECT COALESCE(SUM(COALESCE(l.valor_pago, l.valor)::numeric), 0)
                FROM lancamentos l
                JOIN almasa_vinculos_bancarios v ON v.id_almasa_plano_conta = l.id_plano_conta_credito AND v.ativo = true
                WHERE v.id_conta_bancaria = :contaId
                  AND l.id_plano_conta_debito IS NOT NULL
                  AND l.id_plano_conta_credito IS NOT NULL
                  AND l.status IN ('pago', 'pago_parcial')
                  AND COALESCE(l.data_pagamento, l.data_vencimento) < :data
                  AND (l.id_conta_bancaria IS NULL OR l.id_conta_bancaria != :contaId)";
        $saldoTransfEntrada = (float) $conn->executeQuery($sql4, ['contaId' => $contaId, 'data' => $dataStr])->fetchOne();

        return round($saldo1 + $saldo2 - $saldoTransfSaida + $saldoTransfEntrada, 2);
    }

    /**
     * Retorna resumo por conta bancária agrupando movimentos normalizados
     */
    public function getResumoContas(array $filtros): array
    {
        $movimentos = $this->getMovimentosContaBancaria($filtros);

        $contas = [];

        foreach ($movimentos as $movimento) {
            $conta   = $movimento['_contaBancaria'];
            $contaId = $conta->getId();

            if (!isset($contas[$contaId])) {
                $dataInicio = $filtros['data_inicio'] ?? new \DateTime('first day of this month');
                if (!$dataInicio instanceof \DateTimeInterface) {
                    $dataInicio = new \DateTime($dataInicio);
                }

                $contas[$contaId] = [
                    'conta'         => $conta,
                    'saldo_inicial' => $this->getSaldoInicialConta($contaId, $dataInicio),
                    'entradas'      => 0,
                    'saidas'        => 0,
                    'movimentos'    => [],
                ];
            }

            if ($movimento['_isReceber']) {
                $contas[$contaId]['entradas'] += $movimento['_valor'];
            } else {
                $contas[$contaId]['saidas'] += $movimento['_valor'];
            }

            $contas[$contaId]['movimentos'][] = $movimento;
        }

        foreach ($contas as &$conta) {
            usort(
                $conta['movimentos'],
                static fn(array $a, array $b): int => $a['dataPagamento'] <=> $b['dataPagamento']
            );
            $conta['saldo_final'] = $conta['saldo_inicial'] + $conta['entradas'] - $conta['saidas'];
        }

        return $contas;
    }

    // =========================================================================
    // RELATÓRIO DE PLANO DE CONTAS
    // =========================================================================

    /**
     * Busca plano de contas com filtros
     */
    public function getPlanoContas(array $filtros): array
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('pc')
            ->from(PlanoContas::class, 'pc')
            ->orderBy('pc.codigo', 'ASC');

        // Filtro por tipo
        if (!empty($filtros['tipo']) && $filtros['tipo'] !== 'todos') {
            $tipoValor = match ($filtros['tipo']) {
                'receita' => 0,
                'despesa' => 1,
                'transitoria' => 2,
                'caixa' => 3,
                default => null
            };

            if ($tipoValor !== null) {
                $qb->andWhere('pc.tipo = :tipo')
                    ->setParameter('tipo', $tipoValor);
            }
        }

        // Filtro por status
        if (!empty($filtros['ativo']) && $filtros['ativo'] !== 'todos') {
            $ativo = $filtros['ativo'] === 'ativos';
            $qb->andWhere('pc.ativo = :ativo')
                ->setParameter('ativo', $ativo);
        }

        $contas = $qb->getQuery()->getResult();

        // Adicionar totais movimentados se solicitado
        if (!empty($filtros['mostrar_totais']) && !empty($filtros['data_inicio']) && !empty($filtros['data_fim'])) {
            $totais = $this->getTotaisPlanoContas($filtros);

            foreach ($contas as $conta) {
                $conta->totalMovimentado = $totais[$conta->getId()] ?? 0;
            }
        }

        return $contas;
    }

    /**
     * Calcula totais movimentados por plano de conta (legado + almasa débito/crédito)
     */
    public function getTotaisPlanoContas(array $filtros): array
    {
        $conn = $this->em->getConnection();
        $params = [];
        $whereDatas = '';

        if (!empty($filtros['data_inicio'])) {
            $whereDatas .= ' AND l.data_movimento >= :di';
            $params['di'] = $filtros['data_inicio'] instanceof \DateTimeInterface
                ? $filtros['data_inicio']->format('Y-m-d') : $filtros['data_inicio'];
        }
        if (!empty($filtros['data_fim'])) {
            $whereDatas .= ' AND l.data_movimento <= :df';
            $params['df'] = $filtros['data_fim'] instanceof \DateTimeInterface
                ? $filtros['data_fim']->format('Y-m-d') : $filtros['data_fim'];
        }

        // Legado (planoConta)
        $sql = "
            SELECT plano_id, SUM(valor) as total FROM (
                SELECT id_plano_conta as plano_id, valor::numeric as valor
                FROM lancamentos l
                WHERE id_plano_conta IS NOT NULL {$whereDatas}
            UNION ALL
                SELECT id_plano_conta_debito as plano_id, -valor::numeric as valor
                FROM lancamentos l
                WHERE id_plano_conta_debito IS NOT NULL {$whereDatas}
            UNION ALL
                SELECT id_plano_conta_credito as plano_id, valor::numeric as valor
                FROM lancamentos l
                WHERE id_plano_conta_credito IS NOT NULL {$whereDatas}
            ) sub
            GROUP BY plano_id
        ";

        $results = $conn->executeQuery($sql, $params)->fetchAllAssociative();

        $totais = [];
        foreach ($results as $row) {
            $totais[(int)$row['plano_id']] = (float) $row['total'];
        }

        return $totais;
    }

    // =========================================================================
    // GERAÇÃO DE PDF
    // =========================================================================

    /**
     * Gera PDF do relatório
     */
    public function gerarPdf(string $tipo, array $dados, array $filtros): string
    {
        $template = match ($tipo) {
            'inadimplentes' => 'relatorios/pdf/inadimplentes.html.twig',
            'despesas' => 'relatorios/pdf/despesas.html.twig',
            'receitas' => 'relatorios/pdf/receitas.html.twig',
            'despesas_receitas' => 'relatorios/pdf/despesas_receitas.html.twig',
            'contas_bancarias' => 'relatorios/pdf/contas_bancarias.html.twig',
            'plano_contas' => 'relatorios/pdf/plano_contas.html.twig',
            'extrato_proprietario' => 'relatorios/pdf/extrato_proprietario.html.twig',
            default => throw new \InvalidArgumentException("Tipo de relatório inválido: $tipo"),
        };

        $logoPath = $this->projectDir . '/public/images/almasa-logo.png';
        $logoDataUri = file_exists($logoPath)
            ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath))
            : '';

        $html = $this->twig->render($template, [
            'dados' => $dados,
            'filtros' => $filtros,
            'data_emissao' => new \DateTime(),
            'logo_data_uri' => $logoDataUri,
        ]);

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);

        // Orientação baseada no tipo
        $orientation = in_array($tipo, ['despesas_receitas', 'contas_bancarias']) ? 'landscape' : 'portrait';
        $dompdf->setPaper('A4', $orientation);

        $dompdf->render();

        return $dompdf->output();
    }

    // =========================================================================
    // RELATÓRIO EXTRATO DE CONTA CORRENTE DO PROPRIETÁRIO
    // =========================================================================

    /**
     * Gera extrato de conta corrente por proprietário no período.
     * Estrutura: saldo anterior + pagamentos (despesas) + movimentação (receitas por imóvel) + saldo atual
     */
    public function getExtratoProprietario(array $filtros): array
    {
        $idProprietario = (int)($filtros['id_proprietario'] ?? 0);
        $dataInicio = $filtros['data_inicio'] ?? new \DateTime('first day of this month');
        $dataFim    = $filtros['data_fim']    ?? new \DateTime();
        $status     = $filtros['status']      ?? 'todos';

        if ($idProprietario) {
            $proprietario = $this->em->getRepository(Pessoas::class)->find($idProprietario);
            if (!$proprietario) return [];
            return [$this->buildExtratoUnico($idProprietario, $proprietario, $dataInicio, $dataFim, $status)];
        }

        // Sem filtro: todos os proprietários com movimento no período
        $ids = $this->getProprietariosIdsComMovimento($dataInicio, $dataFim);
        $result = [];
        foreach ($ids as $id) {
            $proprietario = $this->em->getRepository(Pessoas::class)->find($id);
            if (!$proprietario) continue;
            $result[] = $this->buildExtratoUnico($id, $proprietario, $dataInicio, $dataFim, $status);
        }
        return $result;
    }

    private function buildExtratoUnico(int $id, Pessoas $proprietario, \DateTimeInterface $inicio, \DateTimeInterface $fim, string $status): array
    {
        $planoIdProp      = $this->getPlanoIdProprietario($id);
        $saldoAnterior    = $this->calcularSaldoAnteriorExtrato($id, $inicio, $planoIdProp);
        $pagamentos       = $this->getPagamentosExtrato($id, $inicio, $fim, $status, $planoIdProp);
        $movimentacao     = $this->getMovimentacaoExtrato($id, $inicio, $fim, $status, $planoIdProp);
        $totalPagamentos  = array_sum(array_column($pagamentos, 'valor'));
        $totalMovimentacao = array_sum(array_map(fn($g) => $g['subtotal'], $movimentacao));

        return [
            'proprietario'       => $proprietario,
            'saldo_anterior'     => $saldoAnterior,
            'pagamentos'         => $pagamentos,
            'movimentacao'       => $movimentacao,
            'total_pagamentos'   => $totalPagamentos,
            'total_movimentacao' => $totalMovimentacao,
            'saldo_atual'        => $saldoAnterior - $totalPagamentos + $totalMovimentacao,
        ];
    }

    private function getProprietariosIdsComMovimento(\DateTimeInterface $inicio, \DateTimeInterface $fim): array
    {
        $conn = $this->em->getConnection();
        $i = $inicio->format('Y-m-d');
        $f = $fim->format('Y-m-d');

        $sql = "
            SELECT DISTINCT id_proprietario AS id FROM lancamentos_financeiros
            WHERE id_proprietario IS NOT NULL AND data_vencimento BETWEEN :i AND :f
            UNION
            SELECT DISTINCT id_proprietario AS id FROM lancamentos
            WHERE id_proprietario IS NOT NULL AND data_vencimento BETWEEN :i AND :f
            UNION
            SELECT DISTINCT im.id_pessoa_proprietario AS id FROM lancamentos l
            JOIN imoveis im ON im.id = l.id_imovel
            WHERE l.data_vencimento BETWEEN :i AND :f
            ORDER BY id
        ";

        return array_column(
            $conn->executeQuery($sql, ['i' => $i, 'f' => $f])->fetchAllAssociative(),
            'id'
        );
    }

    /**
     * Busca o id do plano de contas analitico que representa o proprietario.
     * Padrao: codigo '2.1.01.{pessoas.cod}' (sufixo é o cod legado MySQL).
     */
    private function getPlanoIdProprietario(int $idProprietario): ?int
    {
        $conn = $this->em->getConnection();
        $sql = "SELECT pc.id FROM almasa_plano_contas pc
                JOIN pessoas p ON pc.codigo = '2.1.01.' || p.cod::text
                WHERE p.idpessoa = :id LIMIT 1";
        $id = $conn->executeQuery($sql, ['id' => $idProprietario])->fetchOne();
        return $id ? (int)$id : null;
    }

    /**
     * Saldo anterior do proprietario: usa APENAS a tabela `lancamentos` (CRUD novo)
     * via plano de contas analitico do proprietario. O historico migrado
     * (lancamentos_financeiros) e ignorado porque os repasses antigos nao foram
     * trazidos da migracao MySQL, gerando saldos absurdos.
     *
     * CREDITO no plano = entrada na conta corrente do prop
     * DEBITO no plano  = saida (despesa do prop)
     */
    private function calcularSaldoAnteriorExtrato(int $idProprietario, \DateTimeInterface $dataInicio, ?int $planoIdProp = null): float
    {
        if (!$planoIdProp) {
            return 0.0;
        }

        $conn = $this->em->getConnection();
        $data = $dataInicio instanceof \DateTimeInterface ? $dataInicio->format('Y-m-d') : $dataInicio;

        $sql = "SELECT
            COALESCE(SUM(CASE WHEN id_plano_conta_credito = :plano THEN COALESCE(valor_pago, valor)::numeric ELSE 0 END), 0)
            - COALESCE(SUM(CASE WHEN id_plano_conta_debito = :plano THEN COALESCE(valor_pago, valor)::numeric ELSE 0 END), 0)
            AS saldo
        FROM lancamentos
        WHERE (id_plano_conta_credito = :plano OR id_plano_conta_debito = :plano)
          AND status IN ('pago','pago_parcial')
          AND data_vencimento < :data";

        return round((float)($conn->executeQuery($sql, ['plano' => $planoIdProp, 'data' => $data])->fetchOne() ?? 0), 2);
    }

    /**
     * Pagamentos do extrato: lancamentos onde planoContaDebito = plano do proprietario
     * (saida da conta corrente dele). Usa APENAS tabela `lancamentos` (CRUD novo).
     */
    private function getPagamentosExtrato(int $idProprietario, \DateTimeInterface $inicio, \DateTimeInterface $fim, string $status, ?int $planoIdProp = null): array
    {
        if (!$planoIdProp) {
            return [];
        }

        $qb = $this->em->createQueryBuilder();
        $qb->select('l')
            ->from(Lancamentos::class, 'l')
            ->where('l.planoContaDebito = :plano')
            ->andWhere('l.dataVencimento >= :inicio')
            ->andWhere('l.dataVencimento <= :fim')
            ->setParameter('plano', $planoIdProp)
            ->setParameter('inicio', $inicio)
            ->setParameter('fim', $fim)
            ->orderBy('l.dataVencimento', 'ASC');
        if ($status !== 'todos') {
            $sit = $status === 'efetivado' ? 'pago' : $status;
            $qb->andWhere('l.status = :sit')->setParameter('sit', $sit);
        }

        $pagamentos = [];
        foreach ($qb->getQuery()->getResult() as $l) {
            $pagamentos[] = [
                'data'      => $l->getDataVencimento(),
                'historico' => $l->getHistorico() ?? '-',
                'valor'     => (float)($l->getValorPagoFloat() ?: $l->getValor()),
            ];
        }

        return $pagamentos;
    }

    /**
     * Movimentacao do extrato: lancamentos onde planoContaCredito = plano do proprietario
     * (entrada na conta corrente dele). Usa APENAS tabela `lancamentos` (CRUD novo).
     */
    private function getMovimentacaoExtrato(int $idProprietario, \DateTimeInterface $inicio, \DateTimeInterface $fim, string $status, ?int $planoIdProp = null): array
    {
        $grupos = [];

        if (!$planoIdProp) {
            return [];
        }

        $qb = $this->em->createQueryBuilder();
        $qb->select('l')
            ->from(Lancamentos::class, 'l')
            ->leftJoin('l.imovel', 'im')
            ->where('l.planoContaCredito = :plano')
            ->andWhere('l.dataVencimento >= :inicio')
            ->andWhere('l.dataVencimento <= :fim')
            ->setParameter('plano', $planoIdProp)
            ->setParameter('inicio', $inicio)
            ->setParameter('fim', $fim)
            ->orderBy('im.id', 'ASC')
            ->addOrderBy('l.dataVencimento', 'ASC');
        if ($status !== 'todos') {
            $sit = $status === 'efetivado' ? 'pago' : $status;
            $qb->andWhere('l.status = :sit')->setParameter('sit', $sit);
        }
        foreach ($qb->getQuery()->getResult() as $l) {
            $imovel = $l->getImovel();
            $imovelId = $imovel?->getId() ?? 'sem_imovel';
            if (!isset($grupos[$imovelId])) {
                $contrato = $imovel ? $this->getContratoAtivoImovel($imovel->getId()) : null;
                $grupos[$imovelId] = [
                    'imovel_cod'      => $imovel?->getCodigoInterno() ?? 'S/N',
                    'imovel_endereco' => $imovel ? $this->buildEnderecoImovel($imovel) : 'Sem imóvel vinculado',
                    'inquilino_nome'  => '-',
                    'proximo_reajuste'=> $contrato?->getDataProximoReajuste(),
                    'lancamentos'     => [],
                    'subtotal'        => 0.0,
                ];
            }
            $valor = (float)($l->getValorPagoFloat() ?: $l->getValor());
            $grupos[$imovelId]['lancamentos'][] = [
                'data'      => $l->getDataVencimento(),
                'historico' => $l->getHistorico() ?? '-',
                'valor'     => $valor,
            ];
            $grupos[$imovelId]['subtotal'] += $valor;
        }

        return array_values($grupos);
    }

    private function getContratoAtivoImovel(int $imovelId): ?ImoveisContratos
    {
        return $this->em->getRepository(ImoveisContratos::class)->findOneBy([
            'imovel'  => $imovelId,
            'status'  => 'ativo',
        ]);
    }

    private function buildEnderecoImovel(Imoveis $imovel): string
    {
        try {
            $end  = $imovel->getEndereco();
            $log  = $end?->getLogradouro();
            $rua  = $log?->getLogradouro() ?? '';
            $num  = $end?->getEndNumero() ?? '';
            $comp = $end?->getComplemento() ?? '';
            $partes = array_filter([$rua, $num ? (string)$num : '', $comp]);
            return implode(', ', $partes) ?: $imovel->getCodigoInterno() ?? '';
        } catch (\Throwable) {
            return $imovel->getCodigoInterno() ?? '';
        }
    }

    // =========================================================================
    // MÉTODOS AUXILIARES
    // =========================================================================

    /**
     * Aplica filtros de data em QueryBuilder de Lancamentos
     */
    private function aplicarFiltrosData(\Doctrine\ORM\QueryBuilder $qb, array $filtros, string $alias): void
    {
        $tipoData = $filtros['tipo_data'] ?? 'vencimento';
        $campo = match ($tipoData) {
            'pagamento' => 'dataPagamento',
            'movimento' => 'dataMovimento',
            default => 'dataVencimento',
        };

        if (!empty($filtros['data_inicio'])) {
            $qb->andWhere("$alias.$campo >= :dataInicio")
                ->setParameter('dataInicio', $filtros['data_inicio']);
        }

        if (!empty($filtros['data_fim'])) {
            $qb->andWhere("$alias.$campo <= :dataFim")
                ->setParameter('dataFim', $filtros['data_fim']);
        }
    }

    /**
     * Build filtros de data e proprietario para SQL nativo em lancamentos_financeiros
     */
    private function buildFiltrosDataHistorico(array &$where, array &$params, array $filtros): void
    {
        if (!empty($filtros['data_inicio'])) {
            $where[] = 'data_vencimento >= :di';
            $params['di'] = $filtros['data_inicio'] instanceof \DateTimeInterface ? $filtros['data_inicio']->format('Y-m-d') : $filtros['data_inicio'];
        }
        if (!empty($filtros['data_fim'])) {
            $where[] = 'data_vencimento <= :df';
            $params['df'] = $filtros['data_fim'] instanceof \DateTimeInterface ? $filtros['data_fim']->format('Y-m-d') : $filtros['data_fim'];
        }
        if (!empty($filtros['status']) && $filtros['status'] !== 'todos') {
            $where[] = 'situacao = :sit';
            $params['sit'] = in_array($filtros['status'], ['efetivado', 'pago']) ? 'pago' : $filtros['status'];
        }
        if (!empty($filtros['id_imovel'])) {
            $where[] = 'id_imovel = :imv';
            $params['imv'] = (int) $filtros['id_imovel'];
        }
        if (!empty($filtros['id_proprietario'])) {
            $where[] = 'id_proprietario = :prop';
            $params['prop'] = (int) $filtros['id_proprietario'];
        }
    }

    /**
     * Build filtros de data e proprietario para SQL nativo em lancamentos (CRUD) via plano
     */
    private function buildFiltrosDataCrud(array &$where, array &$params, array $filtros): void
    {
        if (!empty($filtros['data_inicio'])) {
            $where[] = 'l.data_vencimento >= :dic';
            $params['dic'] = $filtros['data_inicio'] instanceof \DateTimeInterface ? $filtros['data_inicio']->format('Y-m-d') : $filtros['data_inicio'];
        }
        if (!empty($filtros['data_fim'])) {
            $where[] = 'l.data_vencimento <= :dfc';
            $params['dfc'] = $filtros['data_fim'] instanceof \DateTimeInterface ? $filtros['data_fim']->format('Y-m-d') : $filtros['data_fim'];
        }
        if (!empty($filtros['status']) && $filtros['status'] !== 'todos') {
            $where[] = 'l.status = :stc';
            $params['stc'] = $filtros['status'] === 'efetivado' ? 'pago' : $filtros['status'];
        }
        if (!empty($filtros['id_imovel'])) {
            $where[] = 'l.id_imovel = :imc';
            $params['imc'] = (int) $filtros['id_imovel'];
        }
        if (!empty($filtros['id_proprietario'])) {
            $where[] = "pc.codigo = '2.1.01.' || (SELECT cod::text FROM pessoas WHERE idpessoa = :prc)";
            $params['prc'] = (int) $filtros['id_proprietario'];
        }
    }

    /**
     * Aplica filtros de data em QueryBuilder de LancamentosFinanceiros
     */
    private function aplicarFiltrosDataFinanceiro(\Doctrine\ORM\QueryBuilder $qb, array $filtros, string $alias): void
    {
        if (!empty($filtros['data_inicio'])) {
            $qb->andWhere("$alias.dataVencimento >= :dataInicio")
                ->setParameter('dataInicio', $filtros['data_inicio']);
        }

        if (!empty($filtros['data_fim'])) {
            $qb->andWhere("$alias.dataVencimento <= :dataFim")
                ->setParameter('dataFim', $filtros['data_fim']);
        }
    }

    /**
     * Aplica filtros comuns de lançamentos
     */
    private function aplicarFiltrosLancamentos(\Doctrine\ORM\QueryBuilder $qb, array $filtros): void
    {
        if (!empty($filtros['id_plano_conta'])) {
            $qb->andWhere('l.planoConta = :idPlanoConta')
                ->setParameter('idPlanoConta', $filtros['id_plano_conta']);
        }

        if (!empty($filtros['id_imovel'])) {
            $qb->andWhere('l.imovel = :idImovel')
                ->setParameter('idImovel', $filtros['id_imovel']);
        }

        if (!empty($filtros['id_contrato'])) {
            $qb->andWhere('l.contrato = :idContrato')
                ->setParameter('idContrato', $filtros['id_contrato']);
        }

        if (!empty($filtros['id_pessoa_credor'])) {
            $qb->andWhere('l.pessoaCredor = :idCredor')
                ->setParameter('idCredor', $filtros['id_pessoa_credor']);
        }

        if (!empty($filtros['id_pessoa_pagador'])) {
            $qb->andWhere('l.pessoaPagador = :idPagador')
                ->setParameter('idPagador', $filtros['id_pessoa_pagador']);
        }
    }

    /**
     * Agrupa lançamentos por critério
     */
    private function agruparLancamentos(array $lancamentos, string $criterio): array
    {
        $grupos = [];

        foreach ($lancamentos as $lancamento) {
            $chave = $this->getChaveAgrupamento($lancamento, $criterio);
            $nome = $this->getNomeAgrupamento($lancamento, $criterio);

            if (!isset($grupos[$chave])) {
                $grupos[$chave] = [
                    'nome' => $nome,
                    'itens' => [],
                    'total' => 0,
                ];
            }

            $grupos[$chave]['itens'][] = $lancamento;
            $grupos[$chave]['total'] += (float) $lancamento->getValor();
        }

        return $grupos;
    }

    /**
     * Agrupa receitas por critério
     */
    private function agruparReceitas(array $receitas, string $criterio): array
    {
        $grupos = [];

        foreach ($receitas as $item) {
            $chave = match ($criterio) {
                'plano_conta' => $item['plano_conta'] ?? 'Outros',
                'pagador' => $item['pagador'] ?? 'Sem Pagador',
                'imovel' => $item['imovel'] ?? 'Sem Imóvel',
                default => 'Todos',
            };

            if (!isset($grupos[$chave])) {
                $grupos[$chave] = [
                    'nome' => $chave,
                    'itens' => [],
                    'total' => 0,
                ];
            }

            $grupos[$chave]['itens'][] = $item;
            $grupos[$chave]['total'] += $item['valor'];
        }

        return $grupos;
    }

    /**
     * Retorna chave de agrupamento para lançamento
     */
    private function getChaveAgrupamento(Lancamentos $lancamento, string $criterio): string
    {
        return match ($criterio) {
            'plano_conta' => (string) $lancamento->getPlanoConta()->getId(),
            'fornecedor' => (string) ($lancamento->getPessoaCredor()?->getIdpessoa() ?? 0),
            'imovel' => (string) ($lancamento->getImovel()?->getId() ?? 0),
            'mes' => $lancamento->getDataVencimento()->format('Y-m'),
            default => '0',
        };
    }

    /**
     * Retorna nome de agrupamento para lançamento
     */
    private function getNomeAgrupamento(Lancamentos $lancamento, string $criterio): string
    {
        return match ($criterio) {
            'plano_conta' => $lancamento->getPlanoConta()->getDescricao(),
            'fornecedor' => $lancamento->getPessoaCredor()?->getNome() ?? 'Sem Fornecedor',
            'imovel' => $lancamento->getImovel()?->getId() ?? 'Sem Imóvel',
            'mes' => $lancamento->getDataVencimento()->format('m/Y'),
            default => 'Todos',
        };
    }

    /**
     * Retorna chave de agrupamento para receita
     */
    private function getChaveAgrupamentoReceita($entidade, string $criterio): string
    {
        if ($entidade instanceof Lancamentos) {
            return $this->getChaveAgrupamento($entidade, $criterio);
        }

        if ($entidade instanceof LancamentosFinanceiros) {
            return match ($criterio) {
                'plano_conta' => (string) ($entidade->getConta()?->getId() ?? 0),
                'pagador' => (string) ($entidade->getInquilino()?->getIdpessoa() ?? 0),
                'imovel' => (string) ($entidade->getImovel()?->getId() ?? 0),
                'mes' => $entidade->getDataVencimento()->format('Y-m'),
                default => '0',
            };
        }

        return '0';
    }

    /**
     * Retorna nome de agrupamento para receita
     */
    private function getNomeAgrupamentoReceita($entidade, string $criterio): string
    {
        if ($entidade instanceof Lancamentos) {
            return $this->getNomeAgrupamento($entidade, $criterio);
        }

        if ($entidade instanceof LancamentosFinanceiros) {
            return match ($criterio) {
                'plano_conta' => $entidade->getConta()?->getDescricao() ?? 'Aluguel',
                'pagador' => $entidade->getInquilino()?->getNome() ?? 'Sem Pagador',
                'imovel' => $entidade->getImovel()?->getId() ?? 'Sem Imóvel',
                'mes' => $entidade->getDataVencimento()->format('m/Y'),
                default => 'Todos',
            };
        }

        return 'Outros';
    }

    /**
     * Retorna data conforme tipo especificado
     */
    private function getDataPorTipo(Lancamentos $lancamento, string $tipoData): \DateTimeInterface
    {
        return match ($tipoData) {
            'pagamento' => $lancamento->getDataPagamento() ?? $lancamento->getDataVencimento(),
            'movimento' => $lancamento->getDataMovimento(),
            default => $lancamento->getDataVencimento(),
        };
    }

    /**
     * Soma valores de lançamento aos totais
     */
    private function somarLancamento(Lancamentos $lancamento, float &$totalAberto, float &$totalPago, float &$totalGeral): void
    {
        $valor = (float) $lancamento->getValor();
        $totalGeral += $valor;

        if ($lancamento->isPago()) {
            $totalPago += $valor;
        } else {
            $totalAberto += $valor;
        }
    }

    /**
     * Soma valores de receita aos totais
     */
    private function somarReceita(array $item, float &$totalAberto, float &$totalRecebido, float &$totalGeral): void
    {
        $valor = $item['valor'];
        $totalGeral += $valor;

        if ($item['status'] === 'pago') {
            $totalRecebido += $valor;
        } else {
            $totalAberto += $valor;
        }
    }
}
