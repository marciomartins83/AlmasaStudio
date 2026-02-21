<?php

namespace App\Controller;

use App\DTO\SearchFilterDTO;
use App\DTO\SortOptionDTO;
use App\Entity\ConfiguracoesApiBanco;
use App\Form\ConfiguracaoApiBancoType;
use App\Repository\ConfiguracoesApiBancoRepository;
use App\Repository\ContasBancariasRepository;
use App\Service\ConfiguracaoApiBancoService;
use App\Service\PaginationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/configuracao-api-banco', name: 'app_configuracao_api_banco_')]
class ConfiguracaoApiBancoController extends AbstractController
{
    public function __construct(
        private ConfiguracaoApiBancoService $service
    ) {}

    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(ConfiguracoesApiBancoRepository $configuracaoRepository, PaginationService $paginator, Request $request): Response
    {
        $qb = $configuracaoRepository->createQueryBuilder('c')
            ->orderBy('c.id', 'DESC');

        $filters = [
            new SearchFilterDTO('convenio', 'Convênio', 'text', 'c.convenio', 'LIKE', [], 'Convênio...', 3),
            new SearchFilterDTO('ambiente', 'Ambiente', 'select', 'c.ambiente', 'EQ', [
                'sandbox'  => 'Sandbox',
                'producao' => 'Produção',
            ], null, 3),
            new SearchFilterDTO('ativo', 'Ativo', 'boolean', 'c.ativo', 'EQ', [], null, 2),
        ];
        $sortOptions = [
            new SortOptionDTO('id', 'ID', 'DESC'),
            new SortOptionDTO('convenio', 'Convênio'),
            new SortOptionDTO('ambiente', 'Ambiente'),
        ];

        $pagination = $paginator->paginate($qb, $request, null, ['c.convenio', 'c.ambiente'], null, $filters, $sortOptions, 'id', 'DESC');

        return $this->render('configuracao_api_banco/index.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $configuracao = new ConfiguracoesApiBanco();
        $configuracao->setAtivo(true);

        $form = $this->createForm(ConfiguracaoApiBancoType::class, $configuracao);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $contaBancariaId = $configuracao->getContaBancaria()->getId();
                $ambiente = $configuracao->getAmbiente();

                if ($this->service->existeConfiguracaoDuplicada($contaBancariaId, $ambiente)) {
                    $this->addFlash('error', 'Já existe uma configuração para esta conta bancária neste ambiente.');
                    return $this->render('configuracao_api_banco/new.html.twig', [
                        'configuracao' => $configuracao,
                        'form' => $form,
                    ]);
                }

                $certificadoArquivo = $form->get('certificadoArquivo')->getData();
                $this->service->salvar($configuracao, $certificadoArquivo);

                $this->addFlash('success', 'Configuração de API criada com sucesso!');
                return $this->redirectToRoute('app_configuracao_api_banco_index');

            } catch (\Exception $e) {
                $this->addFlash('error', 'Erro ao criar configuração: ' . $e->getMessage());
            }
        }

        return $this->render('configuracao_api_banco/new.html.twig', [
            'configuracao' => $configuracao,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, int $id): Response
    {
        $configuracao = $this->service->buscarPorId($id);

        if (!$configuracao) {
            throw $this->createNotFoundException('Configuração não encontrada');
        }

        $form = $this->createForm(ConfiguracaoApiBancoType::class, $configuracao);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $contaBancariaId = $configuracao->getContaBancaria()->getId();
                $ambiente = $configuracao->getAmbiente();

                if ($this->service->existeConfiguracaoDuplicada($contaBancariaId, $ambiente, $id)) {
                    $this->addFlash('error', 'Já existe outra configuração para esta conta bancária neste ambiente.');
                    return $this->render('configuracao_api_banco/edit.html.twig', [
                        'configuracao' => $configuracao,
                        'form' => $form,
                    ]);
                }

                $certificadoArquivo = $form->get('certificadoArquivo')->getData();
                $this->service->salvar($configuracao, $certificadoArquivo);

                $this->addFlash('success', 'Configuração atualizada com sucesso!');
                return $this->redirectToRoute('app_configuracao_api_banco_index');

            } catch (\Exception $e) {
                $this->addFlash('error', 'Erro ao atualizar: ' . $e->getMessage());
            }
        }

