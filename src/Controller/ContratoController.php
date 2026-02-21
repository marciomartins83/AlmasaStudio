<?php

namespace App\Controller;

use App\Entity\ImoveisContratos;
use App\Repository\ImoveisContratosRepository;
use App\Service\ContratoService;
use App\Service\PaginationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;

/**
 * ContratoController - Thin Controller
 * Apenas recebe Request, valida formulário, chama Service e retorna Response
 *
 * PROIBIDO:
 * - Lógica de negócio
 * - Transações (beginTransaction, commit, rollBack)
 * - Operações de persistência (persist, flush, remove)
 * - Consultas DQL complexas
 */
#[Route('/contrato', name: 'app_contrato_')]
class ContratoController extends AbstractController
{
    private ContratoService $contratoService;
    private LoggerInterface $logger;

    public function __construct(
        ContratoService $contratoService,
        LoggerInterface $logger
    ) {
        $this->contratoService = $contratoService;
        $this->logger = $logger;
    }

    /**
     * Lista todos os contratos com filtros
     */
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(Request $request, ImoveisContratosRepository $contratosRepository, PaginationService $paginator): Response
    {
        $filtros = [
            'status' => $request->query->get('status'),
            'tipoContrato' => $request->query->get('tipo_contrato'),
            'idImovel' => $request->query->get('imovel_id'),
            'idLocatario' => $request->query->get('locatario_id'),
            'ativo' => $request->query->get('ativo'),
        ];

        // Remove filtros vazios
        $filtros = array_filter($filtros, fn($valor) => $valor !== null && $valor !== '');

        $qb = $contratosRepository->createQueryBuilder('c')
            ->orderBy('c.id', 'DESC');

        $pagination = $paginator->paginate($qb, $request, null, ['c.observacoes']);

        // Delega para Service
        $estatisticas = $this->contratoService->obterEstatisticas();

        return $this->render('contrato/index.html.twig', [
            'pagination' => $pagination,
            'estatisticas' => $estatisticas,
            'filtros' => $filtros,
        ]);
    }

    /**
     * Exibe detalhes de um contrato
     */
    #[Route('/show/{id}', name: 'show', methods: ['GET'])]
    public function show(int $id): Response
    {
        // Delega para Service
        $contrato = $this->contratoService->buscarContratoPorId($id);

        if (!$contrato) {
            $this->addFlash('error', 'Contrato não encontrado.');
            return $this->redirectToRoute('app_contrato_index');
        }

        return $this->render('contrato/show.html.twig', [
            'contrato' => $contrato,
        ]);
    }

