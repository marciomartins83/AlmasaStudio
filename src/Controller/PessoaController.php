<?php

namespace App\Controller;

use App\DTO\SearchFilterDTO;
use App\DTO\SortOptionDTO;
use App\Entity\Pessoas;
use App\Form\PessoaFormType;
use App\Repository\PessoaRepository;
use App\Service\PessoaService;
use App\Service\CepService;
use App\Service\PaginationService;
use App\Service\ProfissaoService;
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
    private ProfissaoService $profissaoService;
    private LoggerInterface $logger;

    public function __construct(
        PessoaService $pessoaService,
        ProfissaoService $profissaoService,
        LoggerInterface $logger
    ) {
        $this->pessoaService = $pessoaService;
        $this->profissaoService = $profissaoService;
        $this->logger = $logger;
    }

    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(PessoaRepository $pessoaRepository, PaginationService $paginator, Request $request): Response
    {
        $qb = $pessoaRepository->createQueryBuilder('p')
            ->select('DISTINCT p')
            ->leftJoin('App\Entity\PessoasTipos', 'pt', 'WITH', 'pt.idPessoa = p.idpessoa')
            ->orderBy('p.idpessoa', 'DESC');

        $filters = [
            new SearchFilterDTO('nome', 'Nome', 'text', 'p.nome', 'LIKE', [], 'Buscar por nome...', 3),
            new SearchFilterDTO('cod', 'COD', 'number', 'p.cod', 'EXACT', [], 'Buscar por código...', 1),
            new SearchFilterDTO('tipoPessoa', 'Tipo Pessoa', 'select', 'pt.idTipoPessoa', 'EXACT', [
                '1' => 'Fiador',
                '2' => 'Corretor',
                '3' => 'Corretora',
                '4' => 'Locador',
                '5' => 'Pretendente',
                '6' => 'Contratante',
                '7' => 'Sócio',
                '8' => 'Advogado',
                '12' => 'Inquilino',
            ]),
            new SearchFilterDTO('locadorFilter', 'Perfil Locador', 'select', '', 'CUSTOM', [
                '' => 'Todos',
                'locador_todos' => 'Locador (todos)',
                'locador_proprietario' => 'Locador Proprietário',
                'locador_sem_proprietario' => 'Locador Não-Proprietário',
            ]),
            new SearchFilterDTO('fisicaJuridica', 'Física/Jurídica', 'select', 'p.fisicaJuridica', 'EXACT', [
                'fisica' => 'Física',
                'juridica' => 'Jurídica',
            ]),
            new SearchFilterDTO('status', 'Status', 'select', 'p.status', 'BOOL', [
                '1' => 'Ativo',
                '0' => 'Inativo',
            ]),
        ];

        // Aplica filtro de locador (customizado - não passa pelo paginator)
        $locadorFilter = $request->query->get('locadorFilter', '');
        if ($locadorFilter) {
            $qb->andWhere('pt.idTipoPessoa = :locadorTipo')
               ->setParameter('locadorTipo', 4);

            if ($locadorFilter !== 'locador_todos') {
                $qb->leftJoin('App\Entity\PessoasLocadores', 'pl', 'WITH', 'pl.pessoa = p');

                if ($locadorFilter === 'locador_proprietario') {
                    $qb->andWhere('pl.flgProprietario = :isProprietario')
                       ->setParameter('isProprietario', true);
                } elseif ($locadorFilter === 'locador_sem_proprietario') {
                    $qb->andWhere('pl.flgProprietario = :isProprietario')
                       ->setParameter('isProprietario', false);
                }
            }
        }

        $sortOptions = [
            new SortOptionDTO('nome', 'Nome'),
            new SortOptionDTO('cod', 'COD', 'ASC'),
            new SortOptionDTO('idpessoa', 'ID', 'DESC'),
            new SortOptionDTO('dtCadastro', 'Dt Cadastro', 'DESC'),
        ];

        $pagination = $paginator->paginate($qb, $request, null, ['p.nome'], 'p.idpessoa', $filters, $sortOptions, 'idpessoa', 'DESC');

        return $this->render('pessoa/index.html.twig', [
            'pagination' => $pagination,
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

                // Prepara os dados do cônjuge mesclando dados básicos com as coleções
                $dadosConjuge = $requestData['novo_conjuge'] ?? [];

                if (!empty($dadosConjuge)) {
                    // Injeta as coleções dentro do array do cônjuge para o Service processar
                    $dadosConjuge['telefones'] = $requestData['conjuge_telefones'] ?? [];
                    $dadosConjuge['emails'] = $requestData['conjuge_emails'] ?? [];
                    $dadosConjuge['enderecos'] = $requestData['conjuge_enderecos'] ?? [];
                    $dadosConjuge['chaves_pix'] = $requestData['conjuge_chaves_pix'] ?? [];
                    $dadosConjuge['documentos'] = $requestData['conjuge_documentos'] ?? [];
                    $dadosConjuge['profissoes'] = $requestData['conjuge_profissoes'] ?? [];
                }

                // Merge explícito dos campos raw com os dados do formulário
                $formData = array_merge(
                    $requestData['pessoa_form'] ?? [],
                    [
                        'novo_conjuge' => $dadosConjuge,
                        'temConjuge' => $requestData['temConjuge'] ?? null,
                        'conjuge_id' => $requestData['conjuge_id'] ?? null,
                        // Também passa os arrays diretamente para o Service processar dados múltiplos do cônjuge
                        'conjuge_telefones' => $requestData['conjuge_telefones'] ?? [],
                        'conjuge_emails' => $requestData['conjuge_emails'] ?? [],
                        'conjuge_enderecos' => $requestData['conjuge_enderecos'] ?? [],
                        'conjuge_chaves_pix' => $requestData['conjuge_chaves_pix'] ?? [],
                        'conjuge_documentos' => $requestData['conjuge_documentos'] ?? [],
                        'conjuge_profissoes' => $requestData['conjuge_profissoes'] ?? []
                    ]
                );

                // ✅ CORREÇÃO: tipoPessoa vem dos dados da requisição, não do formulário Symfony
                // O campo foi removido do PessoaFormType.php (linha 128-129)
                // Sistema de múltiplos tipos é gerenciado via JavaScript
                // JavaScript envia como 'tipos_pessoa[]' (ver assets/js/pessoa/pessoa_tipos.js:78)
                $tipoPessoa = $requestData['tipos_pessoa'] ?? [];

                $this->pessoaService->criarPessoa($pessoa, $formData, $tipoPessoa);
                
                $this->addFlash('success', 'Pessoa cadastrada com sucesso!');
                return $this->redirectToRoute('app_pessoa_index');
                
            } catch (\RuntimeException $e) {
                $this->addFlash('error', $e->getMessage());
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erro ao salvar a pessoa: ' . $e->getMessage());
            }
        }

        return $this->render('pessoa/pessoa_form.html.twig', [
            'form' => $form->createView(),
            'isEditMode' => false,
            'pessoaId' => null
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
            case 'socio':
                $subFormType = \App\Form\PessoaSocioType::class;
                $template = 'pessoa/partials/socio.html.twig';
                break;
            case 'advogado':
                $subFormType = \App\Form\PessoaAdvogadoType::class;
                $template = 'pessoa/partials/advogado.html.twig';
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
                'cnpj', 'cnpj (pessoa jurídica)' => $pessoaRepository->findByCnpj($value),
                'id', 'id pessoa' => $pessoaRepository->find((int)$value),
                'nome', 'nome completo' => $this->pessoaService->buscaPorNome($value, $additionalDoc, $additionalDocType),
                default => null,
            };

            if (!$pessoa) {
                $this->logger->info("⚠️ Pessoa não encontrada: $criteria = $value");
                return new JsonResponse(['success' => true, 'pessoa' => null, 'message' => 'Pessoa não encontrada']);
            }

            $this->logger->info('✅ Pessoa encontrada: ' . $pessoa->getIdpessoa());

            $pessoaId = $pessoa->getIdpessoa();
            $cpf = $pessoaRepository->getCpfByPessoa($pessoaId);
            $cnpj = $pessoaRepository->getCnpjByPessoa($pessoaId);
            
            // ✅ CORREÇÃO: Usar o método do repository que JÁ FUNCIONAVA
            $tiposComDados = $pessoaRepository->findTiposComDados($pessoaId);
            
            // Garantir que as chaves existem
            $tipos = $tiposComDados['tipos'] ?? [];
            
            // Usar o service para buscar os dados formatados (arrays, não objetos)
            $tiposDados = $this->pessoaService->buscarDadosTiposPessoa($pessoaId);

            $this->logger->info('🔵 DEBUG: Tipos encontrados: ' . json_encode($tipos));
            $this->logger->info('🔵 DEBUG: Dados dos tipos: ' . json_encode(array_keys($tiposDados)));

            // Manter compatibilidade com código existente
            $tipoParaId = [
                'contratante' => 6,
                'fiador' => 1,
                'locador' => 4,
                'corretor' => 2,
                'corretora' => 3,
                'pretendente' => 5,
                'socio' => 7,
                'advogado' => 8,
                'inquilino' => 12,
            ];

            $ativos = array_keys(array_filter($tipos));
            $tipoString = $ativos ? $ativos[0] : null;
            $tipoId = $tipoString ? ($tipoParaId[$tipoString] ?? null) : null;

            $telefones = $this->pessoaService->buscarTelefonesPessoa($pessoaId);
            $emails = $this->pessoaService->buscarEmailsPessoa($pessoaId);
            $enderecos = $this->pessoaService->buscarEnderecosPessoa($pessoaId);
            $chavesPix = $this->pessoaService->buscarChavesPixPessoa($pessoaId);
            $documentos = $this->pessoaService->buscarDocumentosPessoa($pessoaId);
            $profissoes = $this->pessoaService->buscarProfissoesPessoa($pessoaId);
            $contasBancarias = $this->pessoaService->buscarContasBancariasPessoa($pessoaId);
            $conjuge = $this->pessoaService->buscarConjugePessoa($pessoaId);

            $pessoaData = [
                'id' => $pessoaId,
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
                'contasBancarias' => $contasBancarias,
                'conjuge' => $conjuge,
                // ✅ CORREÇÃO: Adicionar tipos e tiposDados ao JSON de resposta
                'tipos' => $tipos,
                'tiposDados' => $tiposDados,
                'tipoPessoaId' => $tipoId,
                'tipoPessoaString' => $tipoString
            ];

            $this->logger->info('🔵 DEBUG: Resposta final - tipos: ' . json_encode($tipos));

            return new JsonResponse(['success' => true, 'pessoa' => $pessoaData]);
            
        } catch (\Exception $e) {
            $this->logger->error('🔴 ERRO CRÍTICO em searchPessoaAdvanced: ' . $e->getMessage());
            $this->logger->error('🔴 STACK TRACE: ' . $e->getTraceAsString());
            return new JsonResponse(['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()], 500);
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
                case 'banco':
                    $repository = $entityManager->getRepository(\App\Entity\Bancos::class);
                    break;
                case 'agencia':
                    $repository = $entityManager->getRepository(\App\Entity\Agencias::class);
                    break;
                case 'tipo_conta_bancaria':
                    $repository = $entityManager->getRepository(\App\Entity\TiposContasBancarias::class);
                    break;
                default:
                    return new JsonResponse(['error' => 'Entidade não reconhecida'], 400);
            }

            $tipos = $repository->findAll();
            $tiposArray = [];
            foreach ($tipos as $tipo) {
                $nome = null;

                // Determinar o campo nome baseado na entidade
                if ($isProfissao) {
                    $nome = $tipo->getNome();
                } elseif ($entidade === 'banco') {
                    $nome = $tipo->getNome() . ' (' . $tipo->getNumero() . ')';
                } elseif ($entidade === 'agencia') {
                    $nome = $tipo->getNome();
                } else {
                    $nome = $tipo->getTipo();
                }

                $tiposArray[] = [
                    'id' => $tipo->getId(),
                    'tipo' => $nome,
                    'nome' => $nome // Adicionar também como 'nome' para compatibilidade
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

            // ✅ Thin Controller: Delega profissão para Service
            if ($entidade === 'profissao') {
                $profissao = $this->profissaoService->salvarProfissao($tipoNome);
                return new JsonResponse([
                    'success' => true,
                    'tipo' => [
                        'id' => $profissao->getId(),
                        'tipo' => $profissao->getNome()
                    ]
                ]);
            }

            // Demais entidades (telefone, endereco, email, etc.) permanecem aqui
            // TODO: Criar services específicos para cada tipo (refatoração futura)
            $novoTipo = null;

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
                default:
                    return new JsonResponse(['success' => false, 'message' => 'Entidade não reconhecida'], 400);
            }

            $novoTipo->setTipo($tipoNome);
            $entityManager->persist($novoTipo);
            $entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'tipo' => [
                    'id' => $novoTipo->getId(),
                    'tipo' => $novoTipo->getTipo()
                ]
            ]);
        } catch (\RuntimeException $e) {
            return new JsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Busca rápida de pessoas para seleção em formulários
     * GET /pessoa/buscar-rapido?q=TEXTO&tipo=ID_TIPO
     */
    #[Route('/buscar-rapido', name: 'buscar_rapido', methods: ['GET'])]
    public function buscarRapido(Request $request, PessoaRepository $pessoaRepository): JsonResponse
    {
        $q = trim($request->query->get('q', ''));
        $tipo = $request->query->getInt('tipo');

        if (strlen($q) < 2) {
            return $this->json([]);
        }

        $qb = $pessoaRepository->createQueryBuilder('p')
            ->select('p.idpessoa, p.nome, p.fisicaJuridica, p.status')
            ->orderBy('p.nome', 'ASC')
            ->setMaxResults(25);

        if (is_numeric($q)) {
            $qb->andWhere($qb->expr()->orX(
                $qb->expr()->like('p.nome', ':q'),
                $qb->expr()->eq('p.idpessoa', ':pessoaId')
            ))
            ->setParameter('q', '%' . $q . '%')
            ->setParameter('pessoaId', (int) $q);
        } else {
            $qb->andWhere('p.nome LIKE :q')
               ->setParameter('q', '%' . $q . '%');
        }

        if ($tipo > 0) {
            $qb->join('App\Entity\PessoasTipos', 'pt', 'WITH', 'pt.idPessoa = p.idpessoa AND pt.idTipoPessoa = :tipo')
               ->setParameter('tipo', $tipo);
        }

        return $this->json($qb->getQuery()->getArrayResult());
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

            if (!$data || !isset($data['criteria']) || !isset($data['value'])) {
                return new JsonResponse(['success' => false, 'message' => 'Critério e valor são obrigatórios'], 400);
            }

            $criteria = $data['criteria'];
            $value = $data['value'];
            $pessoaIdExcluir = isset($data['pessoaId']) && is_numeric($data['pessoaId'])
                ? (int)$data['pessoaId']
                : null;

            // ✅ Thin Controller: Delega lógica para o Service
            // Service irá garantir que uma pessoa nunca seja cônjuge de si mesma
            $pessoas = $this->pessoaService->buscarConjugePorCriterio($criteria, $value, $pessoaIdExcluir);

            // Formatar resultado para JSON
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
    public function show(Pessoas $pessoa, \Doctrine\DBAL\Connection $connection): Response
    {
        $pessoaId = $pessoa->getIdpessoa();

        // Load related data via raw SQL (entities use integer IDs, not ORM relations)
        $tipos = $connection->fetchAllAssociative(
            'SELECT pt.*, tp.tipo_pessoa as tipo_nome FROM pessoas_tipos pt
             LEFT JOIN tipos_pessoas tp ON tp.id = pt.id_tipo_pessoa
             WHERE pt.id_pessoa = ? ORDER BY pt.data_inicio DESC',
            [$pessoaId]
        );

        $profissoes = $connection->fetchAllAssociative(
            'SELECT pp.*, p.nome as profissao_nome FROM pessoas_profissoes pp
             LEFT JOIN profissoes p ON p.id = pp.id_profissao
             WHERE pp.id_pessoa = ? ORDER BY pp.data_admissao DESC',
            [$pessoaId]
        );

        $telefones = $connection->fetchAllAssociative(
            'SELECT pt.*, t.numero, tt.nome as tipo_telefone FROM pessoas_telefones pt
             LEFT JOIN telefones t ON t.id = pt.id_telefone
             LEFT JOIN tipos_telefones tt ON tt.id = t.id_tipo
             WHERE pt.id_pessoa = ? ORDER BY pt.id DESC',
            [$pessoaId]
        );

        $enderecos = $connection->fetchAllAssociative(
            'SELECT e.*, l.logradouro, l.cep, b.nome as bairro, c.nome as cidade, est.uf,
             te.nome as tipo_endereco FROM enderecos e
             LEFT JOIN logradouros l ON l.id = e.id_logradouro
             LEFT JOIN bairros b ON b.id = l.id_bairro
             LEFT JOIN cidades c ON c.id = b.id_cidade
             LEFT JOIN estados est ON est.id = c.id_estado
             LEFT JOIN tipos_enderecos te ON te.id = e.id_tipo
             WHERE e.id_pessoa = ? ORDER BY e.id DESC',
            [$pessoaId]
        );

        $emails = $connection->fetchAllAssociative(
            'SELECT pe.*, em.email, et.nome as tipo_email FROM pessoas_emails pe
             LEFT JOIN emails em ON em.id = pe.id_email
             LEFT JOIN tipos_emails et ON et.id = em.id_tipo
             WHERE pe.id_pessoa = ? ORDER BY pe.id DESC',
            [$pessoaId]
        );

        $chavesPix = $connection->fetchAllAssociative(
            'SELECT cp.*, tcp.nome as tipo_chave_nome FROM chaves_pix cp
             LEFT JOIN tipos_chaves_pix tcp ON tcp.id = cp.id_tipo_chave
             WHERE cp.id_pessoa = ? ORDER BY cp.principal DESC, cp.id DESC',
            [$pessoaId]
        );

        $contasBancarias = $connection->fetchAllAssociative(
            'SELECT cb.*, b.nome as banco_nome FROM contas_bancarias cb
             LEFT JOIN bancos b ON b.id = cb.id_banco
             WHERE cb.id_pessoa = ? ORDER BY cb.principal DESC, cb.id DESC',
            [$pessoaId]
        );

        return $this->render('pessoa/show.html.twig', [
            'pessoa' => $pessoa,
            'tipos' => $tipos,
            'profissoes' => $profissoes,
            'telefones' => $telefones,
            'enderecos' => $enderecos,
            'emails' => $emails,
            'chavesPix' => $chavesPix,
            'contasBancarias' => $contasBancarias,
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

                // Prepara os dados do cônjuge mesclando dados básicos com as coleções
                $dadosConjuge = $requestData['novo_conjuge'] ?? [];

                if (!empty($dadosConjuge)) {
                    // Injeta as coleções dentro do array do cônjuge para o Service processar
                    $dadosConjuge['telefones'] = $requestData['conjuge_telefones'] ?? [];
                    $dadosConjuge['emails'] = $requestData['conjuge_emails'] ?? [];
                    $dadosConjuge['enderecos'] = $requestData['conjuge_enderecos'] ?? [];
                    $dadosConjuge['chaves_pix'] = $requestData['conjuge_chaves_pix'] ?? [];
                    $dadosConjuge['documentos'] = $requestData['conjuge_documentos'] ?? [];
                    $dadosConjuge['profissoes'] = $requestData['conjuge_profissoes'] ?? [];
                }

                // Merge explícito dos campos raw com os dados do formulário
                $formData = array_merge(
                    $requestData['pessoa_form'] ?? [],
                    [
                        'novo_conjuge' => $dadosConjuge,
                        'temConjuge' => $requestData['temConjuge'] ?? null,
                        'conjuge_id' => $requestData['conjuge_id'] ?? null,
                        // Também passa os arrays diretamente para o Service processar dados múltiplos do cônjuge
                        'conjuge_telefones' => $requestData['conjuge_telefones'] ?? [],
                        'conjuge_emails' => $requestData['conjuge_emails'] ?? [],
                        'conjuge_enderecos' => $requestData['conjuge_enderecos'] ?? [],
                        'conjuge_chaves_pix' => $requestData['conjuge_chaves_pix'] ?? [],
                        'conjuge_documentos' => $requestData['conjuge_documentos'] ?? [],
                        'conjuge_profissoes' => $requestData['conjuge_profissoes'] ?? []
                    ]
                );

                // ✅ CORREÇÃO: tipoPessoa vem dos dados da requisição, não do formulário Symfony
                // O campo foi removido do PessoaFormType.php (linha 128-129)
                // Sistema de múltiplos tipos é gerenciado via JavaScript
                // JavaScript envia como 'tipos_pessoa[]' (ver assets/js/pessoa/pessoa_tipos.js:78)
                $tipoPessoa = $requestData['tipos_pessoa'] ?? [];

                $this->pessoaService->atualizarPessoa($pessoa, $formData, $tipoPessoa);

                $this->addFlash('success', 'Pessoa atualizada com sucesso!');
                return $this->redirectToRoute('app_pessoa_index');

            } catch (\RuntimeException $e) {
                $this->addFlash('error', $e->getMessage());
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erro ao atualizar pessoa: ' . $e->getMessage());
            }
        }

        return $this->render('pessoa/pessoa_form.html.twig', [
            'pessoa' => $pessoa,
            'form' => $form->createView(),
            'isEditMode' => true,
            'pessoaId' => $pessoa->getIdpessoa()
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

    #[Route('/conta-bancaria/{id}', name: 'delete_conta_bancaria', methods: ['DELETE'])]
    public function deleteContaBancaria(int $id, Request $request): JsonResponse
    {
        $token = $request->headers->get('X-CSRF-Token');
        if (!$this->isCsrfTokenValid('ajax_global', $token)) {
            return new JsonResponse(['success' => false, 'message' => 'Token inválido'], 403);
        }

        try {
            $this->pessoaService->excluirContaBancaria($id);
            return new JsonResponse(['success' => true]);
        } catch (\RuntimeException $e) {
            return new JsonResponse(['success' => false, 'message' => $e->getMessage()], 404);
        }
    }

    #[Route('/salvar-banco', name: 'salvar_banco', methods: ['POST'])]
    public function salvarBanco(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!$data) {
                return new JsonResponse(['success' => false, 'message' => 'Dados JSON inválidos'], 400);
            }

            $banco = $this->pessoaService->salvarBanco(
                $data['nome'] ?? '',
                (int)($data['numero'] ?? 0)
            );

            return new JsonResponse(['success' => true, 'banco' => $banco]);

        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
        } catch (\RuntimeException $e) {
            return new JsonResponse(['success' => false, 'message' => $e->getMessage()], 409);
        } catch (\Exception $e) {
            $this->logger->error('Erro ao salvar banco: ' . $e->getMessage());
            return new JsonResponse(['success' => false, 'message' => 'Erro interno'], 500);
        }
    }

    #[Route('/salvar-agencia', name: 'salvar_agencia', methods: ['POST'])]
    public function salvarAgencia(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!$data) {
                return new JsonResponse(['success' => false, 'message' => 'Dados JSON inválidos'], 400);
            }

            $agencia = $this->pessoaService->salvarAgencia(
                (int)($data['banco'] ?? 0),
                $data['codigo'] ?? '',
                $data['nome'] ?? null
            );

            return new JsonResponse(['success' => true, 'agencia' => $agencia]);

        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
        } catch (\RuntimeException $e) {
            return new JsonResponse(['success' => false, 'message' => $e->getMessage()], 409);
        } catch (\Exception $e) {
            $this->logger->error('Erro ao salvar agência: ' . $e->getMessage());
            return new JsonResponse(['success' => false, 'message' => 'Erro interno'], 500);
        }
    }

    #[Route('/salvar-tipo-conta-bancaria', name: 'salvar_tipo_conta_bancaria', methods: ['POST'])]
    public function salvarTipoContaBancaria(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!$data) {
                return new JsonResponse(['success' => false, 'message' => 'Dados JSON inválidos'], 400);
            }

            $tipoConta = $this->pessoaService->salvarTipoContaBancaria(
                $data['tipo'] ?? ''
            );

            return new JsonResponse(['success' => true, 'tipoConta' => $tipoConta]);

        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
        } catch (\RuntimeException $e) {
            return new JsonResponse(['success' => false, 'message' => $e->getMessage()], 409);
        } catch (\Exception $e) {
            $this->logger->error('Erro ao salvar tipo de conta: ' . $e->getMessage());
            return new JsonResponse(['success' => false, 'message' => 'Erro interno'], 500);
        }
    }
}