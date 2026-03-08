<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\AlmasaLancamento;
use App\Entity\AlmasaPlanoContas;
use App\Repository\AlmasaLancamentoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Twig\Environment;

class AlmasaRelatorioService
{
    private string $projectDir;

    public function __construct(
        private EntityManagerInterface $em,
        private AlmasaLancamentoRepository $almasaLancamentoRepo,
        private Environment $twig,
        ParameterBagInterface $params
    ) {
        $this->projectDir = $params->get('kernel.project_dir');
    }

    // =========================================================================
    // DESPESAS ALMASA
    // =========================================================================

    public function getDespesas(array $filtros): array
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('al', 'pc')
            ->from(AlmasaLancamento::class, 'al')
            ->leftJoin('al.almasaPlanoConta', 'pc')
            ->where('al.tipo = :tipo')
            ->setParameter('tipo', AlmasaLancamento::TIPO_DESPESA);

        $this->aplicarFiltrosData($qb, $filtros);
        $this->aplicarFiltrosComuns($qb, $filtros);

        $qb->orderBy('al.dataCompetencia', 'ASC');
        $lancamentos = $qb->getQuery()->getResult();

        $dados = [];
        foreach ($lancamentos as $al) {
            $dados[] = $this->normalizarLancamento($al);
        }

        if (!empty($filtros['agrupar_por']) && $filtros['agrupar_por'] !== 'nenhum') {
            return $this->agrupar($dados, $filtros['agrupar_por']);
        }

