<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\SearchFilterDTO;
use App\DTO\SortOptionDTO;
use App\Entity\Boletos;
use App\Form\BoletoType;
use App\Repository\BoletosRepository;
use App\Repository\ConfiguracoesApiBancoRepository;
use App\Service\BoletoSantanderService;
use App\Service\PaginationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/boleto')]
#[IsGranted('ROLE_USER')]
class BoletoController extends AbstractController
{
    public function __construct(
        private BoletoSantanderService $boletoService,
        private BoletosRepository $boletosRepository,
        private ConfiguracoesApiBancoRepository $configRepository,
        private PaginationService $paginator
    ) {}

    /**
     * Listagem de boletos com filtros
     */
    #[Route('/', name: 'app_boleto_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $qb = $this->boletosRepository->createBaseQueryBuilder();

        $filters = [
            new SearchFilterDTO('nossoNumero', 'Nosso Numero', 'text', 'b.nossoNumero', 'LIKE', [], 'Buscar...', 2),
            new SearchFilterDTO('status', 'Status', 'select', 'b.status', 'EXACT', [
                Boletos::STATUS_PENDENTE   => 'Pendente',
                Boletos::STATUS_REGISTRADO => 'Registrado',
                Boletos::STATUS_PAGO       => 'Pago',
                Boletos::STATUS_VENCIDO    => 'Vencido',
                Boletos::STATUS_BAIXADO    => 'Baixado',
                Boletos::STATUS_PROTESTADO => 'Protestado',
                Boletos::STATUS_ERRO       => 'Erro',
            ], null, 2),
            new SearchFilterDTO('vencimentoDe', 'Venc. De', 'date', 'b.dataVencimento', 'GTE', [], null, 2),
            new SearchFilterDTO('vencimentoAte', 'Venc. Ate', 'date', 'b.dataVencimento', 'LTE', [], null, 2),
        ];

        $sortOptions = [
            new SortOptionDTO('dataVencimento', 'Vencimento', 'DESC'),
            new SortOptionDTO('valorNominal', 'Valor', 'DESC'),
            new SortOptionDTO('dataEmissao', 'Emissao', 'DESC'),
            new SortOptionDTO('status', 'Status', 'ASC'),
        ];

        $pagination = $this->paginator->paginate($qb, $request, null, [], 'b.id', $filters, $sortOptions, 'dataVencimento', 'DESC');

        // Estatísticas
        $estatisticas = $this->boletoService->getEstatisticas();

        // Configurações para filtro
        $configuracoes = $this->configRepository->findBy(['ativo' => true]);

        return $this->render('boleto/index.html.twig', [
            'boletos'      => $pagination['items'],
            'estatisticas' => $estatisticas,
            'configuracoes' => $configuracoes,
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
     * Detalhes do boleto
     */
    #[Route('/{id}', name: 'app_boleto_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id): Response
    {
        $dados = $this->boletoService->buscarPorId($id);

        if (!$dados) {
            throw $this->createNotFoundException('Boleto não encontrado');
        }

        return $this->render('boleto/show.html.twig', [
            'boleto' => $dados['boleto'],
            'logs' => $dados['logs'],
            'pagador' => $dados['pagador'],
            'configuracao' => $dados['configuracao'],
        ]);
    }

    /**
     * Formulário de novo boleto
     */
    #[Route('/new', name: 'app_boleto_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $boleto = new Boletos();

        // Definir valores padrão
        $boleto->setDataVencimento((new \DateTime())->modify('+30 days'));
        $boleto->setTipoDesconto(Boletos::DESCONTO_ISENTO);
        $boleto->setTipoJuros(Boletos::JUROS_ISENTO);
        $boleto->setTipoMulta(Boletos::MULTA_ISENTO);

        $form = $this->createForm(BoletoType::class, $boleto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // O service vai gerar o nosso número e salvar
                $config = $boleto->getConfiguracaoApi();
                $boleto->setNossoNumero($this->boletoService->gerarNossoNumero($config));
                $boleto->setDataEmissao(new \DateTime());
                $boleto->setStatus(Boletos::STATUS_PENDENTE);

                $this->boletosRepository->save($boleto, true);

                // Verificar se deve registrar imediatamente
                $registrarAgora = $request->request->get('registrar_agora') === '1';

                if ($registrarAgora) {
                    $resultado = $this->boletoService->registrarBoleto($boleto);
                    if ($resultado['sucesso']) {
                        $this->addFlash('success', 'Boleto criado e registrado com sucesso!');
                    } else {
                        $this->addFlash('warning', 'Boleto criado, mas erro ao registrar: ' . $resultado['mensagem']);
                    }
                } else {
                    $this->addFlash('success', 'Boleto criado com sucesso!');
                }

                return $this->redirectToRoute('app_boleto_show', ['id' => $boleto->getId()]);

            } catch (\Exception $e) {
                $this->addFlash('error', 'Erro ao criar boleto: ' . $e->getMessage());
            }
        }

        return $this->render('boleto/new.html.twig', [
            'form' => $form->createView(),
            'boleto' => $boleto,
        ]);
    }

