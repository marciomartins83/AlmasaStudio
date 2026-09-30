<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\ContratosCobrancas;
use App\Entity\ContratosItensCobranca;
use App\Repository\ContratosCobrancasRepository;
use App\Repository\LancamentosFinanceirosRepository;
use App\Service\CobrancaContratoService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller para gestão de cobranças de contratos.
 *
 * Funcionalidades:
 * - Listagem de cobranças pendentes para envio manual
 * - Detalhes de uma cobrança
 * - Envio manual individual e em lote
 * - Preview antes do envio
 */
#[Route('/cobranca')]
#[IsGranted('ROLE_USER')]
class CobrancaController extends AbstractController
{
    public function __construct(
        private CobrancaContratoService $cobrancaService,
        private ContratosCobrancasRepository $cobrancasRepo,
        private LancamentosFinanceirosRepository $lancamentosFinanceirosRepo,
        private \App\Service\EmailService $emailService,
        private \App\Repository\ConfiguracoesApiBancoRepository $configApiRepo
    ) {}

    /**
     * Listagem de cobranças pendentes para envio manual.
     */
    #[Route('/', name: 'app_cobranca_index', methods: ['GET'])]
    #[Route('/pendentes', name: 'app_cobranca_pendentes', methods: ['GET'])]
    public function pendentes(Request $request): Response
    {
        // Filtros
        $dataVencimentoStr = $request->query->get('data_vencimento');
        $vencimentoInicioStr = $request->query->get('vencimento_inicio');
        $vencimentoFimStr = $request->query->get('vencimento_fim');
        $mostrarAutomaticos = $request->query->getBoolean('mostrar_automaticos', false);
        $status = $request->query->all('status');

        // Paginação
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;

        // Montar filtros
        $filtros = [];
        $vencimentoInicio = null;
        $vencimentoFim = null;

        // Filtro por período de vencimento (ex: dia 1-10/11-20/21-31 do mês, como no
        // sistema legado) tem prioridade sobre o filtro de data pontual, se informado.
        if ($vencimentoInicioStr || $vencimentoFimStr) {
            try {
                if ($vencimentoInicioStr) {
                    $vencimentoInicio = new \DateTime($vencimentoInicioStr);
                    $filtros['vencimento_inicio'] = $vencimentoInicio;
                }
                if ($vencimentoFimStr) {
                    $vencimentoFim = new \DateTime($vencimentoFimStr);
                    $filtros['vencimento_fim'] = $vencimentoFim;
                }
            } catch (\Exception $e) {
                // Ignora datas inválidas
            }
        } elseif ($dataVencimentoStr) {
            try {
                $dataVencimento = new \DateTime($dataVencimentoStr);
                $filtros['data_vencimento'] = $dataVencimento;
            } catch (\Exception $e) {
                // Ignora data inválida
            }
        } else {
            // Default: hoje
            $filtros['data_vencimento'] = new \DateTime();
        }

        if (!empty($status)) {
            $filtros['status'] = $status;
        } else {
            // Default: pendentes e boleto gerado
            $filtros['status'] = [
                ContratosCobrancas::STATUS_PENDENTE,
                ContratosCobrancas::STATUS_BOLETO_GERADO
            ];
        }

        if (!$mostrarAutomaticos) {
            $filtros['excluir_automaticos'] = true;
        }

        // Buscar cobranças
        $resultado = $this->cobrancaService->listarCobrancas($filtros, $limit, $offset);
        $cobrancas = $resultado['cobrancas'];
        $total = $resultado['total'];

        // Estatísticas para o dia/período (usa a data de referência disponível)
        $dataReferencia = $filtros['data_vencimento'] ?? $vencimentoInicio ?? null;
        $estatisticas = $this->cobrancaService->getEstatisticas($dataReferencia);

        // Contagem por tipo de envio
        $contagemTipoEnvio = $this->cobrancasRepo->contarPorTipoEnvio(
            $dataReferencia ?? new \DateTime()
        );

        // Status disponíveis para filtro
        $statusOptions = ContratosCobrancas::getStatusDisponiveis();

        // Aviso de inadimplência (não bloqueia envio manual — só avisa visualmente)
        $inadimplentes = [];
        foreach ($cobrancas as $cobranca) {
            $locatario = $cobranca->getContrato()->getPessoaLocatario();
            if ($locatario && $this->lancamentosFinanceirosRepo->possuiLancamentoEmAbertoAntesDe($locatario->getIdpessoa(), $cobranca->getDataVencimento())) {
                $inadimplentes[$cobranca->getId()] = true;
            }
        }

        return $this->render('cobranca/pendentes.html.twig', [
            'cobrancas' => $cobrancas,
            'total' => $total,
            'page' => $page,
            'totalPages' => ceil($total / $limit),
            'estatisticas' => $estatisticas,
            'contagem_tipo_envio' => $contagemTipoEnvio,
            'statusOptions' => $statusOptions,
            'filtros' => $filtros,
            'mostrarAutomaticos' => $mostrarAutomaticos,
            'dataVencimento' => $filtros['data_vencimento'] ?? null,
            'vencimentoInicio' => $vencimentoInicio,
            'vencimentoFim' => $vencimentoFim,
            'inadimplentes' => $inadimplentes,
            'queryParams' => array_filter($request->query->all(), fn($v) => !is_array($v)),
            'configsApi' => $this->configApiRepo->findBy(['ativo' => true], ['id' => 'ASC']),
            'tiposItem' => ContratosItensCobranca::getTiposDisponiveis(),
        ]);
    }

