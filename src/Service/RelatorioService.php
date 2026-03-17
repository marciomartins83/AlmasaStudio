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

        if (!empty($filtros['agrupar_por']) && $filtros['agrupar_por'] !== 'nenhum') {
            return $this->agruparDespesas($dados, $filtros['agrupar_por']);
        }

        return $dados;
    }

    /**
     * Calcula totais das despesas via SQL nativo (sem limite de 500)
     */
    public function getTotalDespesas(array $filtros): array
    {
        $conn = $this->em->getConnection();

        $where = ["tipo_lancamento = 'despesa'"];
        $params = [];

        if (!empty($filtros['data_inicio'])) {
            $where[] = 'data_vencimento >= :data_inicio';
            $params['data_inicio'] = $filtros['data_inicio'] instanceof \DateTimeInterface
                ? $filtros['data_inicio']->format('Y-m-d') : $filtros['data_inicio'];
        }
        if (!empty($filtros['data_fim'])) {
            $where[] = 'data_vencimento <= :data_fim';
            $params['data_fim'] = $filtros['data_fim'] instanceof \DateTimeInterface
                ? $filtros['data_fim']->format('Y-m-d') : $filtros['data_fim'];
        }
        if (!empty($filtros['status']) && $filtros['status'] !== 'todos') {
            $where[] = 'situacao = :situacao';
            $params['situacao'] = $filtros['status'];
        }
        if (!empty($filtros['id_imovel'])) {
            $where[] = 'id_imovel = :id_imovel';
            $params['id_imovel'] = (int) $filtros['id_imovel'];
        }

        $whereClause = implode(' AND ', $where);
        $sql = "SELECT COUNT(*) as quantidade,
                       COALESCE(SUM(valor_total::numeric), 0) as total_geral,
                       COALESCE(SUM(CASE WHEN situacao = 'pago' THEN valor_total::numeric ELSE 0 END), 0) as total_pago,
                       COALESCE(SUM(CASE WHEN situacao != 'pago' THEN valor_total::numeric ELSE 0 END), 0) as total_aberto
                FROM lancamentos_financeiros WHERE {$whereClause}";

        $result = $conn->executeQuery($sql, $params)->fetchAssociative();

        return [
            'quantidade' => (int) ($result['quantidade'] ?? 0),
            'total_aberto' => round((float) ($result['total_aberto'] ?? 0), 2),
            'total_pago' => round((float) ($result['total_pago'] ?? 0), 2),
            'total_geral' => round((float) ($result['total_geral'] ?? 0), 2),
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

        if (!empty($filtros['agrupar_por']) && $filtros['agrupar_por'] !== 'nenhum') {
            return $this->agruparReceitas($resultado, $filtros['agrupar_por']);
        }

        return $resultado;
    }

    /**
     * Calcula totais das receitas via SQL nativo (sem limite de 500)
     */
    public function getTotalReceitas(array $filtros): array
    {
        $conn = $this->em->getConnection();

        $where = ["tipo_lancamento IN ('receita', 'aluguel')"];
        $params = [];

        if (!empty($filtros['data_inicio'])) {
            $where[] = 'data_vencimento >= :data_inicio';
            $params['data_inicio'] = $filtros['data_inicio'] instanceof \DateTimeInterface
                ? $filtros['data_inicio']->format('Y-m-d') : $filtros['data_inicio'];
        }
        if (!empty($filtros['data_fim'])) {
            $where[] = 'data_vencimento <= :data_fim';
            $params['data_fim'] = $filtros['data_fim'] instanceof \DateTimeInterface
                ? $filtros['data_fim']->format('Y-m-d') : $filtros['data_fim'];
        }
        if (!empty($filtros['status']) && $filtros['status'] !== 'todos') {
            $situacao = in_array($filtros['status'], ['efetivado', 'pago']) ? 'pago' : 'aberto';
            $where[] = 'situacao = :situacao';
            $params['situacao'] = $situacao;
        }
        if (!empty($filtros['id_imovel'])) {
            $where[] = 'id_imovel = :id_imovel';
            $params['id_imovel'] = (int) $filtros['id_imovel'];
        }

        $whereClause = implode(' AND ', $where);
        $sql = "SELECT COUNT(*) as quantidade,
                       COALESCE(SUM(valor_total::numeric), 0) as total_geral,
                       COALESCE(SUM(CASE WHEN situacao = 'pago' THEN valor_total::numeric ELSE 0 END), 0) as total_recebido,
                       COALESCE(SUM(CASE WHEN situacao != 'pago' THEN valor_total::numeric ELSE 0 END), 0) as total_aberto
                FROM lancamentos_financeiros WHERE {$whereClause}";

        $result = $conn->executeQuery($sql, $params)->fetchAssociative();

        return [
            'quantidade' => (int) ($result['quantidade'] ?? 0),
            'total_aberto' => round((float) ($result['total_aberto'] ?? 0), 2),
            'total_recebido' => round((float) ($result['total_recebido'] ?? 0), 2),
            'total_geral' => round((float) ($result['total_geral'] ?? 0), 2),
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

        $statusNormalizado = $filtros['status'] === 'efetivado' ? 'pago' : $filtros['status'];

        // Analítico precisa de itens individuais — desabilitar pré-agrupamento nas queries
        $semAgrupamento = $visualizacao === 'analitico' ? ['agrupar_por' => null] : [];

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

        // Processar despesas
        foreach ($despesas as $item) {
            $lancamento = $item instanceof Lancamentos ? $item : ($item['entidade'] ?? null);
            if (!$lancamento) continue;

            $chave = $this->getChaveAgrupamento($lancamento, $agruparPor);
            if (!isset($grupos[$chave])) {
                $grupos[$chave] = [
                    'nome' => $this->getNomeAgrupamento($lancamento, $agruparPor),
                    'receitas' => 0,
                    'despesas' => 0,
                ];
            }
            $grupos[$chave]['despesas'] += (float) $lancamento->getValor();
        }

        // Processar receitas
        foreach ($receitas as $item) {
            $valor = is_array($item) ? $item['valor'] : (float) $item->getValor();
            $entidade = is_array($item) ? $item['entidade'] : $item;

            $chave = $this->getChaveAgrupamentoReceita($entidade, $agruparPor);
            if (!isset($grupos[$chave])) {
                $grupos[$chave] = [
                    'nome' => $this->getNomeAgrupamentoReceita($entidade, $agruparPor),
                    'receitas' => 0,
                    'despesas' => 0,
                ];
            }
            $grupos[$chave]['receitas'] += $valor;
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
        if (!empty($filtros['data_inicio'])) {
            $qb2->andWhere('l.dataVencimento >= :dataInicio2')
                ->setParameter('dataInicio2', $filtros['data_inicio']);
        }
        if (!empty($filtros['data_fim'])) {
            $qb2->andWhere('l.dataVencimento <= :dataFim2')
                ->setParameter('dataFim2', $filtros['data_fim']);
        }
        $qb2->orderBy('l.dataVencimento', 'ASC');

        $movimentosCrud = array_map(fn(Lancamentos $l) => [
            'dataPagamento'   => $l->getDataPagamento() ?? $l->getDataVencimento(),
            'receber'         => $l->getTipo() === 'receber',
            'historico'       => $l->getHistorico(),
            'numeroDocumento' => $l->getNumeroDocumento(),
            'valorFloat'      => (float) ($l->getValorPago() ?: $l->getValor()),
            '_contaBancaria'  => $l->getContaBancaria(),
            '_valor'          => (float) ($l->getValorPago() ?: $l->getValor()),
            '_isReceber'      => $l->getTipo() === 'receber',
        ], $qb2->getQuery()->getResult());

        return array_merge($movimentos, $movimentosCrud);
    }

    /**
     * Calcula saldo inicial de uma conta via SQL nativo em lancamentos_financeiros
     */
    public function getSaldoInicialConta(int $contaId, \DateTimeInterface $data): float
    {
        $conn = $this->em->getConnection();
        $dataStr = $data->format('Y-m-d');

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

        $sql2 = "SELECT COALESCE(SUM(
                    CASE WHEN tipo = 'receber'
                         THEN COALESCE(valor_pago, valor)::numeric
                         ELSE -COALESCE(valor_pago, valor)::numeric
                    END
                ), 0)
                FROM lancamentos
                WHERE id_conta_bancaria = :contaId
                  AND status IN ('pago', 'pago_parcial')
                  AND data_vencimento < :data";

        $saldo2 = (float) $conn->executeQuery($sql2, ['contaId' => $contaId, 'data' => $dataStr])->fetchOne();

        return round($saldo1 + $saldo2, 2);
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

            if (!empty($filtros['mostrar_movimentos'])) {
                $contas[$contaId]['movimentos'][] = $movimento;
            }
        }

        foreach ($contas as &$conta) {
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
     * Calcula totais movimentados por plano de conta
     */
    public function getTotaisPlanoContas(array $filtros): array
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('IDENTITY(l.planoConta) as planoContaId, SUM(l.valor) as total')
            ->from(Lancamentos::class, 'l')
            ->where('l.planoConta IS NOT NULL')
            ->groupBy('l.planoConta');

        if (!empty($filtros['data_inicio'])) {
            $qb->andWhere('l.dataMovimento >= :dataInicio')
                ->setParameter('dataInicio', $filtros['data_inicio']);
        }

        if (!empty($filtros['data_fim'])) {
            $qb->andWhere('l.dataMovimento <= :dataFim')
                ->setParameter('dataFim', $filtros['data_fim']);
        }

        $results = $qb->getQuery()->getResult();

        $totais = [];
        foreach ($results as $row) {
            $totais[$row['planoContaId']] = (float) $row['total'];
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
        $saldoAnterior    = $this->calcularSaldoAnteriorExtrato($id, $inicio);
        $pagamentos       = $this->getPagamentosExtrato($id, $inicio, $fim, $status);
        $movimentacao     = $this->getMovimentacaoExtrato($id, $inicio, $fim, $status);
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

    private function calcularSaldoAnteriorExtrato(int $idProprietario, \DateTimeInterface $dataInicio): float
    {
        $conn = $this->em->getConnection();
        $data = $dataInicio instanceof \DateTimeInterface ? $dataInicio->format('Y-m-d') : $dataInicio;

        // Dados históricos (lancamentos_financeiros)
        $sql1 = "SELECT
            COALESCE(SUM(CASE WHEN tipo_lancamento IN ('aluguel','receita') THEN valor_total::numeric ELSE 0 END), 0)
            - COALESCE(SUM(CASE WHEN tipo_lancamento = 'despesa' THEN valor_total::numeric ELSE 0 END), 0)
            AS saldo
        FROM lancamentos_financeiros
        WHERE id_proprietario = :id AND situacao = 'pago' AND data_vencimento < :data";
        $saldo1 = (float)($conn->executeQuery($sql1, ['id' => $idProprietario, 'data' => $data])->fetchOne() ?? 0);

        // Dados do CRUD (lancamentos) — receita: receber como credor/proprietário OU credor em pagar (transferência)
        //                              — despesa: pagar como pagador/proprietário
        $sql2 = "SELECT
            COALESCE(SUM(CASE
                WHEN tipo = 'receber' AND (l.id_proprietario = :id OR l.id_pessoa_credor = :id OR im.id_pessoa_proprietario = :id)
                    THEN COALESCE(valor_pago::numeric, valor::numeric)
                WHEN tipo = 'pagar' AND l.id_pessoa_credor = :id
                    THEN COALESCE(valor_pago::numeric, valor::numeric)
                ELSE 0 END), 0)
            - COALESCE(SUM(CASE
                WHEN tipo = 'pagar' AND (l.id_proprietario = :id OR l.id_pessoa_pagador = :id OR im.id_pessoa_proprietario = :id) AND (l.id_pessoa_credor IS NULL OR l.id_pessoa_credor != :id)
                    THEN COALESCE(valor_pago::numeric, valor::numeric)
                ELSE 0 END), 0)
            AS saldo
        FROM lancamentos l
        LEFT JOIN imoveis im ON im.id = l.id_imovel
        WHERE (l.id_proprietario = :id OR l.id_pessoa_credor = :id OR l.id_pessoa_pagador = :id OR im.id_pessoa_proprietario = :id)
          AND l.status IN ('pago','pago_parcial')
          AND l.data_vencimento < :data";
        $saldo2 = (float)($conn->executeQuery($sql2, ['id' => $idProprietario, 'data' => $data])->fetchOne() ?? 0);

        return round($saldo1 + $saldo2, 2);
    }

    private function getPagamentosExtrato(int $idProprietario, \DateTimeInterface $inicio, \DateTimeInterface $fim, string $status): array
    {
        // Históricos (lancamentos_financeiros)
        $qb = $this->em->createQueryBuilder();
        $qb->select('lf')
            ->from(LancamentosFinanceiros::class, 'lf')
            ->where('lf.tipoLancamento = :tipo')
            ->andWhere('lf.proprietario = :prop')
            ->andWhere('lf.dataVencimento >= :inicio')
            ->andWhere('lf.dataVencimento <= :fim')
            ->setParameter('tipo', 'despesa')
            ->setParameter('prop', $idProprietario)
            ->setParameter('inicio', $inicio)
            ->setParameter('fim', $fim)
            ->orderBy('lf.dataVencimento', 'ASC');
        if ($status !== 'todos') {
            $sit = $status === 'efetivado' ? 'pago' : $status;
            $qb->andWhere('lf.situacao = :sit')->setParameter('sit', $sit);
        }
        $pagamentos = array_map(fn(LancamentosFinanceiros $lf) => [
            'data'      => $lf->getDataVencimento(),
            'historico' => $lf->getHistorico() ?? $lf->getDescricao() ?? '-',
            'valor'     => (float)$lf->getValorTotal(),
        ], $qb->getQuery()->getResult());

        // CRUD novo (lancamentos — tipo=pagar onde prop é pagador ou imóvel pertence ao prop)
        $qb2 = $this->em->createQueryBuilder();
        $qb2->select('l')
            ->from(Lancamentos::class, 'l')
            ->leftJoin('l.imovel', 'im')
            ->where('l.tipo = :tipo')
            ->andWhere('(l.proprietario = :prop OR l.pessoaPagador = :prop OR im.pessoaProprietario = :prop)')
            ->andWhere('l.dataVencimento >= :inicio')
            ->andWhere('l.dataVencimento <= :fim')
            ->setParameter('tipo', 'pagar')
            ->setParameter('prop', $idProprietario)
            ->setParameter('inicio', $inicio)
            ->setParameter('fim', $fim)
            ->orderBy('l.dataVencimento', 'ASC');
        if ($status !== 'todos') {
            $sit = $status === 'efetivado' ? 'pago' : $status;
            $qb2->andWhere('l.status = :sit')->setParameter('sit', $sit);
        }
        foreach ($qb2->getQuery()->getResult() as $l) {
            $pagamentos[] = [
                'data'      => $l->getDataVencimento(),
                'historico' => $l->getHistorico() ?? '-',
                'valor'     => (float)$l->getValorPagoFloat() ?: (float)$l->getValor(),
            ];
        }

        usort($pagamentos, fn($a, $b) => $a['data'] <=> $b['data']);
        return $pagamentos;
    }

    private function getMovimentacaoExtrato(int $idProprietario, \DateTimeInterface $inicio, \DateTimeInterface $fim, string $status): array
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('lf', 'im', 'inq')
            ->from(LancamentosFinanceiros::class, 'lf')
            ->leftJoin('lf.imovel', 'im')
            ->leftJoin('lf.inquilino', 'inq')
            ->where('lf.tipoLancamento IN (:tipos)')
            ->andWhere('lf.proprietario = :prop')
            ->andWhere('lf.dataVencimento >= :inicio')
            ->andWhere('lf.dataVencimento <= :fim')
            ->setParameter('tipos', ['aluguel', 'receita'])
            ->setParameter('prop', $idProprietario)
            ->setParameter('inicio', $inicio)
            ->setParameter('fim', $fim)
            ->orderBy('im.id', 'ASC')
            ->addOrderBy('lf.dataVencimento', 'ASC');

        if ($status !== 'todos') {
            $sit = $status === 'efetivado' ? 'pago' : $status;
            $qb->andWhere('lf.situacao = :sit')->setParameter('sit', $sit);
        }

        $grupos = [];
        foreach ($qb->getQuery()->getResult() as $lf) {
            $imovel   = $lf->getImovel();
            $imovelId = $imovel?->getId() ?? 0;

            if (!isset($grupos[$imovelId])) {
                $contrato = $imovel ? $this->getContratoAtivoImovel($imovel->getId()) : null;
                $grupos[$imovelId] = [
                    'imovel_cod'       => $imovel?->getCodigoInterno() ?? (string)$imovelId,
                    'imovel_endereco'  => $imovel ? $this->buildEnderecoImovel($imovel) : '-',
                    'inquilino_nome'   => $lf->getInquilino()?->getNome() ?? '-',
                    'proximo_reajuste' => $contrato?->getDataProximoReajuste(),
                    'lancamentos'      => [],
                    'subtotal'         => 0.0,
                ];
            }

            $valor = (float)$lf->getValorTotal();
            $grupos[$imovelId]['lancamentos'][] = [
                'data'      => $lf->getDataVencimento(),
                'historico' => $lf->getHistorico() ?? $lf->getDescricao() ?? '-',
                'valor'     => $valor,
            ];
            $grupos[$imovelId]['subtotal'] += $valor;
        }

        // CRUD novo (lancamentos — receitas: tipo=receber OU credor em tipo=pagar/transferência)
        $qb2 = $this->em->createQueryBuilder();
        $qb2->select('l')
            ->from(Lancamentos::class, 'l')
            ->leftJoin('l.imovel', 'im2')
            ->where('(
                (l.tipo = :tipoReceber AND (l.proprietario = :prop OR l.pessoaCredor = :prop OR im2.pessoaProprietario = :prop))
                OR
                (l.tipo = :tipoPagar AND l.pessoaCredor = :prop)
            )')
            ->andWhere('l.dataVencimento >= :inicio')
            ->andWhere('l.dataVencimento <= :fim')
            ->setParameter('tipoReceber', 'receber')
            ->setParameter('tipoPagar', 'pagar')
            ->setParameter('prop', $idProprietario)
            ->setParameter('inicio', $inicio)
            ->setParameter('fim', $fim)
            ->orderBy('l.dataVencimento', 'ASC');
        if ($status !== 'todos') {
            $sit = $status === 'efetivado' ? 'pago' : $status;
            $qb2->andWhere('l.status = :sit')->setParameter('sit', $sit);
        }
        foreach ($qb2->getQuery()->getResult() as $l) {
            $imovel   = $l->getImovel();
            $imovelId = $imovel?->getId() ?? 'crud_sem_imovel';
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
            $valor = (float)$l->getValorPagoFloat() ?: (float)$l->getValor();
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