    /**
     * Cadastro de novo contrato
     */
    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            try {
                $dados = $request->request->all();
                $contrato = new ImoveisContratos();

                // Delega para Service
                $this->contratoService->salvarContrato($contrato, $dados);

                $this->addFlash('success', 'Contrato cadastrado com sucesso!');
                return $this->redirectToRoute('app_contrato_index');
            } catch (\Exception $e) {
                $this->logger->error('Erro ao salvar contrato: ' . $e->getMessage());
                $this->addFlash('error', 'Erro ao salvar contrato: ' . $e->getMessage());
            }
        }

        // Delega para Service
        $imoveisDisponiveis = $this->contratoService->listarImoveisDisponiveis();
        $locatarios = $this->contratoService->listarLocatarios();
        $fiadores = $this->contratoService->listarFiadores();

        return $this->render('contrato/form.html.twig', [
            'contrato' => null,
            'imoveisDisponiveis' => $imoveisDisponiveis,
            'locatarios' => $locatarios,
            'fiadores' => $fiadores,
        ]);
    }

    /**
     * Edição de contrato existente
     */
    #[Route('/edit/{id}', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ImoveisContratos $contrato): Response
    {
        if ($request->isMethod('POST')) {
            try {
                $dados = $request->request->all();

                // Delega para Service
                $this->contratoService->atualizarContrato($contrato, $dados);

                $this->addFlash('success', 'Contrato atualizado com sucesso!');
                return $this->redirectToRoute('app_contrato_index');
            } catch (\Exception $e) {
                $this->logger->error('Erro ao atualizar contrato: ' . $e->getMessage());
                $this->addFlash('error', 'Erro ao atualizar contrato: ' . $e->getMessage());
            }
        }

        // Delega para Service
        $contratoEnriquecido = $this->contratoService->buscarContratoPorId($contrato->getId());
        $imoveisDisponiveis = $this->contratoService->listarImoveisDisponiveis();
        $locatarios = $this->contratoService->listarLocatarios();
        $fiadores = $this->contratoService->listarFiadores();

        return $this->render('contrato/form.html.twig', [
            'contrato' => $contratoEnriquecido,
            'imoveisDisponiveis' => $imoveisDisponiveis,
            'locatarios' => $locatarios,
            'fiadores' => $fiadores,
        ]);
    }

    /**
     * Encerra contrato (AJAX)
     */
    #[Route('/encerrar/{id}', name: 'encerrar', methods: ['POST'])]
    public function encerrar(Request $request, int $id): JsonResponse
    {
        try {
            $dados = json_decode($request->getContent(), true);
            $dataEncerramento = new \DateTime($dados['data_encerramento'] ?? 'now');
            $motivo = $dados['motivo'] ?? null;

            // Delega para Service
            $this->contratoService->encerrarContrato($id, $dataEncerramento, $motivo);

            return new JsonResponse([
                'success' => true,
                'message' => 'Contrato encerrado com sucesso.',
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erro ao encerrar contrato: ' . $e->getMessage());
            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Renova contrato (AJAX)
     */
    #[Route('/renovar/{id}', name: 'renovar', methods: ['POST'])]
    public function renovar(Request $request, int $id): JsonResponse
    {
        try {
            $dados = json_decode($request->getContent(), true);

            // Delega para Service
            $novoContrato = $this->contratoService->renovarContrato($id, $dados);

            return new JsonResponse([
                'success' => true,
                'message' => 'Contrato renovado com sucesso.',
                'novo_contrato_id' => $novoContrato->getId(),
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erro ao renovar contrato: ' . $e->getMessage());
            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Busca contratos próximos ao vencimento (AJAX)
     */
    #[Route('/vencimento-proximo', name: 'vencimento_proximo', methods: ['GET'])]
    public function vencimentoProximo(Request $request): JsonResponse
    {
        try {
            $dias = (int) $request->query->get('dias', 30);

            // Delega para Service
            $contratos = $this->contratoService->buscarContratosVencimentoProximo($dias);

            return new JsonResponse([
                'success' => true,
                'contratos' => $contratos,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erro ao buscar contratos: ' . $e->getMessage());
            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Busca contratos que precisam de reajuste (AJAX)
     */
    #[Route('/para-reajuste', name: 'para_reajuste', methods: ['GET'])]
    public function paraReajuste(): JsonResponse
    {
        try {
            // Delega para Service
            $contratos = $this->contratoService->buscarContratosParaReajuste();

            return new JsonResponse([
                'success' => true,
                'contratos' => $contratos,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erro ao buscar contratos: ' . $e->getMessage());
            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtém estatísticas (AJAX)
     */
    #[Route('/estatisticas', name: 'estatisticas', methods: ['GET'])]
    public function estatisticas(): JsonResponse
    {
        try {
            // Delega para Service
            $estatisticas = $this->contratoService->obterEstatisticas();

            return new JsonResponse([
                'success' => true,
                'estatisticas' => $estatisticas,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erro ao obter estatísticas: ' . $e->getMessage());
            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Lista imóveis disponíveis (AJAX)
     */
    #[Route('/imoveis-disponiveis', name: 'imoveis_disponiveis', methods: ['GET'])]
    public function imoveisDisponiveis(): JsonResponse
    {
        try {
            // Delega para Service
            $imoveis = $this->contratoService->listarImoveisDisponiveis();

            return new JsonResponse([
                'success' => true,
                'imoveis' => $imoveis,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erro ao listar imóveis: ' . $e->getMessage());
            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