    /**
     * Registrar boleto na API (AJAX)
     */
    #[Route('/{id}/registrar', name: 'app_boleto_registrar', methods: ['POST'])]
    public function registrar(Request $request, int $id): JsonResponse
    {
        if (!$this->isCsrfTokenValid('ajax_global', $request->headers->get('X-CSRF-Token'))) {
            return new JsonResponse(['success' => false, 'message' => 'Token CSRF inválido'], 403);
        }

        $boleto = $this->boletosRepository->find($id);

        if (!$boleto) {
            return new JsonResponse(['success' => false, 'message' => 'Boleto não encontrado'], 404);
        }

        $resultado = $this->boletoService->registrarBoleto($boleto);

        return new JsonResponse([
            'success' => $resultado['sucesso'],
            'message' => $resultado['mensagem'],
            'status' => $boleto->getStatus(),
            'statusLabel' => $boleto->getStatusLabel(),
            'statusClass' => $boleto->getStatusClass(),
            'codigoBarras' => $boleto->getCodigoBarras(),
            'linhaDigitavel' => $boleto->getLinhaDigitavel(),
        ]);
    }

    /**
     * Consultar status do boleto na API (AJAX)
     */
    #[Route('/{id}/consultar', name: 'app_boleto_consultar', methods: ['POST'])]
    public function consultar(Request $request, int $id): JsonResponse
    {
        if (!$this->isCsrfTokenValid('ajax_global', $request->headers->get('X-CSRF-Token'))) {
            return new JsonResponse(['success' => false, 'message' => 'Token CSRF inválido'], 403);
        }

        $boleto = $this->boletosRepository->find($id);

        if (!$boleto) {
            return new JsonResponse(['success' => false, 'message' => 'Boleto não encontrado'], 404);
        }

        $resultado = $this->boletoService->consultarBoleto($boleto);

        return new JsonResponse([
            'success' => $resultado['sucesso'],
            'message' => $resultado['mensagem'],
            'status' => $boleto->getStatus(),
            'statusLabel' => $boleto->getStatusLabel(),
            'statusClass' => $boleto->getStatusClass(),
            'dados' => $resultado['dados'] ?? null,
        ]);
    }

    /**
     * Baixar/cancelar boleto (AJAX)
     */
    #[Route('/{id}/baixar', name: 'app_boleto_baixar', methods: ['POST'])]
    public function baixar(Request $request, int $id): JsonResponse
    {
        if (!$this->isCsrfTokenValid('ajax_global', $request->headers->get('X-CSRF-Token'))) {
            return new JsonResponse(['success' => false, 'message' => 'Token CSRF inválido'], 403);
        }

        $boleto = $this->boletosRepository->find($id);

        if (!$boleto) {
            return new JsonResponse(['success' => false, 'message' => 'Boleto não encontrado'], 404);
        }

        $data = json_decode($request->getContent(), true);
        $motivo = $data['motivo'] ?? 'SOLICITACAO_BENEFICIARIO';

        $resultado = $this->boletoService->baixarBoleto($boleto, $motivo);

        return new JsonResponse([
            'success' => $resultado['sucesso'],
            'message' => $resultado['mensagem'],
            'status' => $boleto->getStatus(),
            'statusLabel' => $boleto->getStatusLabel(),
            'statusClass' => $boleto->getStatusClass(),
        ]);
    }

