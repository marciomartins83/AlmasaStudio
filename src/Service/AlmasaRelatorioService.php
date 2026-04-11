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
        // Despesas Almasa = lancamentos onde a conta DEBITADA é tipo 'despesa'
        $dados = $this->buscarLancamentosPorTipoConta('despesa', $filtros);

        if (!empty($filtros['agrupar_por']) && $filtros['agrupar_por'] !== 'nenhum') {
            return $this->agrupar($dados, $filtros['agrupar_por']);
        }

        return $dados;
    }

    public function getTotalDespesas(array $filtros): array
    {
        return $this->getTotaisPorTipoConta('despesa', $filtros);
    }

    // =========================================================================
    // RECEITAS ALMASA
    // =========================================================================

    public function getReceitas(array $filtros): array
    {
        // Receitas Almasa = lancamentos onde a conta CREDITADA é tipo 'receita'
        $dados = $this->buscarLancamentosPorTipoConta('receita', $filtros);

        if (!empty($filtros['agrupar_por']) && $filtros['agrupar_por'] !== 'nenhum') {
            return $this->agrupar($dados, $filtros['agrupar_por']);
        }

        return $dados;
    }

    public function getTotalReceitas(array $filtros): array
    {
        return $this->getTotaisPorTipoConta('receita', $filtros);
    }

    // =========================================================================
    // COMPARATIVO DESPESAS x RECEITAS ALMASA
    // =========================================================================

    public function getDespesasReceitas(array $filtros): array
    {
        $visualizacao = $filtros['visualizacao'] ?? 'sintetico';

        // SEMPRE buscar dados flat (sem agrupamento previo) — o agrupamento
        // e feito internamente por gerarComparativoSintetico quando necessario
        $filtrosFlat = $filtros;
        $filtrosFlat['agrupar_por'] = null;

        $despesas = $this->getDespesas($filtrosFlat);
        $receitas = $this->getReceitas($filtrosFlat);

        if ($visualizacao === 'sintetico') {
            return $this->gerarComparativoSintetico($despesas, $receitas, $filtros);
        }

        // Analitico: sempre flat, sem agrupamento
        return $this->gerarComparativoAnalitico($despesas, $receitas);
    }

    public function getSaldoPeriodo(array $filtros): float
    {
        $totaisReceitas = $this->getTotalReceitas($filtros);
        $totaisDespesas = $this->getTotalDespesas($filtros);

        return round($totaisReceitas['total_geral'] - $totaisDespesas['total_geral'], 2);
    }

    // =========================================================================
    // PLANO DE CONTAS ALMASA (relatorio)
    // =========================================================================

    public function getPlanoContas(array $filtros): array
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('pc', 'pai')
            ->from(AlmasaPlanoContas::class, 'pc')
            ->leftJoin('pc.pai', 'pai')
            ->orderBy('pc.codigo', 'ASC');

        if (!empty($filtros['tipo']) && $filtros['tipo'] !== 'todos') {
            $qb->andWhere('pc.tipo = :tipo')
                ->setParameter('tipo', $filtros['tipo']);
        }

        if (!empty($filtros['nivel']) && $filtros['nivel'] !== 'todos') {
            $qb->andWhere('pc.nivel = :nivel')
                ->setParameter('nivel', (int) $filtros['nivel']);
        }

        if (!empty($filtros['ativo']) && $filtros['ativo'] !== 'todos') {
            $ativo = $filtros['ativo'] === 'ativos';
            $qb->andWhere('pc.ativo = :ativo')
                ->setParameter('ativo', $ativo);
        }

        if (!empty($filtros['aceita_lancamentos']) && $filtros['aceita_lancamentos'] !== 'todos') {
            $aceita = $filtros['aceita_lancamentos'] === 'sim';
            $qb->andWhere('pc.aceitaLancamentos = :aceita')
                ->setParameter('aceita', $aceita);
        }

        return $qb->getQuery()->getResult();
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
            'almasa_plano_contas' => 'relatorios/pdf/almasa_plano_contas.html.twig',
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
    // SALDO ANTERIOR / SALDO ATUAL
    // =========================================================================

    /**
     * Calcula saldo anterior: soma de (receitas - despesas) antes da data_inicio.
     * Se filtro id_plano_conta, filtra pela conta especifica.
     */
    public function calcularSaldoAnterior(array $filtros): float
    {
        $conn = $this->em->getConnection();

        if (empty($filtros['data_inicio'])) {
            return 0.0;
        }

        $dataInicio = $filtros['data_inicio'] instanceof \DateTimeInterface
            ? $filtros['data_inicio']->format('Y-m-d') : $filtros['data_inicio'];

        $tipoDataSA = $filtros['tipo_data'] ?? 'competencia';
        $usaCompSA = ($tipoDataSA === 'competencia');
        $campoData = match ($tipoDataSA) {
            'vencimento' => 'data_vencimento',
            'pagamento' => 'data_pagamento',
            default => 'competencia',
        };

        $diSA = $usaCompSA ? substr($dataInicio, 0, 7) : $dataInicio;
        $where = ["$campoData < :data_inicio"];
        $params = ['data_inicio' => $diSA];

        if (!empty($filtros['status']) && $filtros['status'] !== 'todos') {
            $where[] = 'status = :status';
            $params['status'] = $filtros['status'];
        }
        if (!empty($filtros['id_plano_conta'])) {
            $where[] = '(id_plano_conta_debito = :id_pc OR id_plano_conta_credito = :id_pc)';
            $params['id_pc'] = (int) $filtros['id_plano_conta'];
        }

        $whereClause = implode(' AND ', $where);
        $sql = "SELECT
                    COALESCE(SUM(CASE WHEN tipo = 'receber' THEN valor::numeric ELSE 0 END), 0)
                    - COALESCE(SUM(CASE WHEN tipo = 'pagar' THEN valor::numeric ELSE 0 END), 0)
                    AS saldo
                FROM lancamentos WHERE {$whereClause}";

        $result = $conn->executeQuery($sql, $params)->fetchAssociative();

        return round((float) ($result['saldo'] ?? 0), 2);
    }

    // =========================================================================
    // BUSCA POR TIPO DE CONTA (receita/despesa da Almasa)
    // =========================================================================

    /**
     * Busca lancamentos onde a conta contábil envolvida é do tipo especificado.
     * - 'receita': conta CREDITADA é tipo 'receita' (ex: Taxa de Administração)
     * - 'despesa': conta DEBITADA é tipo 'despesa' (ex: Contas de Água, Luz, etc.)
     */
    private function buscarLancamentosPorTipoConta(string $tipoConta, array $filtros): array
    {
        $conn = $this->em->getConnection();

        // Receita Almasa = crédito em conta tipo 'receita'
        // Despesa Almasa = débito em conta tipo 'despesa'
        $joinConta = $tipoConta === 'receita'
            ? "JOIN almasa_plano_contas pc_filtro ON pc_filtro.id = l.id_plano_conta_credito AND pc_filtro.tipo = 'receita'"
            : "JOIN almasa_plano_contas pc_filtro ON pc_filtro.id = l.id_plano_conta_debito AND pc_filtro.tipo = 'despesa'";

        $where = [];
        $params = [];

        $tipoData = $filtros['tipo_data'] ?? 'competencia';
        $usaCompetencia = ($tipoData === 'competencia');
        $campoData = match ($tipoData) {
            'vencimento' => 'l.data_vencimento',
            'pagamento' => 'l.data_pagamento',
            default => 'l.competencia',
        };

        if (!empty($filtros['data_inicio'])) {
            $di = $filtros['data_inicio'] instanceof \DateTimeInterface
                ? $filtros['data_inicio']->format('Y-m-d') : $filtros['data_inicio'];
            $where[] = "$campoData >= :data_inicio";
            $params['data_inicio'] = $usaCompetencia ? substr($di, 0, 7) : $di;
        }
        if (!empty($filtros['data_fim'])) {
            $df = $filtros['data_fim'] instanceof \DateTimeInterface
                ? $filtros['data_fim']->format('Y-m-d') : $filtros['data_fim'];
            $where[] = "$campoData <= :data_fim";
            $params['data_fim'] = $usaCompetencia ? substr($df, 0, 7) : $df;
        }
        if (!empty($filtros['status']) && $filtros['status'] !== 'todos') {
            $where[] = 'l.status = :status';
            $params['status'] = $filtros['status'];
        }
        if (!empty($filtros['id_plano_conta'])) {
            $where[] = 'pc_filtro.id = :id_pc';
            $params['id_pc'] = (int) $filtros['id_plano_conta'];
        }

        $whereClause = $where ? 'AND ' . implode(' AND ', $where) : '';

        $sql = "SELECT l.id, l.data_vencimento, l.data_pagamento, l.competencia,
                       l.historico, l.valor, l.status, l.tipo,
                       pc_filtro.codigo AS pc_codigo, pc_filtro.descricao AS pc_descricao,
                       pcpai.descricao AS pc_grupo
                FROM lancamentos l
                {$joinConta}
                LEFT JOIN almasa_plano_contas pcpai ON pcpai.id = pc_filtro.id_pai
                WHERE 1=1 {$whereClause}
                ORDER BY l.data_vencimento ASC";

        $rows = $conn->fetchAllAssociative($sql, $params);

        $dados = [];
        foreach ($rows as $r) {
            $dados[] = [
                'id' => $r['id'],
                'dataCompetencia' => $r['competencia'] ? new \DateTime($r['competencia'] . '-01') : null,
                'dataVencimento' => $r['data_vencimento'] ? new \DateTime($r['data_vencimento']) : null,
                'dataPagamento' => $r['data_pagamento'] ? new \DateTime($r['data_pagamento']) : null,
                'descricao' => $r['historico'] ?? '-',
                'planoConta' => ($r['pc_codigo'] ?? '-') . ' - ' . ($r['pc_descricao'] ?? '-'),
                'planoContaCodigo' => $r['pc_codigo'] ?? '-',
                'planoContaDescricao' => $r['pc_descricao'] ?? '-',
                'planoContaGrupo' => $r['pc_grupo'] ?? ($r['pc_descricao'] ?? '-'),
                'valor' => round((float) ($r['valor'] ?? 0), 2),
                'status' => $r['status'],
                'statusLabel' => ucfirst($r['status'] ?? ''),
                'statusBadgeClass' => match ($r['status'] ?? '') {
                    'pago' => 'success',
                    'aberto' => 'warning',
                    'cancelado' => 'danger',
                    default => 'secondary',
                },
                'tipo' => $r['tipo'],
                '_planoContaId' => $r['pc_codigo'] ?? '0',
                '_planoContaGrupoId' => $r['pc_grupo'] ?? ($r['pc_codigo'] ?? '0'),
                '_mes' => $r['competencia'] ?? ($r['data_vencimento'] ? substr($r['data_vencimento'], 0, 7) : ''),
            ];
        }

        return $dados;
    }

    /**
     * Totais por tipo de conta (receita/despesa da Almasa).
     */
    private function getTotaisPorTipoConta(string $tipoConta, array $filtros): array
    {
        $conn = $this->em->getConnection();

        $joinConta = $tipoConta === 'receita'
            ? "JOIN almasa_plano_contas pc ON pc.id = l.id_plano_conta_credito AND pc.tipo = 'receita'"
            : "JOIN almasa_plano_contas pc ON pc.id = l.id_plano_conta_debito AND pc.tipo = 'despesa'";

        $where = [];
        $params = [];

        $tipoData = $filtros['tipo_data'] ?? 'competencia';
        $usaComp = ($tipoData === 'competencia');
        $campoData = match ($tipoData) {
            'vencimento' => 'l.data_vencimento',
            'pagamento' => 'l.data_pagamento',
            default => 'l.competencia',
        };

        if (!empty($filtros['data_inicio'])) {
            $di = $filtros['data_inicio'] instanceof \DateTimeInterface
                ? $filtros['data_inicio']->format('Y-m-d') : $filtros['data_inicio'];
            $where[] = "$campoData >= :data_inicio";
            $params['data_inicio'] = $usaComp ? substr($di, 0, 7) : $di;
        }
        if (!empty($filtros['data_fim'])) {
            $df = $filtros['data_fim'] instanceof \DateTimeInterface
                ? $filtros['data_fim']->format('Y-m-d') : $filtros['data_fim'];
            $where[] = "$campoData <= :data_fim";
            $params['data_fim'] = $usaComp ? substr($df, 0, 7) : $df;
        }
        if (!empty($filtros['status']) && $filtros['status'] !== 'todos') {
            $where[] = 'l.status = :status';
            $params['status'] = $filtros['status'];
        }
        if (!empty($filtros['id_plano_conta'])) {
            $where[] = 'pc.id = :id_pc';
            $params['id_pc'] = (int) $filtros['id_plano_conta'];
        }

        $whereClause = $where ? 'AND ' . implode(' AND ', $where) : '';

        $sql = "SELECT COUNT(*) as quantidade,
                       COALESCE(SUM(l.valor::numeric), 0) as total_geral,
                       COALESCE(SUM(CASE WHEN l.status = 'pago' THEN l.valor::numeric ELSE 0 END), 0) as total_pago,
                       COALESCE(SUM(CASE WHEN l.status != 'pago' THEN l.valor::numeric ELSE 0 END), 0) as total_aberto
                FROM lancamentos l
                {$joinConta}
                WHERE 1=1 {$whereClause}";

        $result = $conn->executeQuery($sql, $params)->fetchAssociative();

        return [
            'quantidade' => (int) ($result['quantidade'] ?? 0),
            'total_aberto' => round((float) ($result['total_aberto'] ?? 0), 2),
            'total_pago' => round((float) ($result['total_pago'] ?? 0), 2),
            'total_geral' => round((float) ($result['total_geral'] ?? 0), 2),
        ];
    }

    // =========================================================================
    // METODOS AUXILIARES (legados — mantidos para compatibilidade)
    // =========================================================================

    private function buscarLancamentos(string $tipo, array $filtros): array
    {
        $conn = $this->em->getConnection();

        $where = ['l.tipo = :tipo'];
        $params = ['tipo' => $tipo];

        $tipoData = $filtros['tipo_data'] ?? 'competencia';
        $usaCompetencia = ($tipoData === 'competencia');
        $campoData = match ($tipoData) {
            'vencimento' => 'l.data_vencimento',
            'pagamento' => 'l.data_pagamento',
            default => 'l.competencia',
        };

        if (!empty($filtros['data_inicio'])) {
            $di = $filtros['data_inicio'] instanceof \DateTimeInterface
                ? $filtros['data_inicio']->format('Y-m-d') : $filtros['data_inicio'];
            if ($usaCompetencia) {
                // Competencia é YYYY-MM: comparar pelo mês da data informada
                $where[] = "$campoData >= :data_inicio";
                $params['data_inicio'] = substr($di, 0, 7); // '2026-04-10' → '2026-04'
            } else {
                $where[] = "$campoData >= :data_inicio";
                $params['data_inicio'] = $di;
            }
        }
        if (!empty($filtros['data_fim'])) {
            $df = $filtros['data_fim'] instanceof \DateTimeInterface
                ? $filtros['data_fim']->format('Y-m-d') : $filtros['data_fim'];
            if ($usaCompetencia) {
                $where[] = "$campoData <= :data_fim";
                $params['data_fim'] = substr($df, 0, 7);
            } else {
                $where[] = "$campoData <= :data_fim";
                $params['data_fim'] = $df;
            }
        }
        if (!empty($filtros['status']) && $filtros['status'] !== 'todos') {
            $where[] = 'l.status = :status';
            $params['status'] = $filtros['status'];
        }
        if (!empty($filtros['id_plano_conta'])) {
            $where[] = '(l.id_plano_conta_debito = :id_pc OR l.id_plano_conta_credito = :id_pc)';
            $params['id_pc'] = (int) $filtros['id_plano_conta'];
        }

        $whereClause = implode(' AND ', $where);
        $sql = "SELECT l.id, l.data_vencimento, l.data_pagamento, l.competencia,
                       l.historico, l.valor, l.status, l.tipo,
                       pcd.codigo AS pc_deb_codigo, pcd.descricao AS pc_deb_descricao,
                       pcc.codigo AS pc_cred_codigo, pcc.descricao AS pc_cred_descricao,
                       pcpai.descricao AS pc_grupo
                FROM lancamentos l
                LEFT JOIN almasa_plano_contas pcd ON pcd.id = l.id_plano_conta_debito
                LEFT JOIN almasa_plano_contas pcc ON pcc.id = l.id_plano_conta_credito
                LEFT JOIN almasa_plano_contas pcpai ON pcpai.id = COALESCE(pcd.id_pai, pcc.id_pai)
                WHERE {$whereClause}
                ORDER BY l.data_vencimento ASC";

        $rows = $conn->fetchAllAssociative($sql, $params);

        $dados = [];
        foreach ($rows as $r) {
            $pcCodigo = $r['pc_deb_codigo'] ?? $r['pc_cred_codigo'] ?? '-';
            $pcDescricao = $r['pc_deb_descricao'] ?? $r['pc_cred_descricao'] ?? '-';
            $pcId = $r['pc_deb_codigo'] ? ($r['pc_deb_codigo']) : ($r['pc_cred_codigo'] ?? '0');

            $dados[] = [
                'id' => $r['id'],
                'dataCompetencia' => $r['competencia'] ? new \DateTime($r['competencia'] . '-01') : null,
                'dataVencimento' => $r['data_vencimento'] ? new \DateTime($r['data_vencimento']) : null,
                'dataPagamento' => $r['data_pagamento'] ? new \DateTime($r['data_pagamento']) : null,
                'descricao' => $r['historico'] ?? '-',
                'planoConta' => $pcCodigo . ' - ' . $pcDescricao,
                'planoContaCodigo' => $pcCodigo,
                'planoContaDescricao' => $pcDescricao,
                'planoContaGrupo' => $r['pc_grupo'] ?? $pcDescricao,
                'valor' => round((float) ($r['valor'] ?? 0), 2),
                'status' => $r['status'],
                'statusLabel' => ucfirst($r['status'] ?? ''),
                'statusBadgeClass' => match ($r['status'] ?? '') {
                    'pago' => 'success',
                    'aberto' => 'warning',
                    'cancelado' => 'danger',
                    default => 'secondary',
                },
                'contaBancaria' => null,
                'observacao' => null,
                'tipo' => $r['tipo'],
                '_planoContaId' => $pcId,
                '_planoContaGrupoId' => $r['pc_grupo'] ?? $pcId,
                '_mes' => $r['competencia'] ?? ($r['data_vencimento'] ? substr($r['data_vencimento'], 0, 7) : ''),
            ];
        }

        return $dados;
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

        // Mapeia tipo do relatório para tipo da tabela lancamentos
        $tipoLancamento = $tipo === 'despesa' ? 'pagar' : 'receber';

        $where = ['tipo = :tipo'];
        $params = ['tipo' => $tipoLancamento];

        $tipoData2 = $filtros['tipo_data'] ?? 'competencia';
        $usaComp2 = ($tipoData2 === 'competencia');
        $campoData = match ($tipoData2) {
            'vencimento' => 'data_vencimento',
            'pagamento' => 'data_pagamento',
            default => 'competencia',
        };

        if (!empty($filtros['data_inicio'])) {
            $di = $filtros['data_inicio'] instanceof \DateTimeInterface
                ? $filtros['data_inicio']->format('Y-m-d') : $filtros['data_inicio'];
            $where[] = "$campoData >= :data_inicio";
            $params['data_inicio'] = $usaComp2 ? substr($di, 0, 7) : $di;
        }
        if (!empty($filtros['data_fim'])) {
            $df = $filtros['data_fim'] instanceof \DateTimeInterface
                ? $filtros['data_fim']->format('Y-m-d') : $filtros['data_fim'];
            $where[] = "$campoData <= :data_fim";
            $params['data_fim'] = $usaComp2 ? substr($df, 0, 7) : $df;
        }
        if (!empty($filtros['status']) && $filtros['status'] !== 'todos') {
            $where[] = 'status = :status';
            $params['status'] = $filtros['status'];
        }
        if (!empty($filtros['id_plano_conta'])) {
            $where[] = '(id_plano_conta_debito = :id_plano_conta OR id_plano_conta_credito = :id_plano_conta)';
            $params['id_plano_conta'] = (int) $filtros['id_plano_conta'];
        }

        $whereClause = implode(' AND ', $where);
        $sql = "SELECT COUNT(*) as quantidade,
                       COALESCE(SUM(valor::numeric), 0) as total_geral,
                       COALESCE(SUM(CASE WHEN status = 'pago' THEN valor::numeric ELSE 0 END), 0) as total_pago,
                       COALESCE(SUM(CASE WHEN status != 'pago' THEN valor::numeric ELSE 0 END), 0) as total_aberto
                FROM lancamentos WHERE {$whereClause}";

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
                'plano_conta' => [$item['_planoContaId'] ?? '0', $item['planoConta'] ?? '-'],
                'grupo' => [$item['_planoContaGrupoId'] ?? '0', $item['planoContaGrupo'] ?? '-'],
                'mes' => $this->extrairChaveMes($item),
                default => ['0', 'Todos'],
            };

            if (!isset($grupos[$chave])) {
                $grupos[$chave] = ['nome' => $nome, 'itens' => [], 'total' => 0];
            }

            $grupos[$chave]['itens'][] = $item;
            $grupos[$chave]['total'] += $item['valor'] ?? 0;
        }

        return $grupos;
    }

    private function extrairChaveMes(array $item): array
    {
        $mes = $item['_mes'] ?? '';
        if ($mes && preg_match('/^\d{4}-\d{2}$/', $mes)) {
            return [$mes, \DateTime::createFromFormat('Y-m', $mes)->format('m/Y')];
        }
        return ['sem_data', 'Sem data'];
    }

    private function gerarComparativoSintetico(array $despesas, array $receitas, array $filtros): array
    {
        $agruparPor = $filtros['agrupar_por'] ?? 'plano_conta';
        $grupos = [];

        foreach ($despesas as $item) {
            [$chave, $nome] = match ($agruparPor) {
                'plano_conta' => [$item['_planoContaId'] ?? '0', $item['planoConta'] ?? '-'],
                'grupo' => [$item['_planoContaGrupoId'] ?? '0', $item['planoContaGrupo'] ?? '-'],
                'mes' => $this->extrairChaveMes($item),
                default => ['0', 'Todos'],
            };

            if (!isset($grupos[$chave])) {
                $grupos[$chave] = ['nome' => $nome, 'receitas' => 0, 'despesas' => 0];
            }
            $grupos[$chave]['despesas'] += $item['valor'] ?? 0;
        }

        foreach ($receitas as $item) {
            [$chave, $nome] = match ($agruparPor) {
                'plano_conta' => [$item['_planoContaId'] ?? '0', $item['planoConta'] ?? '-'],
                'grupo' => [$item['_planoContaGrupoId'] ?? '0', $item['planoContaGrupo'] ?? '-'],
                'mes' => $this->extrairChaveMes($item),
                default => ['0', 'Todos'],
            };

            if (!isset($grupos[$chave])) {
                $grupos[$chave] = ['nome' => $nome, 'receitas' => 0, 'despesas' => 0];
            }
            $grupos[$chave]['receitas'] += $item['valor'] ?? 0;
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