        return $dados;
    }

    public function getTotalDespesas(array $filtros): array
    {
        return $this->getTotaisPorTipo('despesa', $filtros);
    }

    // =========================================================================
    // RECEITAS ALMASA
    // =========================================================================

    public function getReceitas(array $filtros): array
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('al', 'pc')
            ->from(AlmasaLancamento::class, 'al')
            ->leftJoin('al.almasaPlanoConta', 'pc')
            ->where('al.tipo = :tipo')
            ->setParameter('tipo', AlmasaLancamento::TIPO_RECEITA);

        $this->aplicarFiltrosData($qb, $filtros);
        $this->aplicarFiltrosComuns($qb, $filtros);

        $qb->orderBy('al.dataCompetencia', 'ASC');
        $lancamentos = $qb->getQuery()->getResult();

        $dados = [];
        foreach ($lancamentos as $al) {
            $dados[] = $this->normalizarLancamento($al);
        }

        if (!empty($filtros['agrupar_por']) && $filtros['agrupar_por'] !== 'nenhum') {
            return $this->agrupar($dados, $filtros['agrupar_por']);
        }

        return $dados;
    }

    public function getTotalReceitas(array $filtros): array
    {
        return $this->getTotaisPorTipo('receita', $filtros);
    }

    // =========================================================================
    // COMPARATIVO DESPESAS x RECEITAS ALMASA
    // =========================================================================

    public function getDespesasReceitas(array $filtros): array
    {
        $visualizacao = $filtros['visualizacao'] ?? 'sintetico';

        $filtrosSemAgrupamento = $filtros;
        if ($visualizacao === 'analitico') {
            $filtrosSemAgrupamento['agrupar_por'] = null;
        }

        $despesas = $this->getDespesas($filtrosSemAgrupamento);
        $receitas = $this->getReceitas($filtrosSemAgrupamento);

        if ($visualizacao === 'sintetico') {
            return $this->gerarComparativoSintetico($despesas, $receitas, $filtros);
        }

        return $this->gerarComparativoAnalitico($despesas, $receitas);
    }

    public function getSaldoPeriodo(array $filtros): float
    {
        $totaisReceitas = $this->getTotalReceitas($filtros);
        $totaisDespesas = $this->getTotalDespesas($filtros);

        return round($totaisReceitas['total_geral'] - $totaisDespesas['total_geral'], 2);
    }

    // =========================================================================
    // DRE ALMASA (agregacao por plano de contas hierarquico)
    // =========================================================================

    public function getDre(array $filtros): array
    {
        $dataInicio = $filtros['data_inicio'] ?? new \DateTime('first day of this month');
        $dataFim = $filtros['data_fim'] ?? new \DateTime();
        $status = (!empty($filtros['status']) && $filtros['status'] !== 'todos') ? $filtros['status'] : null;

        $totais = $this->almasaLancamentoRepo->getTotaisPorTipoPeriodo($dataInicio, $dataFim, $status);
        $porConta = $this->almasaLancamentoRepo->getTotaisPorContaPeriodo($dataInicio, $dataFim, $status);
        $porSubgrupo = $this->almasaLancamentoRepo->getTotaisPorSubgrupoPeriodo($dataInicio, $dataFim, $status);

        return [
            'totais' => $totais,
            'por_conta' => $porConta,
            'por_subgrupo' => $porSubgrupo,
        ];
    }

    // =========================================================================
    // PDF
    // =========================================================================

    public function gerarPdf(string $tipo, array $dados, array $filtros): string
    {
        $template = match ($tipo) {
            'almasa_despesas' => 'relatorios/pdf/almasa_despesas.html.twig',
            'almasa_receitas' => 'relatorios/pdf/almasa_receitas.html.twig',
            'almasa_despesas_receitas' => 'relatorios/pdf/almasa_despesas_receitas.html.twig',
            default => throw new \InvalidArgumentException("Tipo de relatorio invalido: $tipo"),
        };

        $logoPath = $this->projectDir . '/public/images/almasa-logo.png';
        $logoDataUri = file_exists($logoPath)
            ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath))
            : '';

        $html = $this->twig->render($template, array_merge($dados, [
            'filtros' => $filtros,
            'data_emissao' => new \DateTime(),
            'logo_data_uri' => $logoDataUri,
        ]));

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);

        $orientation = $tipo === 'almasa_despesas_receitas' ? 'landscape' : 'portrait';
        $dompdf->setPaper('A4', $orientation);

        $dompdf->render();

        return $dompdf->output();
    }

    // =========================================================================
    // METODOS AUXILIARES
    // =========================================================================

    private function normalizarLancamento(AlmasaLancamento $al): array
    {
        $pc = $al->getAlmasaPlanoConta();
        $pcPai = $pc?->getPai();

        return [
            'id' => $al->getId(),
            'dataCompetencia' => $al->getDataCompetencia(),
            'dataVencimento' => $al->getDataVencimento(),
            'dataPagamento' => $al->getDataPagamento(),
            'descricao' => $al->getDescricao() ?? '-',
            'planoConta' => $pc ? $pc->getCodigo() . ' - ' . $pc->getDescricao() : '-',
            'planoContaCodigo' => $pc?->getCodigo() ?? '-',
            'planoContaDescricao' => $pc?->getDescricao() ?? '-',
            'planoContaGrupo' => $pcPai ? $pcPai->getDescricao() : ($pc?->getDescricao() ?? '-'),
            'valor' => $al->getValorFloat(),
            'status' => $al->getStatus(),
            'statusLabel' => $al->getStatusLabel(),
            'statusBadgeClass' => $al->getStatusBadgeClass(),
            'contaBancaria' => $al->getContaBancaria()?->getDescricao(),
            'observacao' => $al->getObservacao(),
            'tipo' => $al->getTipo(),
            '_planoContaId' => (string) ($pc?->getId() ?? '0'),
            '_planoContaGrupoId' => (string) ($pcPai?->getId() ?? $pc?->getId() ?? '0'),
            '_mes' => $al->getDataCompetencia()->format('Y-m'),
        ];
    }

    private function aplicarFiltrosData(\Doctrine\ORM\QueryBuilder $qb, array $filtros): void
    {
        $campoData = match ($filtros['tipo_data'] ?? 'competencia') {
            'vencimento' => 'al.dataVencimento',
            'pagamento' => 'al.dataPagamento',
            default => 'al.dataCompetencia',
        };

        if (!empty($filtros['data_inicio'])) {
            $qb->andWhere("$campoData >= :dataInicio")
                ->setParameter('dataInicio', $filtros['data_inicio']);
        }
        if (!empty($filtros['data_fim'])) {
            $qb->andWhere("$campoData <= :dataFim")
                ->setParameter('dataFim', $filtros['data_fim']);
        }
    }

    private function aplicarFiltrosComuns(\Doctrine\ORM\QueryBuilder $qb, array $filtros): void
    {
        if (!empty($filtros['status']) && $filtros['status'] !== 'todos') {
            $qb->andWhere('al.status = :status')
                ->setParameter('status', $filtros['status']);
        }

        if (!empty($filtros['id_plano_conta'])) {
            $qb->andWhere('al.almasaPlanoConta = :idPlanoConta')
                ->setParameter('idPlanoConta', $filtros['id_plano_conta']);
        }
    }

    private function getTotaisPorTipo(string $tipo, array $filtros): array
    {
        $conn = $this->em->getConnection();

        $where = ['tipo = :tipo'];
        $params = ['tipo' => $tipo];

        $campoData = match ($filtros['tipo_data'] ?? 'competencia') {
            'vencimento' => 'data_vencimento',
            'pagamento' => 'data_pagamento',
            default => 'data_competencia',
        };

        if (!empty($filtros['data_inicio'])) {
            $where[] = "$campoData >= :data_inicio";
            $params['data_inicio'] = $filtros['data_inicio'] instanceof \DateTimeInterface
                ? $filtros['data_inicio']->format('Y-m-d') : $filtros['data_inicio'];
        }
        if (!empty($filtros['data_fim'])) {
            $where[] = "$campoData <= :data_fim";
            $params['data_fim'] = $filtros['data_fim'] instanceof \DateTimeInterface
                ? $filtros['data_fim']->format('Y-m-d') : $filtros['data_fim'];
        }
        if (!empty($filtros['status']) && $filtros['status'] !== 'todos') {
            $where[] = 'status = :status';
            $params['status'] = $filtros['status'];
        }
        if (!empty($filtros['id_plano_conta'])) {
            $where[] = 'id_almasa_plano_conta = :id_plano_conta';
            $params['id_plano_conta'] = (int) $filtros['id_plano_conta'];
        }

        $whereClause = implode(' AND ', $where);
        $sql = "SELECT COUNT(*) as quantidade,
                       COALESCE(SUM(valor::numeric), 0) as total_geral,
                       COALESCE(SUM(CASE WHEN status = 'pago' THEN valor::numeric ELSE 0 END), 0) as total_pago,
                       COALESCE(SUM(CASE WHEN status != 'pago' THEN valor::numeric ELSE 0 END), 0) as total_aberto
                FROM almasa_lancamentos WHERE {$whereClause}";

        $result = $conn->executeQuery($sql, $params)->fetchAssociative();

        return [
            'quantidade' => (int) ($result['quantidade'] ?? 0),
            'total_aberto' => round((float) ($result['total_aberto'] ?? 0), 2),
            'total_pago' => round((float) ($result['total_pago'] ?? 0), 2),
            'total_geral' => round((float) ($result['total_geral'] ?? 0), 2),
        ];
    }

    private function agrupar(array $dados, string $criterio): array
    {
        $grupos = [];

        foreach ($dados as $item) {
            [$chave, $nome] = match ($criterio) {
                'plano_conta' => [$item['_planoContaId'], $item['planoConta']],
                'grupo' => [$item['_planoContaGrupoId'], $item['planoContaGrupo']],
                'mes' => [$item['_mes'], \DateTime::createFromFormat('Y-m', $item['_mes'])->format('m/Y')],
                default => ['0', 'Todos'],
            };

            if (!isset($grupos[$chave])) {
                $grupos[$chave] = ['nome' => $nome, 'itens' => [], 'total' => 0];
            }

            $grupos[$chave]['itens'][] = $item;
            $grupos[$chave]['total'] += $item['valor'];
        }

        return $grupos;
    }

    private function gerarComparativoSintetico(array $despesas, array $receitas, array $filtros): array
    {
        $agruparPor = $filtros['agrupar_por'] ?? 'plano_conta';
        $grupos = [];

        foreach ($despesas as $item) {
            $chave = match ($agruparPor) {
                'plano_conta' => $item['_planoContaId'],
                'grupo' => $item['_planoContaGrupoId'],
                'mes' => $item['_mes'],
                default => '0',
            };
            $nome = match ($agruparPor) {
                'plano_conta' => $item['planoConta'],
                'grupo' => $item['planoContaGrupo'],
                'mes' => \DateTime::createFromFormat('Y-m', $item['_mes'])->format('m/Y'),
                default => 'Todos',
            };

            if (!isset($grupos[$chave])) {
                $grupos[$chave] = ['nome' => $nome, 'receitas' => 0, 'despesas' => 0];
            }
            $grupos[$chave]['despesas'] += $item['valor'];
        }

        foreach ($receitas as $item) {
            $chave = match ($agruparPor) {
                'plano_conta' => $item['_planoContaId'],
                'grupo' => $item['_planoContaGrupoId'],
                'mes' => $item['_mes'],
                default => '0',
            };
            $nome = match ($agruparPor) {
                'plano_conta' => $item['planoConta'],
                'grupo' => $item['planoContaGrupo'],
                'mes' => \DateTime::createFromFormat('Y-m', $item['_mes'])->format('m/Y'),
                default => 'Todos',
            };

            if (!isset($grupos[$chave])) {
                $grupos[$chave] = ['nome' => $nome, 'receitas' => 0, 'despesas' => 0];
            }
            $grupos[$chave]['receitas'] += $item['valor'];
        }

        $totalReceitas = array_sum(array_column($grupos, 'receitas'));
        $totalDespesas = array_sum(array_column($grupos, 'despesas'));

        foreach ($grupos as &$grupo) {
            $grupo['saldo'] = $grupo['receitas'] - $grupo['despesas'];
            $grupo['percentual_receitas'] = $totalReceitas > 0 ? round($grupo['receitas'] / $totalReceitas * 100, 2) : 0;
            $grupo['percentual_despesas'] = $totalDespesas > 0 ? round($grupo['despesas'] / $totalDespesas * 100, 2) : 0;
        }

        return $grupos;
    }

    private function gerarComparativoAnalitico(array $despesas, array $receitas): array
    {
        $resultado = [];

        foreach ($despesas as $item) {
            $resultado[] = [
                'data' => $item['dataCompetencia'],
                'tipo' => 'D',
                'descricao' => $item['descricao'],
                'planoConta' => $item['planoConta'],
                'valor_receita' => 0,
                'valor_despesa' => $item['valor'],
            ];
        }

        foreach ($receitas as $item) {
            $resultado[] = [
                'data' => $item['dataCompetencia'],
                'tipo' => 'R',
                'descricao' => $item['descricao'],
                'planoConta' => $item['planoConta'],
                'valor_receita' => $item['valor'],
                'valor_despesa' => 0,
            ];
        }

        usort($resultado, fn($a, $b) => $a['data'] <=> $b['data']);

        $saldoAcumulado = 0;
        foreach ($resultado as &$item) {
            $saldoAcumulado += $item['valor_receita'] - $item['valor_despesa'];
            $item['saldo_acumulado'] = round($saldoAcumulado, 2);
        }

        return $resultado;
    }
}
