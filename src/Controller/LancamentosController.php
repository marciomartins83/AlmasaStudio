<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\SearchFilterDTO;
use App\DTO\SortOptionDTO;
use App\Entity\Lancamentos;
use App\Form\LancamentosType;
use App\Repository\LancamentosRepository;
use App\Service\LancamentosService;
use App\Service\PaginationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * LancamentosController - Thin Controller
 *
 * Responsabilidades:
 * - Receber Request
 * - Validar CSRF
 * - Delegar para LancamentosService
 * - Retornar Response (View ou JSON)
 */
#[Route('/lancamentos')]
class LancamentosController extends AbstractController
{
    public function __construct(
        private LancamentosService $lancamentosService,
        private LancamentosRepository $lancamentosRepository,
        private PaginationService $paginator
    ) {}

    /**
     * Listagem de lançamentos com filtros
     */
    #[Route('/', name: 'app_lancamentos_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $competencias = $this->lancamentosService->listarCompetencias();
        $competenciasChoices = array_combine($competencias, $competencias);

        $qb = $this->lancamentosRepository->createBaseQueryBuilder();

        $filters = [
            new SearchFilterDTO('tipo', 'Tipo', 'select', 'l.tipo', 'EXACT', [
                Lancamentos::TIPO_RECEBER => 'A Receber',
                Lancamentos::TIPO_PAGAR   => 'A Pagar',
            ], null, 2),
            new SearchFilterDTO('status', 'Status', 'select', 'l.status', 'EXACT', [
                Lancamentos::STATUS_ABERTO      => 'Aberto',
                Lancamentos::STATUS_PAGO        => 'Pago',
                Lancamentos::STATUS_PAGO_PARCIAL => 'Pago Parcial',
                Lancamentos::STATUS_CANCELADO   => 'Cancelado',
                Lancamentos::STATUS_SUSPENSO    => 'Suspenso',
            ], null, 2),
            new SearchFilterDTO('vencimentoDe', 'Venc. De', 'date', 'l.dataVencimento', 'GTE', [], null, 2),
            new SearchFilterDTO('vencimentoAte', 'Venc. Ate', 'date', 'l.dataVencimento', 'LTE', [], null, 2),
            new SearchFilterDTO('competencia', 'Competencia', 'select', 'l.competencia', 'EXACT', $competenciasChoices, null, 2),
        ];

        $sortOptions = [
            new SortOptionDTO('dataVencimento', 'Vencimento', 'DESC'),
            new SortOptionDTO('competencia', 'Competencia', 'DESC'),
            new SortOptionDTO('valor', 'Valor', 'DESC'),
            new SortOptionDTO('status', 'Status', 'ASC'),
        ];

        $pagination = $this->paginator->paginate($qb, $request, null, [], 'l.id', $filters, $sortOptions, 'dataVencimento', 'DESC');

        $estatisticas = $this->lancamentosService->getEstatisticas();
        $planosContas = $this->lancamentosService->listarPlanosContaAtivos();

