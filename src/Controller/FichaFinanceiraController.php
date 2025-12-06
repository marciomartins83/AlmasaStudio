<?php

namespace App\Controller;

use App\Service\FichaFinanceiraService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * FichaFinanceiraController - Thin Controller
 *
 * Responsabilidades:
 * - Receber Request
 * - Validar CSRF
 * - Delegar para FichaFinanceiraService
 * - Retornar Response (View ou JSON)
 */
#[Route('/financeiro')]
class FichaFinanceiraController extends AbstractController
{
    public function __construct(
        private FichaFinanceiraService $fichaService
    ) {}

    /**
     * Lista de lançamentos financeiros
     */
    #[Route('/', name: 'app_financeiro_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $filtros = [
            'situacao' => $request->query->get('situacao'),
            'inquilino' => $request->query->get('inquilino'),
            'competenciaInicio' => $request->query->get('competenciaInicio'),
            'competenciaFim' => $request->query->get('competenciaFim'),
            'emAtraso' => $request->query->getBoolean('emAtraso'),
        ];

        // Remove filtros vazios
        $filtros = array_filter($filtros, fn($v) => $v !== null && $v !== '');

        $lancamentos = $this->fichaService->listarLancamentos($filtros);
        $estatisticas = $this->fichaService->obterEstatisticas();
        $inadimplentes = $this->fichaService->listarInquilinosComDebitos();

        return $this->render('financeiro/index.html.twig', [
            'lancamentos' => $lancamentos,
            'estatisticas' => $estatisticas,
            'inadimplentes' => $inadimplentes,
            'filtros' => $filtros,
        ]);
    }

    /**
     * Ficha financeira de um inquilino
     */
    #[Route('/ficha/{inquilinoId}', name: 'app_financeiro_ficha', methods: ['GET'])]
    public function ficha(int $inquilinoId, Request $request): Response
    {
        $ano = $request->query->getInt('ano') ?: null;
        $dados = $this->fichaService->buscarFichaFinanceira($inquilinoId, $ano);

        if (!$dados['inquilino']) {
            $this->addFlash('error', 'Inquilino não encontrado');
            return $this->redirectToRoute('app_financeiro_index');
        }

        return $this->render('financeiro/ficha.html.twig', [
            'dados' => $dados,
            'inquilinoId' => $inquilinoId,
            'ano' => $ano,
        ]);
    }

    /**
     * Formulário de novo lançamento
     */
    #[Route('/lancamento/new', name: 'app_financeiro_lancamento_new', methods: ['GET', 'POST'])]
    public function novoLancamento(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $token = $request->headers->get('X-CSRF-Token') ?? $request->request->get('_token');
            if (!$this->isCsrfTokenValid('ajax_global', $token)) {
                if ($request->isXmlHttpRequest()) {
                    return $this->json(['success' => false, 'message' => 'Token CSRF inválido'], 403);
                }
                $this->addFlash('error', 'Token CSRF inválido');
                return $this->redirectToRoute('app_financeiro_lancamento_new');
            }

            try {
                $dados = $request->isXmlHttpRequest()
                    ? json_decode($request->getContent(), true)
                    : $request->request->all();

                $lancamento = $this->fichaService->criarLancamento($dados);

                if ($request->isXmlHttpRequest()) {
                    return $this->json([
                        'success' => true,
                        'message' => 'Lançamento criado com sucesso',
                        'id' => $lancamento->getId()
                    ]);
                }

                $this->addFlash('success', 'Lançamento criado com sucesso!');
                return $this->redirectToRoute('app_financeiro_index');

            } catch (\Exception $e) {
                if ($request->isXmlHttpRequest()) {
                    return $this->json(['success' => false, 'message' => $e->getMessage()], 400);
                }
                $this->addFlash('error', 'Erro: ' . $e->getMessage());
            }
        }

        return $this->render('financeiro/lancamento_form.html.twig', [
            'lancamento' => null,
            'isEdit' => false,
        ]);
    }

    /**
     * Formulário de edição de lançamento
     */
    #[Route('/lancamento/{id}/edit', name: 'app_financeiro_lancamento_edit', methods: ['GET', 'POST'])]
    public function editarLancamento(int $id, Request $request): Response
    {
        $lancamento = $this->fichaService->buscarLancamentoPorId($id);

        if (!$lancamento) {
            $this->addFlash('error', 'Lançamento não encontrado');
            return $this->redirectToRoute('app_financeiro_index');
        }

        if ($request->isMethod('POST')) {
            $token = $request->headers->get('X-CSRF-Token') ?? $request->request->get('_token');
            if (!$this->isCsrfTokenValid('ajax_global', $token)) {
                if ($request->isXmlHttpRequest()) {
                    return $this->json(['success' => false, 'message' => 'Token CSRF inválido'], 403);
                }
                $this->addFlash('error', 'Token CSRF inválido');
                return $this->redirectToRoute('app_financeiro_lancamento_edit', ['id' => $id]);
            }

            try {
                $dados = $request->isXmlHttpRequest()
                    ? json_decode($request->getContent(), true)
                    : $request->request->all();

                $this->fichaService->atualizarLancamento($id, $dados);

                if ($request->isXmlHttpRequest()) {
                    return $this->json([
                        'success' => true,
                        'message' => 'Lançamento atualizado com sucesso'
                    ]);
                }

                $this->addFlash('success', 'Lançamento atualizado!');
                return $this->redirectToRoute('app_financeiro_index');

            } catch (\Exception $e) {
                if ($request->isXmlHttpRequest()) {
                    return $this->json(['success' => false, 'message' => $e->getMessage()], 400);
                }
                $this->addFlash('error', 'Erro: ' . $e->getMessage());
            }
        }

        return $this->render('financeiro/lancamento_form.html.twig', [
            'lancamento' => $lancamento,
            'isEdit' => true,
        ]);
    }

    /**
     * Visualização de lançamento
     */
    #[Route('/lancamento/{id}', name: 'app_financeiro_lancamento_show', methods: ['GET'])]
    public function showLancamento(int $id): Response
    {
        $lancamento = $this->fichaService->buscarLancamentoPorId($id);

        if (!$lancamento) {
            $this->addFlash('error', 'Lançamento não encontrado');
            return $this->redirectToRoute('app_financeiro_index');
        }

        return $this->render('financeiro/lancamento_show.html.twig', [
            'lancamento' => $lancamento,
        ]);
    }

    /**
     * Realiza baixa (pagamento)
     */
    #[Route('/lancamento/{id}/baixa', name: 'app_financeiro_baixa', methods: ['POST'])]
    public function realizarBaixa(int $id, Request $request): JsonResponse
    {
        $token = $request->headers->get('X-CSRF-Token');
        if (!$this->isCsrfTokenValid('ajax_global', $token)) {
            return $this->json(['success' => false, 'message' => 'Token CSRF inválido'], 403);
        }

        try {
            $dados = json_decode($request->getContent(), true);
            $baixa = $this->fichaService->realizarBaixa($id, $dados);

            return $this->json([
                'success' => true,
                'message' => 'Baixa realizada com sucesso',
                'baixaId' => $baixa->getId()
            ]);

        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Estorna baixa
     */
    #[Route('/baixa/{id}/estornar', name: 'app_financeiro_estornar', methods: ['POST'])]
    public function estornarBaixa(int $id, Request $request): JsonResponse
    {
        $token = $request->headers->get('X-CSRF-Token');
        if (!$this->isCsrfTokenValid('ajax_global', $token)) {
            return $this->json(['success' => false, 'message' => 'Token CSRF inválido'], 403);
        }

        try {
            $dados = json_decode($request->getContent(), true);
            $motivo = $dados['motivo'] ?? 'Estorno solicitado';

            $this->fichaService->estornarBaixa($id, $motivo);

            return $this->json([
                'success' => true,
                'message' => 'Baixa estornada com sucesso'
            ]);

        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Cancela lançamento
     */
    #[Route('/lancamento/{id}/cancelar', name: 'app_financeiro_cancelar', methods: ['POST'])]
    public function cancelarLancamento(int $id, Request $request): JsonResponse
    {
        $token = $request->headers->get('X-CSRF-Token');
        if (!$this->isCsrfTokenValid('ajax_global', $token)) {
            return $this->json(['success' => false, 'message' => 'Token CSRF inválido'], 403);
        }

        try {
            $dados = json_decode($request->getContent(), true);
            $motivo = $dados['motivo'] ?? 'Cancelamento solicitado';

            $this->fichaService->cancelarLancamento($id, $motivo);

            return $this->json([
                'success' => true,
                'message' => 'Lançamento cancelado com sucesso'
            ]);

        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Gera lançamentos automáticos para uma competência
     */
    #[Route('/gerar-lancamentos', name: 'app_financeiro_gerar', methods: ['POST'])]
    public function gerarLancamentos(Request $request): JsonResponse
    {
        $token = $request->headers->get('X-CSRF-Token');
        if (!$this->isCsrfTokenValid('ajax_global', $token)) {
            return $this->json(['success' => false, 'message' => 'Token CSRF inválido'], 403);
        }

        try {
            $dados = json_decode($request->getContent(), true);
            $competencia = new \DateTime($dados['competencia'] . '-01');

            $lancamentos = $this->fichaService->gerarLancamentosAutomaticos($competencia);

            return $this->json([
                'success' => true,
                'message' => count($lancamentos) . ' lançamento(s) gerado(s) com sucesso',
                'quantidade' => count($lancamentos)
            ]);

        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Lista lançamentos em atraso
     */
    #[Route('/em-atraso', name: 'app_financeiro_atraso', methods: ['GET'])]
    public function emAtraso(): Response
    {
        $lancamentos = $this->fichaService->buscarEmAtraso();

        return $this->render('financeiro/em_atraso.html.twig', [
            'lancamentos' => $lancamentos,
        ]);
    }

    /**
     * API - Lista lançamentos
     */
    #[Route('/api/lancamentos', name: 'app_financeiro_api_lista', methods: ['GET'])]
    public function apiLista(Request $request): JsonResponse
    {
        $filtros = [
            'situacao' => $request->query->get('situacao'),
            'inquilino' => $request->query->get('inquilino'),
            'imovel' => $request->query->get('imovel'),
            'competenciaInicio' => $request->query->get('competenciaInicio'),
            'competenciaFim' => $request->query->get('competenciaFim'),
        ];

        // Remove filtros vazios
        $filtros = array_filter($filtros, fn($v) => $v !== null && $v !== '');

        $lancamentos = $this->fichaService->listarLancamentos($filtros);

        return $this->json([
            'success' => true,
            'lancamentos' => $lancamentos
        ]);
    }

    /**
     * API - Estatísticas
     */
    #[Route('/api/estatisticas', name: 'app_financeiro_api_estatisticas', methods: ['GET'])]
    public function apiEstatisticas(Request $request): JsonResponse
    {
        $filtros = [
            'competenciaInicio' => $request->query->get('competenciaInicio'),
            'competenciaFim' => $request->query->get('competenciaFim'),
        ];

        // Remove filtros vazios
        $filtros = array_filter($filtros, fn($v) => $v !== null && $v !== '');

        $estatisticas = $this->fichaService->obterEstatisticas($filtros ?: null);

        return $this->json([
            'success' => true,
            'estatisticas' => $estatisticas
        ]);
    }

    /**
     * API - Busca ficha financeira
     */
    #[Route('/api/ficha/{inquilinoId}', name: 'app_financeiro_api_ficha', methods: ['GET'])]
    public function apiFicha(int $inquilinoId, Request $request): JsonResponse
    {
        $ano = $request->query->getInt('ano') ?: null;
        $dados = $this->fichaService->buscarFichaFinanceira($inquilinoId, $ano);

        return $this->json([
            'success' => true,
            'dados' => $dados
        ]);
    }

    /**
     * API - Busca baixas recentes
     */
    #[Route('/api/baixas-recentes', name: 'app_financeiro_api_baixas_recentes', methods: ['GET'])]
    public function apiBaixasRecentes(Request $request): JsonResponse
    {
        $limite = $request->query->getInt('limite', 10);
        $baixas = $this->fichaService->buscarBaixasRecentes($limite);

        return $this->json([
            'success' => true,
            'baixas' => $baixas
        ]);
    }
}
