<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\PrestacoesContas;
use App\Form\PrestacaoContasFiltroType;
use App\Form\PrestacaoContasRepasseType;
use App\Service\PrestacaoContasService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * PrestacaoContasController - Thin Controller
 *
 * Responsabilidades:
 * - Receber Request
 * - Validar CSRF
 * - Delegar para PrestacaoContasService
 * - Retornar Response (View ou JSON)
 */
#[Route('/prestacao-contas')]
class PrestacaoContasController extends AbstractController
{
    public function __construct(
        private PrestacaoContasService $prestacaoService
    ) {}

    /**
     * Dashboard/Listagem de prestações
     */
    #[Route('/', name: 'app_prestacao_contas_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $filtros = [
            'proprietario' => $request->query->get('proprietario'),
            'status' => $request->query->get('status'),
            'ano' => $request->query->get('ano'),
        ];

        $filtros = array_filter($filtros, fn($v) => $v !== null && $v !== '');

        $prestacoes = $this->prestacaoService->listarPrestacoes($filtros);
        $estatisticas = $this->prestacaoService->getEstatisticas(
            $filtros['ano'] ?? (int) date('Y')
        );
        $estatisticasMes = $this->prestacaoService->getEstatisticasMesAtual();
        $anosDisponiveis = $this->prestacaoService->getAnosDisponiveis();

        return $this->render('prestacao_contas/index.html.twig', [
            'prestacoes' => $prestacoes,
            'estatisticas' => $estatisticas,
            'estatisticasMes' => $estatisticasMes,
            'anosDisponiveis' => $anosDisponiveis,
            'filtros' => $filtros,
        ]);
    }

    /**
     * Formulário de geração de prestação
     */
    #[Route('/gerar', name: 'app_prestacao_contas_gerar', methods: ['GET', 'POST'])]
    public function gerar(Request $request): Response
    {
        $form = $this->createForm(PrestacaoContasFiltroType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $dados = $form->getData();
                $filtros = [
                    'proprietario' => $dados['proprietario']->getIdpessoa(),
                    'imovel' => $dados['imovel']?->getId(),
                    'data_inicio' => $dados['dataInicio'],
                    'data_fim' => $dados['dataFim'],
                    'tipo_periodo' => $dados['tipoPeriodo'],
                    'competencia' => $dados['competencia'],
                    'incluir_ficha_financeira' => $dados['incluirFichaFinanceira'] ?? true,
                    'incluir_lancamentos' => $dados['incluirLancamentos'] ?? true,
                ];

                $prestacao = $this->prestacaoService->gerarPrestacao($filtros);

                $this->addFlash('success', sprintf(
                    'Prestação de Contas nº %s gerada com sucesso!',
                    $prestacao->getNumeroFormatado()
                ));

                return $this->redirectToRoute('app_prestacao_contas_visualizar', [
                    'id' => $prestacao->getId(),
                ]);

            } catch (\Exception $e) {
                $this->addFlash('error', 'Erro: ' . $e->getMessage());
            }
        }

        return $this->render('prestacao_contas/gerar.html.twig', [
            'form' => $form,
        ]);
    }

    /**
     * Preview AJAX antes de gerar
     */
    #[Route('/preview', name: 'app_prestacao_contas_preview', methods: ['POST'])]
    public function preview(Request $request): JsonResponse
    {
        $token = $request->headers->get('X-CSRF-Token');
        if (!$this->isCsrfTokenValid('ajax_global', $token)) {
            return $this->json(['success' => false, 'message' => 'Token CSRF inválido'], 403);
        }

        try {
            $dados = json_decode($request->getContent(), true);

            if (empty($dados['proprietario'])) {
                return $this->json(['success' => false, 'message' => 'Proprietário é obrigatório']);
            }

            $filtros = [
                'proprietario' => $dados['proprietario'],
                'imovel' => $dados['imovel'] ?? null,
                'data_inicio' => new \DateTime($dados['dataInicio']),
                'data_fim' => new \DateTime($dados['dataFim']),
                'incluir_ficha_financeira' => $dados['incluirFichaFinanceira'] ?? true,
                'incluir_lancamentos' => $dados['incluirLancamentos'] ?? true,
            ];

            $preview = $this->prestacaoService->preview($filtros);

            return $this->json([
                'success' => true,
                'data' => $preview,
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erro ao gerar preview: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Visualizar prestação
     */
    #[Route('/{id}', name: 'app_prestacao_contas_visualizar', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function visualizar(int $id): Response
    {
        $prestacao = $this->prestacaoService->buscarPorId($id);

        if (!$prestacao) {
            $this->addFlash('error', 'Prestação de Contas não encontrada');
            return $this->redirectToRoute('app_prestacao_contas_index');
        }

        return $this->render('prestacao_contas/visualizar.html.twig', [
            'prestacao' => $prestacao,
        ]);
    }

    /**
     * Gerar PDF
     */
    #[Route('/{id}/pdf', name: 'app_prestacao_contas_pdf', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function pdf(int $id): Response
    {
        $prestacao = $this->prestacaoService->buscarPorId($id);

        if (!$prestacao) {
            $this->addFlash('error', 'Prestação de Contas não encontrada');
            return $this->redirectToRoute('app_prestacao_contas_index');
        }

        // Renderizar HTML para PDF
        $html = $this->renderView('prestacao_contas/pdf/extrato.html.twig', [
            'prestacao' => $prestacao,
        ]);

        // Configurar DOMPDF
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $fileName = sprintf(
            'prestacao_contas_%s.pdf',
            $prestacao->getNumeroFormatado()
        );

        return new Response(
            $dompdf->output(),
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $fileName . '"',
            ]
        );
    }

    /**
     * Aprovar prestação
     */
    #[Route('/{id}/aprovar', name: 'app_prestacao_contas_aprovar', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function aprovar(int $id, Request $request): JsonResponse
    {
        $token = $request->headers->get('X-CSRF-Token');
        if (!$this->isCsrfTokenValid('ajax_global', $token)) {
            return $this->json(['success' => false, 'message' => 'Token CSRF inválido'], 403);
        }

        try {
            $prestacao = $this->prestacaoService->aprovarPrestacao($id);

            return $this->json([
                'success' => true,
                'message' => 'Prestação aprovada com sucesso!',
                'status' => $prestacao->getStatus(),
                'statusLabel' => $prestacao->getStatusLabel(),
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Formulário de repasse
     */
    #[Route('/{id}/repasse', name: 'app_prestacao_contas_repasse_form', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function repasseForm(int $id): Response
    {
        $prestacao = $this->prestacaoService->buscarPorId($id);

        if (!$prestacao) {
            $this->addFlash('error', 'Prestação de Contas não encontrada');
            return $this->redirectToRoute('app_prestacao_contas_index');
        }

        if (!$prestacao->podeRegistrarRepasse()) {
            $this->addFlash('error', 'Esta prestação não está aprovada para repasse.');
            return $this->redirectToRoute('app_prestacao_contas_visualizar', ['id' => $id]);
        }

        $form = $this->createForm(PrestacaoContasRepasseType::class);

        return $this->render('prestacao_contas/repasse.html.twig', [
            'prestacao' => $prestacao,
            'form' => $form,
        ]);
    }

    /**
     * Registrar repasse
     */
    #[Route('/{id}/repasse', name: 'app_prestacao_contas_repasse', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function repasse(int $id, Request $request): Response
    {
        $prestacao = $this->prestacaoService->buscarPorId($id);

        if (!$prestacao) {
            $this->addFlash('error', 'Prestação de Contas não encontrada');
            return $this->redirectToRoute('app_prestacao_contas_index');
        }

        $form = $this->createForm(PrestacaoContasRepasseType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $dados = $form->getData();
                $comprovante = $form->get('comprovante')->getData();

                $dadosRepasse = [
                    'data_repasse' => $dados['dataRepasse'],
                    'forma_repasse' => $dados['formaRepasse'],
                    'conta_bancaria' => $dados['contaBancaria']?->getId(),
                    'observacoes' => $dados['observacoes'],
                    'comprovante' => $comprovante,
                ];

                $this->prestacaoService->registrarRepasse($id, $dadosRepasse);

                $this->addFlash('success', 'Repasse registrado com sucesso!');
                return $this->redirectToRoute('app_prestacao_contas_visualizar', ['id' => $id]);

            } catch (\Exception $e) {
                $this->addFlash('error', 'Erro: ' . $e->getMessage());
            }
        }

        return $this->render('prestacao_contas/repasse.html.twig', [
            'prestacao' => $prestacao,
            'form' => $form,
        ]);
    }

    /**
     * Cancelar prestação
     */
    #[Route('/{id}/cancelar', name: 'app_prestacao_contas_cancelar', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function cancelar(int $id, Request $request): JsonResponse
    {
        $token = $request->headers->get('X-CSRF-Token');
        if (!$this->isCsrfTokenValid('ajax_global', $token)) {
            return $this->json(['success' => false, 'message' => 'Token CSRF inválido'], 403);
        }

        try {
            $dados = json_decode($request->getContent(), true);
            $motivo = $dados['motivo'] ?? null;

            $prestacao = $this->prestacaoService->cancelarPrestacao($id, $motivo);

            return $this->json([
                'success' => true,
                'message' => 'Prestação cancelada com sucesso!',
                'status' => $prestacao->getStatus(),
                'statusLabel' => $prestacao->getStatusLabel(),
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Excluir prestação
     */
    #[Route('/{id}', name: 'app_prestacao_contas_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(int $id, Request $request): JsonResponse
    {
        $token = $request->headers->get('X-CSRF-Token');
        if (!$this->isCsrfTokenValid('ajax_global', $token)) {
            return $this->json(['success' => false, 'message' => 'Token CSRF inválido'], 403);
        }

        try {
            $this->prestacaoService->excluirPrestacao($id);

            return $this->json([
                'success' => true,
                'message' => 'Prestação excluída com sucesso!',
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Histórico por proprietário
     */
    #[Route('/historico/{idProprietario}', name: 'app_prestacao_contas_historico', methods: ['GET'], requirements: ['idProprietario' => '\d+'])]
    public function historico(int $idProprietario): Response
    {
        $prestacoes = $this->prestacaoService->getHistoricoPorProprietario($idProprietario);

        return $this->render('prestacao_contas/historico.html.twig', [
            'prestacoes' => $prestacoes,
            'idProprietario' => $idProprietario,
        ]);
    }

    /**
     * Buscar imóveis do proprietário (AJAX)
     */
    #[Route('/imoveis/{idProprietario}', name: 'app_prestacao_contas_imoveis', methods: ['GET'], requirements: ['idProprietario' => '\d+'])]
    public function getImoveis(int $idProprietario): JsonResponse
    {
        $imoveis = $this->prestacaoService->getImoveisDoProprietario($idProprietario);

        $resultado = [];
        foreach ($imoveis as $imovel) {
            $resultado[] = [
                'id' => $imovel->getId(),
                'descricao' => $imovel->getId() . ' - ' . ($imovel->getEndereco() ?? 'Sem endereço'),
            ];
        }

        return $this->json([
            'success' => true,
            'imoveis' => $resultado,
        ]);
    }

    /**
     * Calcular período automaticamente (AJAX)
     */
    #[Route('/calcular-periodo', name: 'app_prestacao_contas_calcular_periodo', methods: ['POST'])]
    public function calcularPeriodo(Request $request): JsonResponse
    {
        try {
            $dados = json_decode($request->getContent(), true);
            $tipoPeriodo = $dados['tipoPeriodo'] ?? PrestacoesContas::PERIODO_MENSAL;
            $dataBase = !empty($dados['dataBase']) ? new \DateTime($dados['dataBase']) : null;

            $periodo = $this->prestacaoService->calcularPeriodo($tipoPeriodo, $dataBase);

            return $this->json([
                'success' => true,
                'dataInicio' => $periodo['inicio']->format('Y-m-d'),
                'dataFim' => $periodo['fim']->format('Y-m-d'),
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
