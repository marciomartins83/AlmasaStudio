<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\ContratosCobrancas;
use App\Repository\ContratosCobrancasRepository;
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
        private ContratosCobrancasRepository $cobrancasRepo
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
        $mostrarAutomaticos = $request->query->getBoolean('mostrar_automaticos', false);
        $status = $request->query->all('status');

        // Paginação
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;

        // Montar filtros
        $filtros = [];

        if ($dataVencimentoStr) {
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

        // Estatísticas para o dia
        $estatisticas = $this->cobrancaService->getEstatisticas(
            $filtros['data_vencimento'] ?? null
        );

        // Contagem por tipo de envio
        $contagemTipoEnvio = $this->cobrancasRepo->contarPorTipoEnvio(
            $filtros['data_vencimento'] ?? new \DateTime()
        );

        // Status disponíveis para filtro
        $statusOptions = ContratosCobrancas::getStatusDisponiveis();

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
            'dataVencimento' => $filtros['data_vencimento'] ?? new \DateTime(),
            'queryParams' => $request->query->all(),
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

        // Buscar histórico de emails
        $emailService = $this->container->get('App\Service\EmailService');
        $historicoEmails = method_exists($emailService, 'getHistoricoByReferencia')
            ? $emailService->getHistoricoByReferencia('COBRANCA', $id)
            : [];

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
