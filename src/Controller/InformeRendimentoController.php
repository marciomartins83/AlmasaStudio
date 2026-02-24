<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\InformeRendimentoService;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * Controller para Informe de Rendimentos / DIMOB
 * Padrão: Thin Controller - delega toda lógica para o Service
 */
#[Route('/informe-rendimento')]
class InformeRendimentoController extends AbstractController
{
    public function __construct(
        private InformeRendimentoService $informeService,
        private CsrfTokenManagerInterface $csrfTokenManager
    ) {}

    /**
     * Página principal com as 4 abas
     */
    #[Route('/', name: 'app_informe_rendimento_index', methods: ['GET'])]
    public function index(): Response
    {
        $anos = $this->informeService->listarAnosDisponiveis();
        $proprietarios = $this->informeService->listarProprietarios();

        return $this->render('informe_rendimento/index.html.twig', [
            'anos' => $anos,
            'proprietarios' => $proprietarios,
            'anoAtual' => (int) date('Y'),
        ]);
    }

    /**
     * Processa informes de rendimentos
     */
    #[Route('/processar', name: 'app_informe_rendimento_processar', methods: ['POST'])]
    public function processar(Request $request): JsonResponse
    {
        if (!$this->validarCsrf($request)) {
            return $this->json(['success' => false, 'message' => 'Token CSRF inválido'], 403);
        }

        try {
            $dados = json_decode($request->getContent(), true);

            $ano = (int) ($dados['ano'] ?? date('Y'));
            $proprietarioInicial = !empty($dados['proprietarioInicial']) ? (int) $dados['proprietarioInicial'] : null;
            $proprietarioFinal = !empty($dados['proprietarioFinal']) ? (int) $dados['proprietarioFinal'] : null;
            $reprocessar = (bool) ($dados['reprocessar'] ?? false);

            $resultado = $this->informeService->processarInformesAno(
                $ano,
                $proprietarioInicial,
                $proprietarioFinal,
                $reprocessar
            );

            return $this->json([
                'success' => true,
                'processados' => $resultado['processados'],
                'criados' => $resultado['criados'],
                'atualizados' => $resultado['atualizados'],
                'erros' => $resultado['erros'],
                'message' => sprintf(
                    'Processamento concluído: %d registros processados, %d criados, %d atualizados, %d erros',
                    $resultado['processados'],
                    $resultado['criados'],
                    $resultado['atualizados'],
                    $resultado['erros']
                )
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erro ao processar: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Busca informes para aba Manutenção
     */
    #[Route('/manutencao', name: 'app_informe_rendimento_manutencao', methods: ['GET'])]
    public function manutencao(Request $request): JsonResponse
    {
        try {
            $filtros = [
                'ano' => $request->query->getInt('ano', (int) date('Y')),
                'idProprietario' => $request->query->get('proprietario'),
                'codigoImovel' => $request->query->get('imovel'),
                'nomeInquilino' => $request->query->get('inquilino'),
                'status' => $request->query->get('status'),
            ];

            // Remover filtros vazios
            $filtros = array_filter($filtros, fn($v) => $v !== null && $v !== '');

            $informes = $this->informeService->buscarInformesComFiltros($filtros);

            return $this->json([
                'success' => true,
                'informes' => $informes,
                'total' => count($informes)
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erro ao buscar informes: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Atualiza um informe específico
     */
    #[Route('/informe/{id}', name: 'app_informe_rendimento_atualizar', methods: ['PUT'])]
    public function atualizar(int $id, Request $request): JsonResponse
    {
        if (!$this->validarCsrf($request)) {
            return $this->json(['success' => false, 'message' => 'Token CSRF inválido'], 403);
        }

        try {
            $dados = json_decode($request->getContent(), true);

            $informe = $this->informeService->atualizarInforme($id, $dados);

            return $this->json([
                'success' => true,
                'message' => 'Informe atualizado com sucesso',
                'id' => $informe->getId()
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erro ao atualizar: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Impressão de informes (PDF)
     */
    #[Route('/impressao', name: 'app_informe_rendimento_impressao', methods: ['GET'])]
    public function impressao(Request $request): Response
    {
        try {
            $ano = $request->query->getInt('ano', (int) date('Y'));
            $modelo = $request->query->getInt('modelo', 1);
            $proprietario = $request->query->get('proprietario');
            $abaterTaxa = $request->query->getBoolean('abaterTaxa', false);

            $idProprietario = !empty($proprietario) ? (int) $proprietario : null;

            if ($modelo === 2) {
                $dados = $this->informeService->gerarDadosPdfModelo2($ano, $idProprietario, $abaterTaxa);
            } else {
                $dados = $this->informeService->gerarDadosPdfModelo1($ano, $idProprietario, $abaterTaxa);
            }

            $html = $this->renderView('informe_rendimento/impressao.html.twig', [
                'ano' => $ano,
                'modelo' => $modelo,
                'dados' => $dados,
                'abaterTaxa' => $abaterTaxa,
            ]);

            $options = new Options();
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isPhpEnabled', false);
            $options->set('defaultFont', 'DejaVu Sans');

            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            $filename = sprintf('informe-rendimento-%d-modelo%d.pdf', $ano, $modelo);

            return new Response($dompdf->output(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $filename . '"',
            ]);

        } catch (\Exception $e) {
            $this->addFlash('error', 'Erro ao gerar PDF: ' . $e->getMessage());
            return $this->redirectToRoute('app_informe_rendimento_index');
        }
    }

    /**
     * Busca configuração DIMOB
     */
    #[Route('/dimob', name: 'app_informe_rendimento_dimob_get', methods: ['GET'])]
    public function getDimob(Request $request): JsonResponse
    {
        try {
            $ano = $request->query->getInt('ano', (int) date('Y'));
            $config = $this->informeService->buscarDimobPorAno($ano);

            return $this->json([
                'success' => true,
                'data' => $config ? [
                    'id' => $config->getId(),
                    'ano' => $config->getAno(),
                    'cnpjDeclarante' => $config->getCnpjDeclarante(),
                    'cpfResponsavel' => $config->getCpfResponsavel(),
                    'codigoCidade' => $config->getCodigoCidade(),
                    'declaracaoRetificadora' => $config->isDeclaracaoRetificadora(),
                    'situacaoEspecial' => $config->isSituacaoEspecial(),
                    'dataGeracao' => $config->getDataGeracao()?->format('d/m/Y H:i:s'),
                ] : null
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erro ao buscar configuração: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Salva configuração DIMOB
     */
    #[Route('/dimob', name: 'app_informe_rendimento_dimob_salvar', methods: ['POST'])]
    public function salvarDimob(Request $request): JsonResponse
    {
        if (!$this->validarCsrf($request)) {
            return $this->json(['success' => false, 'message' => 'Token CSRF inválido'], 403);
        }

        try {
            $dados = json_decode($request->getContent(), true);

            // Validar campos obrigatórios
            if (empty($dados['cnpjDeclarante']) || empty($dados['cpfResponsavel']) || empty($dados['codigoCidade'])) {
                return $this->json([
                    'success' => false,
                    'message' => 'Preencha todos os campos obrigatórios'
                ], 400);
            }

            $config = $this->informeService->salvarDimobConfiguracao($dados);

            return $this->json([
                'success' => true,
                'message' => 'Configuração salva com sucesso',
                'id' => $config->getId()
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erro ao salvar: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Gera arquivo DIMOB para download
     */
    #[Route('/dimob/gerar', name: 'app_informe_rendimento_dimob_gerar', methods: ['GET'])]
    public function gerarDimob(Request $request): Response
    {
        try {
            $ano = $request->query->getInt('ano', (int) date('Y'));
            $proprietarioInicial = $request->query->get('proprietarioInicial');
            $proprietarioFinal = $request->query->get('proprietarioFinal');

            $propInicial = !empty($proprietarioInicial) ? (int) $proprietarioInicial : null;
            $propFinal = !empty($proprietarioFinal) ? (int) $proprietarioFinal : null;

            $conteudo = $this->informeService->gerarArquivoDimob($ano, $propInicial, $propFinal);

            $response = new Response($conteudo);

            $disposition = $response->headers->makeDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                sprintf('DIMOB_%d.txt', $ano)
            );

            $response->headers->set('Content-Type', 'text/plain; charset=ISO-8859-1');
            $response->headers->set('Content-Disposition', $disposition);

            return $response;

        } catch (\Exception $e) {
            $this->addFlash('error', 'Erro ao gerar DIMOB: ' . $e->getMessage());
            return $this->redirectToRoute('app_informe_rendimento_index');
        }
    }

    /**
     * Valida token CSRF
     */
    private function validarCsrf(Request $request): bool
    {
        $token = $request->headers->get('X-CSRF-Token');

        if ($token === null) {
            return false;
        }

        return $this->csrfTokenManager->isTokenValid(new CsrfToken('ajax_global', $token));
    }
}