        return $this->render('configuracao_api_banco/edit.html.twig', [
            'configuracao' => $configuracao,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(Request $request, int $id): JsonResponse
    {
        if (!$this->isCsrfTokenValid('ajax_global', $request->headers->get('X-CSRF-Token'))) {
            return new JsonResponse(['success' => false, 'message' => 'Token CSRF inválido'], 403);
        }

        try {
            $resultado = $this->service->deletar($id);

            if ($resultado) {
                return new JsonResponse(['success' => true, 'message' => 'Configuração excluída com sucesso']);
            }

            return new JsonResponse(['success' => false, 'message' => 'Configuração não encontrada'], 404);

        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    #[Route('/{id}/testar-conexao', name: 'testar_conexao', methods: ['POST'])]
    public function testarConexao(Request $request, int $id): JsonResponse
    {
        if (!$this->isCsrfTokenValid('ajax_global', $request->headers->get('X-CSRF-Token'))) {
            return new JsonResponse(['success' => false, 'message' => 'Token CSRF inválido'], 403);
        }

        $configuracao = $this->service->buscarPorId($id);

        if (!$configuracao) {
            return new JsonResponse(['success' => false, 'message' => 'Configuração não encontrada'], 404);
        }

        $resultado = [
            'success' => true,
            'message' => 'Verificação de configuração concluída',
            'detalhes' => []
        ];

        if (empty($configuracao->getClientId()) || empty($configuracao->getClientSecret())) {
            $resultado['detalhes']['credenciais'] = [
                'status' => 'warning',
                'mensagem' => 'Client ID ou Client Secret não configurados'
            ];
        } else {
            $resultado['detalhes']['credenciais'] = [
                'status' => 'ok',
                'mensagem' => 'Credenciais configuradas'
            ];
        }

        if (empty($configuracao->getCertificadoPath())) {
            $resultado['detalhes']['certificado'] = [
                'status' => 'warning',
                'mensagem' => 'Certificado não enviado'
            ];
        } elseif (!$configuracao->isCertificadoValido()) {
            $resultado['detalhes']['certificado'] = [
                'status' => 'error',
                'mensagem' => 'Certificado expirado em ' . $configuracao->getCertificadoValidade()?->format('d/m/Y')
            ];
            $resultado['success'] = false;
        } else {
            $resultado['detalhes']['certificado'] = [
                'status' => 'ok',
                'mensagem' => 'Certificado válido até ' . $configuracao->getCertificadoValidade()?->format('d/m/Y')
            ];
        }

        $resultado['detalhes']['ambiente'] = [
            'status' => 'info',
            'mensagem' => 'Ambiente: ' . ($configuracao->getAmbiente() === 'producao' ? 'Produção' : 'Sandbox')
        ];

        $resultado['detalhes']['urls'] = [
            'autenticacao' => $configuracao->getUrlAutenticacao(),
            'api' => $configuracao->getUrlApi()
        ];

        return new JsonResponse($resultado);
    }

    #[Route('/api/contas-por-banco/{bancoId}', name: 'api_contas_por_banco', methods: ['GET'])]
    public function contasPorBanco(int $bancoId, ContasBancariasRepository $contasRepository): JsonResponse
    {
        $contas = $contasRepository->findBy(['idBanco' => $bancoId, 'ativo' => true]);

        $resultado = [];
        foreach ($contas as $conta) {
            $resultado[] = [
                'id' => $conta->getId(),
                'codigo' => $conta->getCodigo(),
                'digito' => $conta->getDigitoConta(),
                'titular' => $conta->getTitular(),
                'label' => sprintf('%s-%s (%s)',
                    $conta->getCodigo(),
                    $conta->getDigitoConta() ?? '0',
                    $conta->getTitular() ?? 'Sem titular'
                )
            ];
        }

        return new JsonResponse($resultado);
    }
}
