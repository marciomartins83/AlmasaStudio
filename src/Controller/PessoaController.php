<?php

namespace App\Controller;

use App\Entity\Pessoas;
use App\Form\PessoaFormType;
use App\Repository\PessoaRepository;
use App\Service\PessoaService;
use App\Service\CepService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;

#[Route('/pessoa', name: 'app_pessoa_')]
class PessoaController extends AbstractController
{
    private PessoaService $pessoaService;
    private LoggerInterface $logger;

    public function __construct(
        PessoaService $pessoaService,
        LoggerInterface $logger
    ) {
        $this->pessoaService = $pessoaService;
        $this->logger = $logger;
    }

    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(PessoaRepository $pessoaRepository): Response
    {
        return $this->render('pessoa/index.html.twig', [
            'pessoas' => $pessoaRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $form = $this->createForm(PessoaFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $pessoa = $form->getData();
                $requestData = $request->request->all();
                $formData = $requestData['pessoa_form'] ?? $requestData;
                $tipoPessoa = $form->get('tipoPessoa')->getData();

                $this->pessoaService->criarPessoa($pessoa, $formData, $tipoPessoa);
                
                $this->addFlash('success', 'Pessoa cadastrada com sucesso!');
                return $this->redirectToRoute('app_pessoa_index');
                
            } catch (\RuntimeException $e) {
                $this->addFlash('error', $e->getMessage());
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erro ao salvar a pessoa: ' . $e->getMessage());
            }
        }

        return $this->render('pessoa/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/_subform', name: '_subform', methods: ['POST'])]
    public function _subform(Request $request): Response
    {
        $tipo = $request->get('tipo');

        if (!$tipo) {
            return new Response('', 204);
        }

        $tiposSemCampos = ['contratante', 'corretora'];
        if (in_array($tipo, $tiposSemCampos)) {
            return new Response('', 204);
        }

        $subFormType = null;
        $template = null;

        switch ($tipo) {
            case 'fiador':
                $subFormType = \App\Form\PessoaFiadorType::class;
                $template = 'pessoa/partials/fiador.html.twig';
                break;
            case 'corretor':
                $subFormType = \App\Form\PessoaCorretorType::class;
                $template = 'pessoa/partials/corretor.html.twig';
                break;
            case 'locador':
                $subFormType = \App\Form\PessoaLocadorType::class;
                $template = 'pessoa/partials/locador.html.twig';
                break;
            case 'pretendente':
                $subFormType = \App\Form\PessoaPretendenteType::class;
                $template = 'pessoa/partials/pretendente.html.twig';
                break;
            default:
                return new Response('', 204);
        }

        try {
            $form = $this->createForm($subFormType);
            return $this->render($template, [
                'form' => $form->createView()
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erro ao carregar sub-formulário: ' . $e->getMessage());
            return new Response('<div class="alert alert-danger">Erro ao carregar formulário: ' . $e->getMessage() . '</div>', 500);
        }
    }

    #[Route('/search-pessoa-advanced', name: 'search_pessoa_advanced', methods: ['POST'])]
    public function searchPessoaAdvanced(
        Request $request,
        PessoaRepository $pessoaRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        try {
            $this->logger->info('🔵 DEBUG: Iniciando searchPessoaAdvanced');
            $data = json_decode($request->getContent(), true);
            
            if (!$data) {
                return new JsonResponse(['success' => false, 'message' => 'Dados JSON inválidos'], 400);
            }

            $criteria = strtolower($data['criteria'] ?? '');
            $value = $data['value'] ?? '';
            $additionalDoc = $data['additionalDoc'] ?? null;
            $additionalDocType = $data['additionalDocType'] ?? null;

            if (empty($criteria) || empty($value)) {
                return new JsonResponse(['success' => false, 'message' => 'Critério e valor são obrigatórios'], 400);
            }

            $pessoa = match ($criteria) {
                'cpf', 'cpf (pessoa física)' => $pessoaRepository->findByCpfDocumento($value),
                'cnpj', 'cnpj (pessoa jurídica)' => $pessoaRepository->findByCnpjDocumento($value),
                'id', 'id pessoa' => $pessoaRepository->find((int)$value),
                'nome', 'nome completo' => $this->pessoaService->buscaPorNome($value, $additionalDoc, $additionalDocType),
                default => null,
            };

            if (!$pessoa) {
                $this->logger->info("⚠️ Pessoa não encontrada: $criteria = $value");
                return new JsonResponse(['success' => true, 'pessoa' => null, 'message' => 'Pessoa não encontrada']);
            }

            $this->logger->info('✅ Pessoa encontrada: ' . $pessoa->getIdpessoa());

            $cpf = $pessoaRepository->getCpfByPessoa($pessoa->getIdpessoa());
            $cnpj = $pessoaRepository->getCnpjByPessoa($pessoa->getIdpessoa());
            
            $tiposComDados = $pessoaRepository->findTiposComDados($pessoa->getIdpessoa());
            $tiposComDados['tipos'] = $tiposComDados['tipos'] ?? [];
            $tiposComDados['tiposDados'] = $this->pessoaService->buscarDadosTiposPessoa($pessoa->getIdpessoa());

            $tipoParaId = [
                'contratante' => 6,
                'fiador' => 1,
                'locador' => 4,
                'corretor' => 2,
                'corretora' => 3,
                'pretendente' => 5,
            ];

            $ativos = array_keys(array_filter($tiposComDados['tipos']));
            $tipoString = $ativos ? $ativos[0] : null;
            $tipoId = $tipoString ? ($tipoParaId[$tipoString] ?? null) : null;

            $telefones = $this->pessoaService->buscarTelefonesPessoa($pessoa->getIdpessoa());
            $emails = $this->pessoaService->buscarEmailsPessoa($pessoa->getIdpessoa());
            $enderecos = $this->pessoaService->buscarEnderecosPessoa($pessoa->getIdpessoa());
            $chavesPix = $this->pessoaService->buscarChavesPixPessoa($pessoa->getIdpessoa());
            $documentos = $this->pessoaService->buscarDocumentosPessoa($pessoa->getIdpessoa());
            $profissoes = $this->pessoaService->buscarProfissoesPessoa($pessoa->getIdpessoa());

            return new JsonResponse([
                'success' => true,
                'pessoa' => [
                    'id' => $pessoa->getIdpessoa(),
                    'nome' => $pessoa->getNome(),
                    'cpf' => $cpf,
                    'cnpj' => $cnpj,
                    'fisicaJuridica' => $pessoa->getFisicaJuridica(),
                    'dataNascimento' => $pessoa->getDataNascimento()?->format('Y-m-d'),
                    'estadoCivil' => $pessoa->getEstadoCivil()?->getId(),
                    'nacionalidade' => $pessoa->getNacionalidade()?->getId(),
                    'naturalidade' => $pessoa->getNaturalidade()?->getId(),
                    'nomePai' => $pessoa->getNomePai(),
                    'nomeMae' => $pessoa->getNomeMae(),
                    'renda' => $pessoa->getRenda(),
                    'observacoes' => $pessoa->getObservacoes(),
                    'telefones' => $telefones,
                    'enderecos' => $enderecos,
                    'emails' => $emails,
                    'documentos' => $documentos,
                    'chavesPix' => $chavesPix,
                    'profissoes' => $profissoes,
                    'conjuge' => null,
                    'tipos' => $tiposComDados['tipos'],
                    'tiposDados' => $tiposComDados['tiposDados'],
                    'tipoPessoaId' => $tipoId,
                    'tipoPessoaString' => $tipoString
                ],
            ]);
        } catch (\Exception $e) {
            $this->logger->error('🔴 ERRO CRÍTICO em searchPessoaAdvanced: ' . $e->getMessage());
            return new JsonResponse(['success' => false, 'message' => 'Erro interno'], 500);
        }
    }

    #[Route('/load-tipos/{entidade}', name: 'load_tipos', methods: ['GET'])]
    public function loadTipos(string $entidade, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            $repository = null;
            $isProfissao = ($entidade === 'profissao');

            switch ($entidade) {
                case 'telefone':
                    $repository = $entityManager->getRepository(\App\Entity\TiposTelefones::class);
                    break;
                case 'endereco':
                    $repository = $entityManager->getRepository(\App\Entity\TiposEnderecos::class);
                    break;
                case 'email':
                    $repository = $entityManager->getRepository(\App\Entity\TiposEmails::class);
                    break;
                case 'chave-pix':
                    $repository = $entityManager->getRepository(\App\Entity\TiposChavesPix::class);
                    break;
                case 'documento':
                    $repository = $entityManager->getRepository(\App\Entity\TiposDocumentos::class);
                    break;
                case 'profissao':
                    $repository = $entityManager->getRepository(\App\Entity\Profissoes::class);
                    break;
                default:
                    return new JsonResponse(['error' => 'Entidade não reconhecida'], 400);
            }

            $tipos = $repository->findAll();
            $tiposArray = [];
            foreach ($tipos as $tipo) {
                $tiposArray[] = [
                    'id' => $tipo->getId(),
                    'tipo' => $isProfissao ? $tipo->getNome() : $tipo->getTipo()
                ];
            }

            return new JsonResponse(['tipos' => $tiposArray]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Erro interno: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/salvar-tipo/{entidade}', name: 'salvar_tipo', methods: ['POST'])]
    public function salvarTipo(string $entidade, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!$data || !isset($data['tipo'])) {
                return new JsonResponse(['success' => false, 'message' => 'Dados inválidos'], 400);
            }

            $tipoNome = trim($data['tipo']);
            if (empty($tipoNome)) {
                return new JsonResponse(['success' => false, 'message' => 'Nome do tipo é obrigatório'], 400);
            }

            $novoTipo = null;
            $isProfissao = ($entidade === 'profissao');

            switch ($entidade) {
                case 'telefone':
                    $novoTipo = new \App\Entity\TiposTelefones();
                    break;
                case 'endereco':
                    $novoTipo = new \App\Entity\TiposEnderecos();
                    break;
                case 'email':
                    $novoTipo = new \App\Entity\TiposEmails();
                    break;
                case 'chave-pix':
                    $novoTipo = new \App\Entity\TiposChavesPix();
                    break;
                case 'documento':
                    $novoTipo = new \App\Entity\TiposDocumentos();
                    break;
                case 'profissao':
                    $novoTipo = new \App\Entity\Profissoes();
                    break;
                default:
                    return new JsonResponse(['success' => false, 'message' => 'Entidade não reconhecida'], 400);
            }

            if ($isProfissao) {
                $novoTipo->setNome($tipoNome);
                $novoTipo->setAtivo(true);
            } else {
                $novoTipo->setTipo($tipoNome);
            }

            $entityManager->persist($novoTipo);
            $entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'tipo' => [
                    'id' => $novoTipo->getId(),
                    'tipo' => $isProfissao ? $novoTipo->getNome() : $novoTipo->getTipo()
                ]
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/buscar-cep', name: 'buscar_cep', methods: ['POST'])]
    public function buscarCep(Request $request, CepService $cepService): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $cep = $data['cep'] ?? null;

            if (!$cep) {
                return new JsonResponse(['success' => false, 'message' => 'CEP não informado'], 400);
            }

            $endereco = $cepService->buscarEpersistirEndereco($cep);

            return new JsonResponse([
                'success' => true,
                'logradouro' => $endereco['logradouro'],
                'bairro' => $endereco['bairro'],
                'cidade' => $endereco['cidade'],
                'estado' => $endereco['estado']
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/search-conjuge', name: 'search_conjuge', methods: ['POST'])]
    public function searchConjuge(Request $request, PessoaRepository $pessoaRepository): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!$data || !isset($data['termo'])) {
                return new JsonResponse(['success' => false, 'message' => 'Termo de busca não informado'], 400);
            }

            $termo = trim($data['termo']);

            if (strlen($termo) < 3) {
                return new JsonResponse(['success' => false, 'message' => 'Digite pelo menos 3 caracteres'], 400);
            }

            $pessoas = $pessoaRepository->createQueryBuilder('p')
                ->where('p.nome LIKE :termo')
                ->setParameter('termo', '%' . $termo . '%')
                ->andWhere('p.fisicaJuridica = :fisica')
                ->setParameter('fisica', 'fisica')
                ->getQuery()
                ->getResult();

            $result = [];
            foreach ($pessoas as $pessoa) {
                $cpf = $pessoaRepository->getCpfByPessoa($pessoa->getIdpessoa());

                $result[] = [
                    'id' => $pessoa->getIdpessoa(),
                    'nome' => $pessoa->getNome(),
                    'cpf' => $cpf,
                    'data_nascimento' => $pessoa->getDataNascimento() ? $pessoa->getDataNascimento()->format('Y-m-d') : null,
                    'nacionalidade' => $pessoa->getNacionalidade() ? $pessoa->getNacionalidade()->getNome() : null,
                    'naturalidade' => $pessoa->getNaturalidade() ? $pessoa->getNaturalidade()->getNome() : null
                ];
            }

            return new JsonResponse([
                'success' => true,
                'pessoas' => $result
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Erro na busca: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/{id}', name: 'show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Pessoas $pessoa): Response
    {
        return $this->render('pessoa/show.html.twig', [
            'pessoa' => $pessoa,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, Pessoas $pessoa): Response
    {
        $form = $this->createForm(PessoaFormType::class, $pessoa);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $requestData = $request->request->all();
                $formData = $requestData['pessoa_form'] ?? $requestData;
                $tipoPessoa = $form->get('tipoPessoa')->getData();

                $this->pessoaService->atualizarPessoa($pessoa, $formData, $tipoPessoa);

                $this->addFlash('success', 'Pessoa atualizada com sucesso!');
                return $this->redirectToRoute('app_pessoa_index');
                
            } catch (\RuntimeException $e) {
                $this->addFlash('error', $e->getMessage());
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erro ao atualizar pessoa: ' . $e->getMessage());
            }
        }

        return $this->render('pessoa/edit.html.twig', [
            'pessoa' => $pessoa,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, Pessoas $pessoa, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $pessoa->getIdpessoa(), $request->request->get('_token'))) {
            $entityManager->remove($pessoa);
            $entityManager->flush();
            $this->addFlash('success', 'Pessoa excluída com sucesso.');
        }

        return $this->redirectToRoute('app_pessoa_index');
    }

    #[Route('/endereco/{id}', name: 'delete_endereco', methods: ['DELETE'])]
    public function deleteEndereco(int $id, Request $request): JsonResponse
    {
        $token = $request->headers->get('X-CSRF-Token');
        if (!$this->isCsrfTokenValid('ajax_global', $token)) {
            return new JsonResponse(['success' => false, 'message' => 'Token inválido'], 403);
        }

        try {
            $this->pessoaService->excluirEndereco($id);
            return new JsonResponse(['success' => true]);
        } catch (\RuntimeException $e) {
            return new JsonResponse(['success' => false, 'message' => $e->getMessage()], 404);
        }
    }

    #[Route('/telefone/{id}', name: 'delete_telefone', methods: ['DELETE'])]
    public function deleteTelefone(int $id, Request $request): JsonResponse
    {
        $token = $request->headers->get('X-CSRF-Token');
        if (!$this->isCsrfTokenValid('ajax_global', $token)) {
            return new JsonResponse(['success' => false, 'message' => 'Token inválido'], 403);
        }

        try {
            $this->pessoaService->excluirTelefone($id);
            return new JsonResponse(['success' => true]);
        } catch (\RuntimeException $e) {
            return new JsonResponse(['success' => false, 'message' => $e->getMessage()], 404);
        }
    }

    #[Route('/email/{id}', name: 'delete_email', methods: ['DELETE'])]
    public function deleteEmail(int $id, Request $request): JsonResponse
    {
        $token = $request->headers->get('X-CSRF-Token');
        if (!$this->isCsrfTokenValid('ajax_global', $token)) {
            return new JsonResponse(['success' => false, 'message' => 'Token inválido'], 403);
        }

        try {
            $this->pessoaService->excluirEmail($id);
            return new JsonResponse(['success' => true]);
        } catch (\RuntimeException $e) {
            return new JsonResponse(['success' => false, 'message' => $e->getMessage()], 404);
        }
    }

    #[Route('/chave-pix/{id}', name: 'delete_chave_pix', methods: ['DELETE'])]
    public function deleteChavePix(int $id, Request $request): JsonResponse
    {
        $token = $request->headers->get('X-CSRF-Token');
        if (!$this->isCsrfTokenValid('ajax_global', $token)) {
            return new JsonResponse(['success' => false, 'message' => 'Token inválido'], 403);
        }

        try {
            $this->pessoaService->excluirChavePix($id);
            return new JsonResponse(['success' => true]);
        } catch (\RuntimeException $e) {
            return new JsonResponse(['success' => false, 'message' => $e->getMessage()], 404);
        }
    }

    #[Route('/documento/{id}', name: 'delete_documento', methods: ['DELETE'])]
    public function deleteDocumento(int $id, Request $request): JsonResponse
    {
        $token = $request->headers->get('X-CSRF-Token');
        if (!$this->isCsrfTokenValid('ajax_global', $token)) {
            return new JsonResponse(['success' => false, 'message' => 'Token inválido'], 403);
        }

        try {
            $this->pessoaService->excluirDocumento($id);
            return new JsonResponse(['success' => true]);
        } catch (\RuntimeException $e) {
            return new JsonResponse(['success' => false, 'message' => $e->getMessage()], 404);
        }
    }

    #[Route('/profissao/{id}', name: 'delete_profissao', methods: ['DELETE'])]
    public function deleteProfissao(int $id, Request $request): JsonResponse
    {
        $token = $request->headers->get('X-CSRF-Token');
        if (!$this->isCsrfTokenValid('ajax_global', $token)) {
            return new JsonResponse(['success' => false, 'message' => 'Token inválido'], 403);
        }

        try {
            $this->pessoaService->excluirProfissao($id);
            return new JsonResponse(['success' => true]);
        } catch (\RuntimeException $e) {
            return new JsonResponse(['success' => false, 'message' => $e->getMessage()], 404);
        }
    }
}