    /**
     * Excluir boleto (AJAX) - apenas se PENDENTE
     */
    #[Route('/{id}', name: 'app_boleto_delete', methods: ['DELETE'])]
    public function delete(Request $request, int $id): JsonResponse
    {
        if (!$this->isCsrfTokenValid('ajax_global', $request->headers->get('X-CSRF-Token'))) {
            return new JsonResponse(['success' => false, 'message' => 'Token CSRF inválido'], 403);
        }

        $resultado = $this->boletoService->deletarBoleto($id);

        return new JsonResponse([
            'success' => $resultado['sucesso'],
            'message' => $resultado['mensagem'],
        ]);
    }

    /**
     * Imprimir boleto (gerar PDF/HTML para impressão)
     */
    #[Route('/{id}/imprimir', name: 'app_boleto_imprimir', methods: ['GET'])]
    public function imprimir(int $id): Response
    {
        $dados = $this->boletoService->buscarPorId($id);

        if (!$dados) {
            throw $this->createNotFoundException('Boleto não encontrado');
        }

        $boleto = $dados['boleto'];

        if (!$boleto->isRegistrado() && $boleto->getStatus() !== Boletos::STATUS_PAGO) {
            $this->addFlash('warning', 'Boleto ainda não foi registrado no banco');
            return $this->redirectToRoute('app_boleto_show', ['id' => $id]);
        }

        return $this->render('boleto/_imprimir.html.twig', [
            'boleto' => $boleto,
            'pagador' => $dados['pagador'],
            'configuracao' => $dados['configuracao'],
        ]);
    }

    /**
     * Segunda via do boleto
     */
    #[Route('/{id}/segunda-via', name: 'app_boleto_segunda_via', methods: ['GET'])]
    public function segundaVia(int $id): Response
    {
        // Por enquanto, redireciona para imprimir
        return $this->redirectToRoute('app_boleto_imprimir', ['id' => $id]);
    }

    /**
     * Registrar múltiplos boletos (AJAX)
     */
    #[Route('/registrar-lote', name: 'app_boleto_registrar_lote', methods: ['POST'])]
    public function registrarLote(Request $request): JsonResponse
    {
        if (!$this->isCsrfTokenValid('ajax_global', $request->headers->get('X-CSRF-Token'))) {
            return new JsonResponse(['success' => false, 'message' => 'Token CSRF inválido'], 403);
        }

        $data = json_decode($request->getContent(), true);
        $ids = $data['ids'] ?? [];

        if (empty($ids)) {
            return new JsonResponse(['success' => false, 'message' => 'Nenhum boleto selecionado'], 400);
        }

        $resultado = $this->boletoService->registrarLotePorIds($ids);

        return new JsonResponse([
            'success' => true,
            'message' => sprintf(
                'Processados %d boletos: %d sucesso, %d erro',
                $resultado['total'],
                $resultado['sucesso'],
                $resultado['erro']
            ),
            'total' => $resultado['total'],
            'sucesso' => $resultado['sucesso'],
            'erro' => $resultado['erro'],
            'detalhes' => $resultado['detalhes'],
        ]);
    }

    /**
     * Consultar múltiplos boletos (AJAX)
     */
    #[Route('/consultar-lote', name: 'app_boleto_consultar_lote', methods: ['POST'])]
    public function consultarLote(Request $request): JsonResponse
    {
        if (!$this->isCsrfTokenValid('ajax_global', $request->headers->get('X-CSRF-Token'))) {
            return new JsonResponse(['success' => false, 'message' => 'Token CSRF inválido'], 403);
        }

        $data = json_decode($request->getContent(), true);
        $ids = $data['ids'] ?? [];

        if (empty($ids)) {
            return new JsonResponse(['success' => false, 'message' => 'Nenhum boleto selecionado'], 400);
        }

        $resultado = $this->boletoService->consultarLotePorIds($ids);

        return new JsonResponse([
            'success' => true,
            'message' => sprintf(
                'Consultados %d boletos: %d sucesso, %d erro',
                $resultado['total'],
                $resultado['sucesso'],
                $resultado['erro']
            ),
            'total' => $resultado['total'],
            'sucesso' => $resultado['sucesso'],
            'erro' => $resultado['erro'],
            'detalhes' => $resultado['detalhes'],
        ]);
    }

    /**
     * API: Retorna estatísticas de boletos (AJAX)
     */
    #[Route('/api/estatisticas', name: 'app_boleto_api_estatisticas', methods: ['GET'])]
    public function apiEstatisticas(): JsonResponse
    {
        $estatisticas = $this->boletoService->getEstatisticas();

        return new JsonResponse($estatisticas);
    }
}
