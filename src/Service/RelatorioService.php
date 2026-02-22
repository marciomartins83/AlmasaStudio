<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\ContasBancarias;
use App\Entity\Lancamentos;
use App\Entity\LancamentosFinanceiros;
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
        $qb->select('lf')
            ->from(LancamentosFinanceiros::class, 'lf')
            ->leftJoin('lf.inquilino', 'inq')
            ->leftJoin('lf.imovel', 'im')
            ->leftJoin('lf.proprietario', 'prop')
            ->where('lf.situacao = :situacao')
            ->andWhere('lf.dataVencimento < :dataRef')
            ->setParameter('situacao', 'aberto')
            ->setParameter('dataRef', $dataReferencia);

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
     * Busca despesas (contas a pagar)
     */
    public function getDespesas(array $filtros): array
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('l')
            ->from(Lancamentos::class, 'l')
            ->leftJoin('l.planoConta', 'pc')
            ->leftJoin('l.pessoaCredor', 'cred')
            ->leftJoin('l.imovel', 'im')
            ->where('l.tipo = :tipo')
            ->setParameter('tipo', Lancamentos::TIPO_PAGAR);

        $this->aplicarFiltrosData($qb, $filtros, 'l');
        $this->aplicarFiltrosLancamentos($qb, $filtros);

        // Status
        if (!empty($filtros['status']) && $filtros['status'] !== 'todos') {
            $qb->andWhere('l.status = :status')
                ->setParameter('status', $filtros['status']);
        }

        $qb->orderBy('l.dataVencimento', 'ASC');

        $despesas = $qb->getQuery()->getResult();

        // Agrupar se solicitado
        if (!empty($filtros['agrupar_por']) && $filtros['agrupar_por'] !== 'nenhum') {
            return $this->agruparLancamentos($despesas, $filtros['agrupar_por']);
        }

        return $despesas;
    }

    /**
     * Calcula totais das despesas
     */
    public function getTotalDespesas(array $filtros): array
    {
        $despesas = $this->getDespesas($filtros);

        $totalAberto = 0;
        $totalPago = 0;
        $totalGeral = 0;
        $quantidade = 0;

        // Verificar se está agrupado
        $primeiroItem = reset($despesas);
        if (is_array($primeiroItem) && isset($primeiroItem['itens'])) {
            foreach ($despesas as $grupo) {
                foreach ($grupo['itens'] as $lancamento) {
                    $this->somarLancamento($lancamento, $totalAberto, $totalPago, $totalGeral);
                    $quantidade++;
                }
            }
        } else {
            foreach ($despesas as $lancamento) {
                $this->somarLancamento($lancamento, $totalAberto, $totalPago, $totalGeral);
                $quantidade++;
            }
        }

        return [
            'quantidade' => $quantidade,
            'total_aberto' => round($totalAberto, 2),
            'total_pago' => round($totalPago, 2),
            'total_geral' => round($totalGeral, 2),
        ];
    }

    // =========================================================================
    // RELATÓRIO DE RECEITAS
    // =========================================================================

    /**
     * Busca receitas (contas a receber)
     */
    public function getReceitas(array $filtros): array
    {
        $origem = $filtros['origem'] ?? 'todos';
        $resultado = [];

        // Buscar de lancamentos (tipo receber)
        if ($origem === 'todos' || $origem === 'lancamentos') {
            $qb = $this->em->createQueryBuilder();
            $qb->select('l')
                ->from(Lancamentos::class, 'l')
                ->leftJoin('l.planoConta', 'pc')
                ->leftJoin('l.pessoaPagador', 'pag')
                ->leftJoin('l.imovel', 'im')
                ->where('l.tipo = :tipo')
                ->setParameter('tipo', Lancamentos::TIPO_RECEBER);

            $this->aplicarFiltrosData($qb, $filtros, 'l');
            $this->aplicarFiltrosLancamentos($qb, $filtros);

            if (!empty($filtros['status']) && $filtros['status'] !== 'todos') {
                $qb->andWhere('l.status = :status')
                    ->setParameter('status', $filtros['status']);
            }

            $qb->orderBy('l.dataVencimento', 'ASC');
            $lancamentos = $qb->getQuery()->getResult();

            foreach ($lancamentos as $l) {
                $resultado[] = [
                    'tipo' => 'lancamento',
                    'entidade' => $l,
                    'data' => $this->getDataPorTipo($l, $filtros['tipo_data'] ?? 'vencimento'),
                    'documento' => $l->getNumeroDocumento(),
                    'pagador' => $l->getPessoaPagador()?->getNome() ?? '-',
                    'historico' => $l->getHistorico(),
                    'plano_conta' => $l->getPlanoConta()->getDescricao(),
                    'imovel' => $l->getImovel()?->getId(),
                    'valor' => (float) $l->getValor(),
                    'status' => $l->getStatus(),
                ];
            }
        }

        // Buscar de lancamentos_financeiros (ficha financeira)
        if ($origem === 'todos' || $origem === 'ficha_financeira') {
            $qb = $this->em->createQueryBuilder();
            $qb->select('lf')
                ->from(LancamentosFinanceiros::class, 'lf')
                ->leftJoin('lf.conta', 'pc')
                ->leftJoin('lf.inquilino', 'inq')
                ->leftJoin('lf.imovel', 'im');

            $this->aplicarFiltrosDataFinanceiro($qb, $filtros, 'lf');

            if (!empty($filtros['id_imovel'])) {
                $qb->andWhere('lf.imovel = :idImovel')
                    ->setParameter('idImovel', $filtros['id_imovel']);
            }

            if (!empty($filtros['status']) && $filtros['status'] !== 'todos') {
                $situacao = $filtros['status'] === 'pago' ? 'pago' : 'aberto';
                $qb->andWhere('lf.situacao = :situacao')
                    ->setParameter('situacao', $situacao);
            }

            $qb->orderBy('lf.dataVencimento', 'ASC');
            $financeiros = $qb->getQuery()->getResult();

            foreach ($financeiros as $lf) {
                $resultado[] = [
                    'tipo' => 'ficha_financeira',
                    'entidade' => $lf,
                    'data' => $lf->getDataVencimento(),
                    'documento' => $lf->getNumeroBoleto() ?? $lf->getNumeroRecibo(),
                    'pagador' => $lf->getInquilino()?->getNome() ?? '-',
                    'historico' => $lf->getHistorico() ?? $lf->getDescricao(),
                    'plano_conta' => $lf->getConta()?->getDescricao() ?? 'Aluguel',
                    'imovel' => $lf->getImovel()?->getId(),
                    'valor' => (float) $lf->getValorTotal(),
                    'status' => $lf->getSituacao(),
                ];
            }
        }

        // Ordenar por data
        usort($resultado, fn($a, $b) => $a['data'] <=> $b['data']);

        // Agrupar se solicitado
        if (!empty($filtros['agrupar_por']) && $filtros['agrupar_por'] !== 'nenhum') {
            return $this->agruparReceitas($resultado, $filtros['agrupar_por']);
        }

        return $resultado;
    }

    /**
     * Calcula totais das receitas
     */
    public function getTotalReceitas(array $filtros): array
    {
        $receitas = $this->getReceitas($filtros);

        $totalAberto = 0;
        $totalRecebido = 0;
        $totalGeral = 0;
        $quantidade = 0;

        // Verificar se está agrupado
        $primeiroItem = reset($receitas);
        if (is_array($primeiroItem) && isset($primeiroItem['itens'])) {
            foreach ($receitas as $grupo) {
                foreach ($grupo['itens'] as $item) {
                    $this->somarReceita($item, $totalAberto, $totalRecebido, $totalGeral);
                    $quantidade++;
                }
            }
        } else {
            foreach ($receitas as $item) {
                $this->somarReceita($item, $totalAberto, $totalRecebido, $totalGeral);
                $quantidade++;
            }
        }

        return [
            'quantidade' => $quantidade,
            'total_aberto' => round($totalAberto, 2),
            'total_recebido' => round($totalRecebido, 2),
            'total_geral' => round($totalGeral, 2),
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

        // Buscar despesas
        $filtrosDespesas = array_merge($filtros, ['status' => $filtros['status'] === 'efetivado' ? 'pago' : $filtros['status']]);
        $despesas = $this->getDespesas($filtrosDespesas);

        // Buscar receitas
        $filtrosReceitas = array_merge($filtros, [
            'status' => $filtros['status'] === 'efetivado' ? 'pago' : $filtros['status'],
            'origem' => 'todos'
        ]);
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

        // Processar despesas
        foreach ($despesas as $item) {
            $lancamento = $item instanceof Lancamentos ? $item : ($item['entidade'] ?? null);
            if (!$lancamento) continue;

            $resultado[] = [
                'data' => $this->getDataPorTipo($lancamento, $filtros['tipo_data'] ?? 'vencimento'),
                'tipo' => 'D',
                'historico' => $lancamento->getHistorico(),
                'plano_conta' => $lancamento->getPlanoConta()->getDescricao(),
                'valor_receita' => 0,
                'valor_despesa' => (float) $lancamento->getValor(),
            ];
        }

        // Processar receitas
        foreach ($receitas as $item) {
            $resultado[] = [
                'data' => is_array($item) ? $item['data'] : $item->getDataVencimento(),
                'tipo' => 'R',
                'historico' => is_array($item) ? $item['historico'] : $item->getHistorico(),
                'plano_conta' => is_array($item) ? $item['plano_conta'] : '',
                'valor_receita' => is_array($item) ? $item['valor'] : (float) $item->getValorTotal(),
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
     * Busca movimentos de conta bancária
     */
    public function getMovimentosContaBancaria(array $filtros): array
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('l')
            ->from(Lancamentos::class, 'l')
            ->leftJoin('l.contaBancaria', 'cb')
            ->where('l.contaBancaria IS NOT NULL')
            ->andWhere('l.status = :status')
            ->setParameter('status', 'pago');

        if (!empty($filtros['id_conta_bancaria'])) {
            $qb->andWhere('l.contaBancaria = :idConta')
                ->setParameter('idConta', $filtros['id_conta_bancaria']);
        }

        if (!empty($filtros['data_inicio'])) {
            $qb->andWhere('l.dataPagamento >= :dataInicio')
                ->setParameter('dataInicio', $filtros['data_inicio']);
        }

        if (!empty($filtros['data_fim'])) {
            $qb->andWhere('l.dataPagamento <= :dataFim')
                ->setParameter('dataFim', $filtros['data_fim']);
        }

        $qb->orderBy('l.dataPagamento', 'ASC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Calcula saldo inicial de uma conta em determinada data
     */
    public function getSaldoInicialConta(int $contaId, \DateTime $data): float
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('SUM(CASE WHEN l.tipo = :tipoReceber THEN l.valor ELSE -l.valor END) as saldo')
            ->from(Lancamentos::class, 'l')
            ->where('l.contaBancaria = :contaId')
            ->andWhere('l.status = :status')
            ->andWhere('l.dataPagamento < :data')
            ->setParameter('contaId', $contaId)
            ->setParameter('status', 'pago')
            ->setParameter('tipoReceber', Lancamentos::TIPO_RECEBER)
            ->setParameter('data', $data);

        $result = $qb->getQuery()->getSingleScalarResult();

        return (float) ($result ?? 0);
    }

    /**
     * Retorna resumo por conta bancária
     */
    public function getResumoContas(array $filtros): array
    {
        $movimentos = $this->getMovimentosContaBancaria($filtros);

        $contas = [];

        foreach ($movimentos as $lancamento) {
            $conta = $lancamento->getContaBancaria();
            $contaId = $conta->getId();

            if (!isset($contas[$contaId])) {
                $saldoInicial = $this->getSaldoInicialConta($contaId, $filtros['data_inicio'] ?? new \DateTime('first day of this month'));

                $contas[$contaId] = [
                    'conta' => $conta,
                    'saldo_inicial' => $saldoInicial,
                    'entradas' => 0,
                    'saidas' => 0,
                    'movimentos' => [],
                ];
            }

            $valor = (float) $lancamento->getValor();

            if ($lancamento->isReceber()) {
                $contas[$contaId]['entradas'] += $valor;
            } else {
                $contas[$contaId]['saidas'] += $valor;
            }

            if (!empty($filtros['mostrar_movimentos'])) {
                $contas[$contaId]['movimentos'][] = $lancamento;
            }
        }

        // Calcular saldo final
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
            default => throw new \InvalidArgumentException("Tipo de relatório inválido: $tipo"),
        };

        $html = $this->twig->render($template, [
            'dados' => $dados,
            'filtros' => $filtros,
            'data_emissao' => new \DateTime(),
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