    /**
     * Detalhes de uma cobrança.
     */
    #[Route('/{id}', name: 'app_cobranca_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id): Response
    {
        $cobranca = $this->cobrancasRepo->find($id);

        if (!$cobranca) {
            throw $this->createNotFoundException('Cobrança não encontrada');
        }

        $historicoEmails = $this->emailService->getHistoricoByReferencia('COBRANCA', $id);

        return $this->render('cobranca/show.html.twig', [
            'cobranca' => $cobranca,
            'contrato' => $cobranca->getContrato(),
            'boleto' => $cobranca->getBoleto(),
            'historicoEmails' => $historicoEmails,
        ]);
    }

    /**
     * Envia cobrança individual (AJAX).
     */
    #[Route('/{id}/enviar', name: 'app_cobranca_enviar', methods: ['POST'])]
    public function enviar(Request $request, int $id): JsonResponse
    {
        if (!$this->isCsrfTokenValid('ajax_global', $request->headers->get('X-CSRF-Token'))) {
            return new JsonResponse(['success' => false, 'message' => 'Token CSRF inválido'], 403);
        }

        $cobranca = $this->cobrancasRepo->find($id);

        if (!$cobranca) {
            return new JsonResponse(['success' => false, 'message' => 'Cobrança não encontrada'], 404);
        }

        if (!$cobranca->podeEnviarManualmente()) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Cobrança não pode ser enviada no status atual'
            ], 400);
        }

        $resultado = $this->cobrancaService->gerarEEnviarBoleto(
            $cobranca,
            ContratosCobrancas::TIPO_ENVIO_MANUAL
        );

        return new JsonResponse([
            'success' => $resultado['sucesso'],
            'message' => $resultado['mensagem'],
            'status' => $cobranca->getStatus(),
            'statusLabel' => $cobranca->getStatusLabel(),
            'statusClass' => $cobranca->getStatusClass(),
        ]);
    }

    /**
     * Envia múltiplas cobranças (AJAX).
     */
    #[Route('/enviar-lote', name: 'app_cobranca_enviar_lote', methods: ['POST'])]
    public function enviarLote(Request $request): JsonResponse
    {
        if (!$this->isCsrfTokenValid('ajax_global', $request->headers->get('X-CSRF-Token'))) {
            return new JsonResponse(['success' => false, 'message' => 'Token CSRF inválido'], 403);
        }

        $data = json_decode($request->getContent(), true);
        $ids = $data['ids'] ?? [];

        if (empty($ids)) {
            return new JsonResponse(['success' => false, 'message' => 'Nenhuma cobrança selecionada'], 400);
        }

        // REGRA DE NEGÓCIO: boleto só é emitido com o relatório de conferência APROVADO.
        // O front só manda aprovado=true depois que o usuário confirma o relatório.
        if (($data['aprovado'] ?? false) !== true) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Emissão bloqueada: gere e aprove o relatório de conferência antes de emitir os boletos.'
            ], 400);
        }

        $resultados = [
            'total' => count($ids),
            'sucesso' => 0,
            'falha' => 0,
            'detalhes' => []
        ];

        foreach ($ids as $id) {
            $cobranca = $this->cobrancasRepo->find($id);

            if (!$cobranca) {
                $resultados['falha']++;
                $resultados['detalhes'][] = [
                    'id' => $id,
                    'sucesso' => false,
                    'mensagem' => 'Cobrança não encontrada'
                ];
                continue;
            }

            if (!$cobranca->podeEnviarManualmente()) {
                $resultados['falha']++;
                $resultados['detalhes'][] = [
                    'id' => $id,
                    'sucesso' => false,
                    'mensagem' => 'Status não permite envio'
                ];
                continue;
            }

            $resultado = $this->cobrancaService->gerarEEnviarBoleto(
                $cobranca,
                ContratosCobrancas::TIPO_ENVIO_MANUAL
            );

            if ($resultado['sucesso']) {
                $resultados['sucesso']++;
            } else {
                $resultados['falha']++;
            }

            $resultados['detalhes'][] = [
                'id' => $id,
                'sucesso' => $resultado['sucesso'],
                'mensagem' => $resultado['mensagem']
            ];
        }

        return new JsonResponse([
            'success' => true,
            'message' => sprintf(
                'Processados %d cobranças: %d sucesso, %d falha',
                $resultados['total'],
                $resultados['sucesso'],
                $resultados['falha']
            ),
            'total' => $resultados['total'],
            'sucesso' => $resultados['sucesso'],
            'falha' => $resultados['falha'],
            'detalhes' => $resultados['detalhes'],
        ]);
    }

    /**
     * Cancela uma cobrança (AJAX).
     */
    #[Route('/{id}/cancelar', name: 'app_cobranca_cancelar', methods: ['POST'])]
    public function cancelar(Request $request, int $id): JsonResponse
    {
        if (!$this->isCsrfTokenValid('ajax_global', $request->headers->get('X-CSRF-Token'))) {
            return new JsonResponse(['success' => false, 'message' => 'Token CSRF inválido'], 403);
        }

        $cobranca = $this->cobrancasRepo->find($id);

        if (!$cobranca) {
            return new JsonResponse(['success' => false, 'message' => 'Cobrança não encontrada'], 404);
        }

        $resultado = $this->cobrancaService->cancelarCobranca($cobranca);

        return new JsonResponse([
            'success' => $resultado['sucesso'],
            'message' => $resultado['mensagem'],
            'status' => $cobranca->getStatus(),
            'statusLabel' => $cobranca->getStatusLabel(),
            'statusClass' => $cobranca->getStatusClass(),
        ]);
    }

    /**
     * Marca cobrança com canal Administração/Correio como entregue manualmente.
     */
    #[Route('/{id}/marcar-entregue', name: 'app_cobranca_marcar_entregue', methods: ['POST'])]
    public function marcarEntregue(Request $request, int $id): JsonResponse
    {
        if (!$this->isCsrfTokenValid('ajax_global', $request->headers->get('X-CSRF-Token'))) {
            return new JsonResponse(['success' => false, 'message' => 'Token CSRF inválido'], 403);
        }

        $cobranca = $this->cobrancasRepo->find($id);

        if (!$cobranca) {
            return new JsonResponse(['success' => false, 'message' => 'Cobrança não encontrada'], 404);
        }

        $resultado = $this->cobrancaService->marcarEntregue($cobranca);

        return new JsonResponse([
            'success' => $resultado['sucesso'],
            'message' => $resultado['mensagem'],
            'status' => $cobranca->getStatus(),
            'statusLabel' => $cobranca->getStatusLabel(),
            'statusClass' => $cobranca->getStatusClass(),
        ]);
    }

    /**
     * Gera preview de cobrança (sem enviar).
     */
    #[Route('/gerar-preview', name: 'app_cobranca_preview', methods: ['POST'])]
    public function gerarPreview(Request $request): JsonResponse
    {
        if (!$this->isCsrfTokenValid('ajax_global', $request->headers->get('X-CSRF-Token'))) {
            return new JsonResponse(['success' => false, 'message' => 'Token CSRF inválido'], 403);
        }

        $data = json_decode($request->getContent(), true);
        $ids = $data['ids'] ?? [];

        if (empty($ids)) {
            return new JsonResponse(['success' => false, 'message' => 'Nenhuma cobrança selecionada'], 400);
        }

        $preview = [];
        $valorTotal = 0;

        foreach ($ids as $id) {
            $cobranca = $this->cobrancasRepo->find($id);

            if ($cobranca && $cobranca->podeEnviarManualmente()) {
                $contrato = $cobranca->getContrato();
                $locatario = $contrato->getPessoaLocatario();

                $preview[] = [
                    'id' => $cobranca->getId(),
                    'contrato' => $contrato->getId(),
                    'locatario' => $locatario ? $locatario->getNome() : '-',
                    'competencia' => $cobranca->getCompetenciaFormatada(),
                    'vencimento' => $cobranca->getDataVencimento()->format('d/m/Y'),
                    'valor' => $cobranca->getValorTotalFloat(),
                    'valor_formatado' => $cobranca->getValorTotalFormatado(),
                ];

                $valorTotal += $cobranca->getValorTotalFloat();
            }
        }

        return new JsonResponse([
            'success' => true,
            'cobrancas' => $preview,
            'quantidade' => count($preview),
            'valor_total' => $valorTotal,
            'valor_total_formatado' => 'R$ ' . number_format($valorTotal, 2, ',', '.'),
        ]);
    }

    /**
     * Gera a RELAÇÃO (rascunhos de cobrança) de um período, SEM enviar. O usuário
     * depois confere o relatório e só então emite. Aceita competência (mês) OU
     * intervalo de datas de vencimento.
     */
    #[Route('/gerar-periodo', name: 'app_cobranca_gerar_periodo', methods: ['POST'])]
    public function gerarPeriodo(Request $request): Response
    {
        if (!$this->isCsrfTokenValid('cobranca_gerar_periodo', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF inválido.');
            return $this->redirectToRoute('app_cobranca_pendentes');
        }

        $modo = $request->request->get('modo', 'vencimento');
        $opts = [];
        $redir = [];

        try {
            if ($modo === 'competencia') {
                $comp = trim((string) $request->request->get('competencia', ''));
                if (!preg_match('/^\d{4}-\d{2}$/', $comp)) {
                    throw new \InvalidArgumentException('Informe a competência (mês/ano).');
                }
                $opts['competencia'] = $comp;
                [$y, $m] = explode('-', $comp);
                $ini = new \DateTime(sprintf('%s-%s-01', $y, $m));
                $fim = (clone $ini)->modify('last day of this month');
                $redir = ['vencimento_inicio' => $ini->format('Y-m-d'), 'vencimento_fim' => $fim->format('Y-m-d')];
            } else {
                $iniS = (string) $request->request->get('vencimento_inicio', '');
                $fimS = (string) $request->request->get('vencimento_fim', '');
                if (!$iniS || !$fimS) {
                    throw new \InvalidArgumentException('Informe o intervalo de vencimento (início e fim).');
                }
                $ini = new \DateTime($iniS);
                $fim = new \DateTime($fimS);
                if ($fim < $ini) {
                    throw new \InvalidArgumentException('A data final deve ser maior ou igual à inicial.');
                }
                $opts['vencimento_inicio'] = $ini;
                $opts['vencimento_fim'] = $fim;
                $redir = ['vencimento_inicio' => $ini->format('Y-m-d'), 'vencimento_fim' => $fim->format('Y-m-d')];
            }

            $r = $this->cobrancaService->gerarCobrancasDoPeriodo($opts);
            $msg = sprintf('Relação gerada: %d nova(s), %d já existente(s).', $r['criadas'], $r['existentes']);
            if (!empty($r['erros'])) {
                $msg .= ' Avisos: ' . implode('; ', array_slice($r['erros'], 0, 3));
            }
            $this->addFlash('success', $msg);
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
            return $this->redirectToRoute('app_cobranca_pendentes');
        }

        return $this->redirectToRoute('app_cobranca_pendentes', $redir);
    }

    /**
     * Retorna os itens de uma cobrança (para o modal de edição) — AJAX.
     */
    #[Route('/{id}/itens', name: 'app_cobranca_itens_get', methods: ['GET'])]
    public function getItens(int $id): JsonResponse
    {
        $cobranca = $this->cobrancasRepo->find($id);
        if (!$cobranca) {
            return new JsonResponse(['success' => false, 'message' => 'Cobrança não encontrada'], 404);
        }

        return new JsonResponse([
            'success' => true,
            'id' => $cobranca->getId(),
            'competencia' => $cobranca->getCompetenciaFormatada(),
            'podeEditar' => $cobranca->getStatus() === ContratosCobrancas::STATUS_PENDENTE,
            'itens' => $cobranca->getItensDetalhados() ?: [],
            'tipos' => ContratosItensCobranca::getTiposDisponiveis(),
        ]);
    }

    /**
     * Salva os itens editados de uma cobrança PENDENTE — AJAX.
     */
    #[Route('/{id}/itens', name: 'app_cobranca_itens_save', methods: ['POST'])]
    public function saveItens(Request $request, int $id): JsonResponse
    {
        if (!$this->isCsrfTokenValid('ajax_global', $request->headers->get('X-CSRF-Token'))) {
            return new JsonResponse(['success' => false, 'message' => 'Token CSRF inválido'], 403);
        }

        $cobranca = $this->cobrancasRepo->find($id);
        if (!$cobranca) {
            return new JsonResponse(['success' => false, 'message' => 'Cobrança não encontrada'], 404);
        }

        $data = json_decode($request->getContent(), true);
        $itens = $data['itens'] ?? [];

        $r = $this->cobrancaService->atualizarItensCobranca($cobranca, $itens);

        return new JsonResponse([
            'success' => $r['sucesso'],
            'message' => $r['mensagem'],
            'valor_total_formatado' => $cobranca->getValorTotalFormatado(),
        ]);
    }

    /**
     * Emite um boleto AVULSO (fora de contrato) direto da tela de lote — AJAX.
     * Exige confirmação (o modal é a conferência do avulso).
     */
    #[Route('/avulso', name: 'app_cobranca_avulso', methods: ['POST'])]
    public function avulso(Request $request): JsonResponse
    {
        if (!$this->isCsrfTokenValid('ajax_global', $request->headers->get('X-CSRF-Token'))) {
            return new JsonResponse(['success' => false, 'message' => 'Token CSRF inválido'], 403);
        }

        $data = json_decode($request->getContent(), true) ?? [];

        if (($data['aprovado'] ?? false) !== true) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Confira os dados e aprove antes de emitir o boleto avulso.'
            ], 400);
        }

        $r = $this->cobrancaService->emitirBoletoAvulso($data);

        return new JsonResponse([
            'success' => $r['sucesso'],
            'message' => $r['mensagem'],
            'boleto_id' => $r['boleto_id'] ?? null,
        ]);
    }

    /**
     * Verifica (sob demanda) o pagamento do boleto de uma cobrança — AJAX.
     */
    #[Route('/{id}/verificar-pagamento', name: 'app_cobranca_verificar_pagamento', methods: ['POST'])]
    public function verificarPagamento(Request $request, int $id): JsonResponse
    {
        if (!$this->isCsrfTokenValid('ajax_global', $request->headers->get('X-CSRF-Token'))) {
            return new JsonResponse(['success' => false, 'message' => 'Token CSRF inválido'], 403);
        }

        $cobranca = $this->cobrancasRepo->find($id);
        if (!$cobranca) {
            return new JsonResponse(['success' => false, 'message' => 'Cobrança não encontrada'], 404);
        }

        $r = $this->cobrancaService->verificarPagamento($cobranca);

        return new JsonResponse([
            'success' => $r['sucesso'],
            'message' => $r['mensagem'],
            'status' => $cobranca->getStatus(),
            'statusLabel' => $cobranca->getStatusLabel(),
            'statusClass' => $cobranca->getStatusClass(),
        ]);
    }

    /**
     * API: Retorna estatísticas de cobranças (AJAX).
     */
    #[Route('/api/estatisticas', name: 'app_cobranca_api_estatisticas', methods: ['GET'])]
    public function apiEstatisticas(Request $request): JsonResponse
    {
        $dataVencimentoStr = $request->query->get('data_vencimento');
        $dataVencimento = null;

        if ($dataVencimentoStr) {
            try {
                $dataVencimento = new \DateTime($dataVencimentoStr);
            } catch (\Exception $e) {
                // Ignora
            }
        }

        $estatisticas = $this->cobrancaService->getEstatisticas($dataVencimento);

        return new JsonResponse($estatisticas);
    }
}