        return $this->render('lancamentos/index.html.twig', [
            'lancamentos'  => $pagination['items'],
            'estatisticas' => $estatisticas,
            'planosContas' => $planosContas,
            'competencias' => $competencias,
            'totalItems'   => $pagination['totalItems'],
            'currentPage'  => $pagination['currentPage'],
            'itemsPerPage' => $pagination['itemsPerPage'],
            'totalPages'   => $pagination['totalPages'],
            'filters'      => $pagination['filters'],
            'filterDefs'   => $pagination['filterDefs'],
            'sortField'    => $pagination['sortField'],
            'sortDir'      => $pagination['sortDir'],
            'sortOptions'  => $pagination['sortOptions'],
        ]);
    }

    /**
     * Formulário de novo lançamento
     */
    #[Route('/new', name: 'app_lancamentos_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $lancamento = new Lancamentos();
        $form = $this->createForm(LancamentosType::class, $lancamento);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $dados = $this->extrairDadosFormulario($form);
                $this->lancamentosService->salvarLancamento($dados);

                $this->addFlash('success', 'Lançamento criado com sucesso!');
                return $this->redirectToRoute('app_lancamentos_index');

            } catch (\Exception $e) {
                $this->addFlash('error', 'Erro: ' . $e->getMessage());
            }
        }

        return $this->render('lancamentos/new.html.twig', [
            'form' => $form,
            'lancamento' => $lancamento,
        ]);
    }

    /**
     * Formulário de edição
     */
    #[Route('/{id}/edit', name: 'app_lancamentos_edit', methods: ['GET', 'POST'])]
    public function edit(int $id, Request $request): Response
    {
        $lancamento = $this->lancamentosService->buscarPorId($id);

        if (!$lancamento) {
            $this->addFlash('error', 'Lançamento não encontrado');
            return $this->redirectToRoute('app_lancamentos_index');
        }

        $form = $this->createForm(LancamentosType::class, $lancamento);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $dados = $this->extrairDadosFormulario($form);
                $this->lancamentosService->atualizarLancamento($lancamento, $dados);

                $this->addFlash('success', 'Lançamento atualizado com sucesso!');
                return $this->redirectToRoute('app_lancamentos_index');

            } catch (\Exception $e) {
                $this->addFlash('error', 'Erro: ' . $e->getMessage());
            }
        }

        return $this->render('lancamentos/edit.html.twig', [
            'form' => $form,
            'lancamento' => $lancamento,
        ]);
    }

    /**
     * Excluir lançamento
     */
    #[Route('/{id}', name: 'app_lancamentos_delete', methods: ['DELETE'])]
    public function delete(int $id, Request $request): JsonResponse
    {
        $token = $request->headers->get('X-CSRF-Token');
        if (!$this->isCsrfTokenValid('ajax_global', $token)) {
            return $this->json(['success' => false, 'message' => 'Token CSRF inválido'], 403);
        }

        try {
            $lancamento = $this->lancamentosService->buscarPorId($id);
            if (!$lancamento) {
                return $this->json(['success' => false, 'message' => 'Lançamento não encontrado'], 404);
            }

            $this->lancamentosService->excluirLancamento($lancamento);

            return $this->json(['success' => true, 'message' => 'Lançamento excluído com sucesso']);

        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Realizar baixa (pagamento)
     */
    #[Route('/{id}/baixa', name: 'app_lancamentos_baixa', methods: ['POST'])]
    public function baixa(int $id, Request $request): JsonResponse
    {
        $token = $request->headers->get('X-CSRF-Token');
        if (!$this->isCsrfTokenValid('ajax_global', $token)) {
            return $this->json(['success' => false, 'message' => 'Token CSRF inválido'], 403);
        }

        try {
            $dados = json_decode($request->getContent(), true) ?? [];
            $lancamento = $this->lancamentosService->baixarLancamento($id, $dados);

            return $this->json([
                'success' => true,
                'message' => 'Baixa realizada com sucesso',
                'status' => $lancamento->getStatus(),
                'valorPago' => $lancamento->getValorPagoFloat(),
            ]);

        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Estornar baixa
     */
    #[Route('/{id}/estornar', name: 'app_lancamentos_estornar', methods: ['POST'])]
    public function estornar(int $id, Request $request): JsonResponse
    {
        $token = $request->headers->get('X-CSRF-Token');
        if (!$this->isCsrfTokenValid('ajax_global', $token)) {
            return $this->json(['success' => false, 'message' => 'Token CSRF inválido'], 403);
        }

        try {
            $lancamento = $this->lancamentosService->estornarBaixa($id);

            return $this->json([
                'success' => true,
                'message' => 'Estorno realizado com sucesso',
                'status' => $lancamento->getStatus(),
            ]);

        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Cancelar lançamento
     */
    #[Route('/{id}/cancelar', name: 'app_lancamentos_cancelar', methods: ['POST'])]
    public function cancelar(int $id, Request $request): JsonResponse
    {
        $token = $request->headers->get('X-CSRF-Token');
        if (!$this->isCsrfTokenValid('ajax_global', $token)) {
            return $this->json(['success' => false, 'message' => 'Token CSRF inválido'], 403);
        }

        try {
            $dados = json_decode($request->getContent(), true) ?? [];
            $motivo = $dados['motivo'] ?? 'Cancelamento solicitado';

            $lancamento = $this->lancamentosService->cancelarLancamento($id, $motivo);

            return $this->json([
                'success' => true,
                'message' => 'Lançamento cancelado com sucesso',
                'status' => $lancamento->getStatus(),
            ]);

        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Suspender lançamento
     */
    #[Route('/{id}/suspender', name: 'app_lancamentos_suspender', methods: ['POST'])]
    public function suspender(int $id, Request $request): JsonResponse
    {
        $token = $request->headers->get('X-CSRF-Token');
        if (!$this->isCsrfTokenValid('ajax_global', $token)) {
            return $this->json(['success' => false, 'message' => 'Token CSRF inválido'], 403);
        }

        try {
            $dados = json_decode($request->getContent(), true) ?? [];
            $motivo = $dados['motivo'] ?? 'Suspenso pelo usuário';

            $lancamento = $this->lancamentosService->suspenderLancamento($id, $motivo);

            return $this->json([
                'success' => true,
                'message' => 'Lançamento suspenso com sucesso',
                'status' => $lancamento->getStatus(),
            ]);

        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Reativar lançamento suspenso
     */
    #[Route('/{id}/reativar', name: 'app_lancamentos_reativar', methods: ['POST'])]
    public function reativar(int $id, Request $request): JsonResponse
    {
        $token = $request->headers->get('X-CSRF-Token');
        if (!$this->isCsrfTokenValid('ajax_global', $token)) {
            return $this->json(['success' => false, 'message' => 'Token CSRF inválido'], 403);
        }

        try {
            $lancamento = $this->lancamentosService->reativarLancamento($id);

            return $this->json([
                'success' => true,
                'message' => 'Lançamento reativado com sucesso',
                'status' => $lancamento->getStatus(),
            ]);

        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Lista lançamentos vencidos
     */
    #[Route('/vencidos', name: 'app_lancamentos_vencidos', methods: ['GET'])]
    public function vencidos(Request $request): Response
    {
        $tipo = $request->query->get('tipo');
        $lancamentos = $this->lancamentosService->listarVencidos($tipo);

        return $this->render('lancamentos/vencidos.html.twig', [
            'lancamentos' => $lancamentos,
            'tipo' => $tipo,
        ]);
    }

    /**
     * Dashboard de estatísticas
     */
    #[Route('/estatisticas', name: 'app_lancamentos_estatisticas', methods: ['GET'])]
    public function estatisticas(Request $request): Response
    {
        $competencia = $request->query->get('competencia');
        $estatisticas = $this->lancamentosService->getEstatisticas($competencia);
        $competencias = $this->lancamentosService->listarCompetencias();

        return $this->render('lancamentos/estatisticas.html.twig', [
            'estatisticas' => $estatisticas,
            'competencias' => $competencias,
            'competenciaAtual' => $competencia,
        ]);
    }

    /**
     * API - Lista lançamentos
     */
    #[Route('/api/lista', name: 'app_lancamentos_api_lista', methods: ['GET'])]
    public function apiLista(Request $request): JsonResponse
    {
        $filtros = [
            'tipo' => $request->query->get('tipo'),
            'status' => $request->query->get('status'),
            'competencia' => $request->query->get('competencia'),
        ];

        $filtros = array_filter($filtros, fn($v) => $v !== null && $v !== '');

        $lancamentos = $this->lancamentosService->listarLancamentos($filtros);

        $data = array_map(fn($l) => [
            'id' => $l->getId(),
            'numero' => $l->getNumero(),
            'tipo' => $l->getTipo(),
            'tipoLabel' => $l->getTipoLabel(),
            'dataVencimento' => $l->getDataVencimento()->format('Y-m-d'),
            'valor' => $l->getValorFloat(),
            'valorPago' => $l->getValorPagoFloat(),
            'saldo' => $l->getSaldo(),
            'status' => $l->getStatus(),
            'statusLabel' => $l->getStatusLabel(),
            'historico' => $l->getHistorico(),
            'vencido' => $l->isVencido(),
            'diasAtraso' => $l->getDiasAtraso(),
        ], $lancamentos);

        return $this->json(['success' => true, 'lancamentos' => $data]);
    }

    /**
     * API - Estatísticas
     */
    #[Route('/api/estatisticas', name: 'app_lancamentos_api_estatisticas', methods: ['GET'])]
    public function apiEstatisticas(Request $request): JsonResponse
    {
        $competencia = $request->query->get('competencia');
        $estatisticas = $this->lancamentosService->getEstatisticas($competencia);

        return $this->json(['success' => true, 'estatisticas' => $estatisticas]);
    }

    /**
     * Extrai dados do formulário para array
     */
    private function extrairDadosFormulario($form): array
    {
        $lancamento = $form->getData();

        return [
            'tipo' => $lancamento->getTipo(),
            'data_movimento' => $lancamento->getDataMovimento()->format('Y-m-d'),
            'data_vencimento' => $lancamento->getDataVencimento()->format('Y-m-d'),
            'competencia' => $lancamento->getCompetencia(),
            'id_plano_conta' => $lancamento->getPlanoConta()?->getId(),
            'historico' => $lancamento->getHistorico(),
            'centro_custo' => $lancamento->getCentroCusto(),
            'id_pessoa_credor' => $lancamento->getPessoaCredor()?->getIdpessoa(),
            'id_pessoa_pagador' => $lancamento->getPessoaPagador()?->getIdpessoa(),
            'id_contrato' => $lancamento->getContrato()?->getId(),
            'id_imovel' => $lancamento->getImovel()?->getId(),
            'id_conta_bancaria' => $lancamento->getContaBancaria()?->getId(),
            'valor' => $lancamento->getValor(),
            'valor_desconto' => $lancamento->getValorDesconto(),
            'valor_juros' => $lancamento->getValorJuros(),
            'valor_multa' => $lancamento->getValorMulta(),
            'reter_inss' => $lancamento->isReterInss(),
            'perc_inss' => $lancamento->getPercInss(),
            'reter_iss' => $lancamento->isReterIss(),
            'perc_iss' => $lancamento->getPercIss(),
            'tipo_documento' => $lancamento->getTipoDocumento(),
            'numero_documento' => $lancamento->getNumeroDocumento(),
            'forma_pagamento' => $lancamento->getFormaPagamento(),
            'observacoes' => $lancamento->getObservacoes(),
        ];
    }
}
