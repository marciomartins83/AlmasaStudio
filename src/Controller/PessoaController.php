<?php

namespace App\Controller;

use App\Entity\Pessoas;
use App\Entity\PessoasDocumentos;
use App\Entity\TiposDocumentos;
use App\Entity\Telefones;
use App\Entity\PessoasTelefones;
use App\Entity\Emails;
use App\Entity\PessoasEmails;
use App\Entity\TiposTelefones;
use App\Entity\TiposEnderecos;
use App\Entity\TiposEmails;
use App\Entity\TiposChavesPix;
use App\Entity\Profissoes;
use App\Entity\Enderecos;
use App\Entity\ChavesPix;
use App\Entity\Logradouros;
use App\Entity\Bairros;
use App\Entity\Cidades;
use App\Entity\Estados;
use App\Entity\EstadoCivil;
use App\Entity\RelacionamentosFamiliares;
use App\Entity\Nacionalidade;
use App\Entity\Naturalidade;
use App\Entity\TiposPessoas;
use App\Form\PessoaFormType;
use App\Repository\PessoaRepository;
use App\Form\PessoaCorretorType;
use App\Form\PessoaContratanteType;
use App\Form\PessoaFiadorType;
use App\Form\PessoaLocadorType;
use App\Form\PessoaPretendenteType;
use App\Form\PessoaCorretoraType;
use App\Service\CepService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\PessoasFiadores;
use App\Entity\PessoasCorretores;
use App\Entity\PessoasLocadores;
use App\Entity\PessoasPretendentes;
use App\Entity\PessoasContratantes;
use App\Entity\PessoasCorretoras;
use App\Entity\PessoasProfissoes;


#[Route('/pessoa', name: 'app_pessoa_')]
class PessoaController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(PessoaRepository $pessoaRepository): Response
    {
        return $this->render('pessoa/index.html.twig', [
            'pessoas' => $pessoaRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(PessoaFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData(); // Isso é um objeto Pessoas agora
            $requestData = $request->request->all();
            $tipoPessoa = $form->get('tipoPessoa')->getData();

            // VERIFICAR se é pessoa existente - se for, processar como edição
            $pessoaId = $form->get('pessoaId')->getData();
            if (!empty($pessoaId)) {
                // É uma pessoa existente - processar como edição aqui mesmo
                $pessoa = $entityManager->getRepository(Pessoas::class)->find($pessoaId);
                if (!$pessoa) {
                    $this->addFlash('error', "Pessoa com ID {$pessoaId} não encontrada");
                    return $this->redirectToRoute('app_pessoa_new');
                }

                // Processar edição da pessoa existente
                $entityManager->getConnection()->beginTransaction();
                try {
                    // Para edição, usar dados do request, não do $data
                    $formData = $requestData['pessoa_form'] ?? [];
                    
                    // Atualizar dados básicos
                    if (isset($formData['nome'])) $pessoa->setNome($formData['nome']);
                    if (isset($formData['dataNascimento'])) {
                        $pessoa->setDataNascimento($formData['dataNascimento'] ? new \DateTime($formData['dataNascimento']) : null);
                    }
                    if (isset($formData['estadoCivil']) && !empty($formData['estadoCivil'])) {
                        $estadoCivil = $entityManager->getReference(EstadoCivil::class, $formData['estadoCivil']);
                        $pessoa->setEstadoCivil($estadoCivil);
                    }
                    if (isset($formData['nacionalidade']) && !empty($formData['nacionalidade'])) {
                        $nacionalidade = $entityManager->getReference(Nacionalidade::class, $formData['nacionalidade']);
                        $pessoa->setNacionalidade($nacionalidade);
                    }
                    if (isset($formData['naturalidade']) && !empty($formData['naturalidade'])) {
                        $naturalidade = $entityManager->getReference(Naturalidade::class, $formData['naturalidade']);
                        $pessoa->setNaturalidade($naturalidade);
                    }
                    if (isset($formData['nomePai'])) $pessoa->setNomePai($formData['nomePai']);
                    if (isset($formData['nomeMae'])) $pessoa->setNomeMae($formData['nomeMae']);
                    if (isset($formData['renda'])) $pessoa->setRenda($formData['renda']);
                    if (isset($formData['observacoes'])) $pessoa->setObservacoes($formData['observacoes']);

                    // Atualizar tipoPessoa se alterado
                    if (is_string($tipoPessoa)) {
                        $tipoPessoaId = $this->convertTipoPessoaToId($tipoPessoa, $entityManager);
                    } else {
                        $tipoPessoaId = (int)$tipoPessoa;
                    }
                    $pessoa->setTipoPessoa($tipoPessoaId);

                    $entityManager->persist($pessoa);
                    $entityManager->flush();

                    // Processar dados múltiplos (telefones, endereços, etc.)
                    $this->salvarDadosMultiplosCorrigido($pessoa->getIdpessoa(), $requestData, $entityManager);

                    // Processar cônjuge
                    $this->salvarConjuge($pessoa, $requestData, $entityManager);

                    $entityManager->flush();
                    $entityManager->getConnection()->commit();

                    $this->addFlash('success', 'Pessoa atualizada com sucesso!');
                    return $this->redirectToRoute('app_pessoa_index');

                } catch (\Exception $e) {
                    $entityManager->getConnection()->rollBack();
                    $this->addFlash('error', 'Erro ao atualizar pessoa: ' . $e->getMessage());
                    error_log('Erro na edição via new: ' . $e->getMessage());
                }

                // Se chegou aqui, houve erro - continuar no formulário
                return $this->render('pessoa/new.html.twig', [
                    'form' => $form->createView(),
                ]);
            }

            // A partir daqui é APENAS criação de pessoa nova
            $entityManager->getConnection()->beginTransaction();
            try {
                // 1. Criar nova pessoa
                $pessoa = new Pessoas();
                $pessoa->setDtCadastro(new \DateTime());
                $pessoa->setStatus(true);

                // 2. Definir dados da pessoa - $data é objeto, usar getters
                $pessoa->setNome($data->getNome());
                $pessoa->setDataNascimento($data->getDataNascimento());
                $pessoa->setEstadoCivil($data->getEstadoCivil());
                $pessoa->setNacionalidade($data->getNacionalidade());
                $pessoa->setNaturalidade($data->getNaturalidade());
                $pessoa->setNomePai($data->getNomePai());
                $pessoa->setNomeMae($data->getNomeMae());
                $pessoa->setRenda($data->getRenda());
                $pessoa->setObservacoes($data->getObservacoes());

                // Definir física/jurídica baseado no CPF/CNPJ do requestData
                $cpfCnpj = $requestData['pessoa_form']['searchTerm'] ?? '';
                $fisicaJuridica = 'fisica';
                if (!empty($cpfCnpj)) {
                    $cpfCnpj = preg_replace('/[^\d]/', '', $cpfCnpj);
                    $fisicaJuridica = strlen($cpfCnpj) === 11 ? 'fisica' : 'juridica';
                }
                
                $pessoa->setFisicaJuridica($fisicaJuridica);
                
                // Converter tipoPessoa do formulário para ID
                if (is_string($tipoPessoa)) {
                    $tipoPessoaId = $this->convertTipoPessoaToId($tipoPessoa, $entityManager);
                } else {
                    $tipoPessoaId = (int)$tipoPessoa;
                }
                $pessoa->setTipoPessoa($tipoPessoaId);

                // 3. Persistir pessoa
                $entityManager->persist($pessoa);

                // 4. FLUSH para obter ID da pessoa
                $entityManager->flush();
                
                // 5. Salvar CPF/CNPJ se informado
                if (!empty($cpfCnpj)) {
                    $this->salvarDocumentoPrincipal($pessoa, $cpfCnpj, $entityManager);
                }

                // 6. Criar vinculação de tipo específico
                $this->salvarTipoEspecifico($pessoa, $tipoPessoa, $data, $entityManager);

                // 7. Salvar dados múltiplos
                $this->salvarDadosMultiplosCorrigido($pessoa->getIdpessoa(), $requestData, $entityManager);

                // 8. Salvar cônjuge
                $this->salvarConjuge($pessoa, $requestData, $entityManager);

                // 9. FLUSH FINAL
                $entityManager->flush();
                $entityManager->getConnection()->commit();

                $this->addFlash('success', 'Pessoa cadastrada com sucesso!');
                return $this->redirectToRoute('app_pessoa_index');

            } catch (\Exception $e) {
                $entityManager->getConnection()->rollBack();
                $this->addFlash('error', 'Erro ao cadastrar pessoa: ' . $e->getMessage());
                
                error_log('Erro detalhado na criação de pessoa: ' . $e->getMessage());
                error_log('Trace: ' . $e->getTraceAsString());
            }
        }

        return $this->render('pessoa/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * MÉTODO CORRIGIDO: Dados múltiplos sem flush internos - COMPLETO
     */
    private function salvarDadosMultiplosCorrigido(int $pessoaId, array $requestData, EntityManagerInterface $entityManager): void
    {
        // Salvar Telefones (SEM flush interno)
        if (isset($requestData['telefones']) && is_array($requestData['telefones'])) {
            foreach ($requestData['telefones'] as $telefoneData) {
                if (!empty($telefoneData['tipo']) && !empty($telefoneData['numero'])) {
                    $telefone = new Telefones();
                    $telefone->setTipo($entityManager->getReference(\App\Entity\TiposTelefones::class, (int)$telefoneData['tipo']));
                    $telefone->setNumero($telefoneData['numero']);
                    $entityManager->persist($telefone);
                    
                    $pessoaTelefone = new PessoasTelefones();
                    $pessoaTelefone->setIdPessoa($pessoaId);
                    $pessoaTelefone->setIdTelefone($telefone->getId());
                    $entityManager->persist($pessoaTelefone);
                }
            }
        }

        // Salvar Emails (SEM flush interno)
        if (isset($requestData['emails']) && is_array($requestData['emails'])) {
            foreach ($requestData['emails'] as $emailData) {
                if (!empty($emailData['tipo']) && !empty($emailData['email'])) {
                    $email = new Emails();
                    $email->setEmail($emailData['email']);
                    $email->setTipo($entityManager->getReference(\App\Entity\TiposEmails::class, (int)$emailData['tipo']));
                    $entityManager->persist($email);
                    
                    $pessoaEmail = new PessoasEmails();
                    $pessoaEmail->setIdPessoa($pessoaId);
                    $pessoaEmail->setIdEmail($email->getId());
                    $entityManager->persist($pessoaEmail);
                }
            }
        }

        // Salvar Endereços (SEM flush interno)
        if (isset($requestData['enderecos']) && is_array($requestData['enderecos'])) {
            foreach ($requestData['enderecos'] as $enderecoData) {
                if (!empty($enderecoData['tipo']) && !empty($enderecoData['numero'])) {
                    $logradouroId = $this->buscarOuCriarLogradouro($enderecoData, $entityManager);
                    
                    $endereco = new Enderecos();
                    $endereco->setLogradouro($entityManager->getReference(Logradouros::class, $logradouroId));
                    $endereco->setTipo($entityManager->getReference(\App\Entity\TiposEnderecos::class, (int)$enderecoData['tipo']));
                    $endereco->setEndNumero((int)$enderecoData['numero']);
                    
                    if (!empty($enderecoData['complemento'])) {
                        $endereco->setComplemento($enderecoData['complemento']);
                    }
                    
                    $entityManager->persist($endereco);
                }
            }
        }

        // Salvar Chaves PIX (SEM flush interno)
        if (isset($requestData['chaves_pix']) && is_array($requestData['chaves_pix'])) {
            foreach ($requestData['chaves_pix'] as $pixData) {
                if (!empty($pixData['tipo']) && !empty($pixData['chave'])) {
                    $chavePix = new ChavesPix();
                    $chavePix->setIdPessoa($pessoaId);
                    $chavePix->setIdTipoChave((int)$pixData['tipo']);
                    $chavePix->setChavePix($pixData['chave']);
                    $chavePix->setPrincipal(!empty($pixData['principal']));
                    $chavePix->setAtivo(true);
                    $entityManager->persist($chavePix);
                }
            }
        }

        // Salvar Documentos (SEM flush interno)
        if (isset($requestData['documentos']) && is_array($requestData['documentos'])) {
            foreach ($requestData['documentos'] as $documentoData) {
                if (!empty($documentoData['tipo']) && !empty($documentoData['numero'])) {
                    $documento = new PessoasDocumentos();
                    $documento->setIdPessoa($pessoaId);
                    $documento->setIdTipoDocumento((int)$documentoData['tipo']);
                    $documento->setNumeroDocumento($documentoData['numero']);
                    
                    if (!empty($documentoData['orgao_emissor'])) {
                        $documento->setOrgaoEmissor($documentoData['orgao_emissor']);
                    }
                    
                    if (!empty($documentoData['data_emissao'])) {
                        $documento->setDataEmissao(new \DateTime($documentoData['data_emissao']));
                    }
                    
                    if (!empty($documentoData['data_vencimento'])) {
                        $documento->setDataVencimento(new \DateTime($documentoData['data_vencimento']));
                    }
                    
                    if (!empty($documentoData['observacoes'])) {
                        $documento->setObservacoes($documentoData['observacoes']);
                    }
                    
                    $documento->setAtivo(true);
                    $entityManager->persist($documento);
                }
            }
        }

        // Salvar Profissões (SEM flush interno)
        if (isset($requestData['profissoes']) && is_array($requestData['profissoes'])) {
            foreach ($requestData['profissoes'] as $profissaoData) {
                if (!empty($profissaoData['profissao'])) {
                    $pessoaProfissao = new \App\Entity\PessoasProfissoes();
                    $pessoaProfissao->setIdPessoa($pessoaId);
                    $pessoaProfissao->setIdProfissao((int)$profissaoData['profissao']);
                    
                    if (!empty($profissaoData['empresa'])) {
                        $pessoaProfissao->setEmpresa($profissaoData['empresa']);
                    }
                    
                    if (!empty($profissaoData['data_admissao'])) {
                        $pessoaProfissao->setDataAdmissao(new \DateTime($profissaoData['data_admissao']));
                    }
                    
                    if (!empty($profissaoData['data_demissao'])) {
                        $pessoaProfissao->setDataDemissao(new \DateTime($profissaoData['data_demissao']));
                    }
                    
                    if (!empty($profissaoData['renda'])) {
                        $pessoaProfissao->setRenda((float)$profissaoData['renda']);
                    }
                    
                    if (!empty($profissaoData['observacoes'])) {
                        $pessoaProfissao->setObservacoes($profissaoData['observacoes']);
                    }
                    
                    $pessoaProfissao->setAtivo(true);
                    $entityManager->persist($pessoaProfissao);
                }
            }
        }
    }

    /**
     * Converte string do tipo de pessoa para ID correspondente na tabela tipos_pessoas
     */
    private function convertTipoPessoaToId(string $tipoPessoaString, EntityManagerInterface $entityManager): int
    {
        $tipoRepository = $entityManager->getRepository(\App\Entity\TiposPessoas::class);
        $tipo = $tipoRepository->findOneBy(['tipo' => $tipoPessoaString, 'ativo' => true]);
        
        if ($tipo) {
            return $tipo->getId();
        }
        
        // Fallback para fiador se não encontrar
        $fiador = $tipoRepository->findOneBy(['tipo' => 'fiador', 'ativo' => true]);
        return $fiador ? $fiador->getId() : 1;
    }   

    #[Route('/_subform', name: '_subform', methods: ['POST'])]
    public function _subform(Request $request): Response
    {
        $tipo = $request->get('tipo');

        if (!$tipo) {
            return new Response('', 204);
        }

        // Tipos que só fazem vinculação (sem campos extras) - retornar vazio
        $tiposSemCampos = ['contratante', 'corretora'];
        if (in_array($tipo, $tiposSemCampos)) {
            return new Response('', 204);
        }

        $subFormType = null;
        $template = null;

        switch ($tipo) {
            case 'fiador':
                $subFormType = PessoaFiadorType::class;
                $template = 'pessoa/partials/fiador.html.twig';
                break;
            case 'corretor':
                $subFormType = PessoaCorretorType::class;
                $template = 'pessoa/partials/corretor.html.twig';
                break;
            case 'locador':
                $subFormType = PessoaLocadorType::class;
                $template = 'pessoa/partials/locador.html.twig';
                break;
            case 'pretendente':
                $subFormType = PessoaPretendenteType::class;
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
            error_log('Erro ao carregar sub-formulário: ' . $e->getMessage());
            return new Response('<div class="alert alert-danger">Erro ao carregar formulário: ' . $e->getMessage() . '</div>', 500);
        }
    }

    #[Route('/search-pessoa-advanced', name: 'search_pessoa_advanced', methods: ['POST'])]
    public function searchPessoaAdvanced(Request $request, PessoaRepository $pessoaRepository): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            
            if (!$data) {
                return new JsonResponse(['success' => false, 'message' => 'Dados JSON inválidos'], 400);
            }

            $criteria = $data['criteria'] ?? '';
            $value = $data['value'] ?? '';
            $additionalDoc = $data['additionalDoc'] ?? null;
            $additionalDocType = $data['additionalDocType'] ?? null;

            if (empty($criteria) || empty($value)) {
                return new JsonResponse(['success' => false, 'message' => 'Critério e valor são obrigatórios'], 400);
            }

            $pessoa = null;
            
            switch ($criteria) {
                case 'cpf':
                case 'CPF':
                case 'CPF (Pessoa Física)':
                    $pessoa = $pessoaRepository->findByCpf($value);
                    break;
                
                case 'cnpj':
                case 'CNPJ':
                case 'CNPJ (Pessoa Jurídica)':
                    $pessoa = $pessoaRepository->findByCnpj($value);
                    break;
                
                case 'id':
                case 'ID':
                case 'Id Pessoa':
                    $pessoa = $pessoaRepository->find((int)$value);
                    break;
                
                case 'nome':
                case 'Nome':
                case 'Nome Completo':
                    if ($additionalDoc && $additionalDocType) {
                        $isDocCpf = (stripos($additionalDocType, 'cpf') !== false);
                        $isDocCnpj = (stripos($additionalDocType, 'cnpj') !== false);
                        
                        if ($isDocCpf) {
                            $pessoaPorDoc = $pessoaRepository->findByCpf($additionalDoc);
                        } elseif ($isDocCnpj) {
                            $pessoaPorDoc = $pessoaRepository->findByCnpj($additionalDoc);
                        } else {
                            $pessoaPorDoc = null;
                        }

                        if ($pessoaPorDoc && stripos($pessoaPorDoc->getNome(), $value) !== false) {
                            $pessoa = $pessoaPorDoc;
                        }
                    } else {
                        $pessoas = $pessoaRepository->findByNome($value);
                        if (count($pessoas) === 1) {
                            $pessoa = $pessoas[0];
                        } elseif (count($pessoas) > 1) {
                            return new JsonResponse([
                                'success' => false, 
                                'message' => 'Múltiplas pessoas encontradas com este nome. Por favor, informe o CPF ou CNPJ para especificar.'
                            ]);
                        }
                    }
                    break;
                
                default:
                    return new JsonResponse(['success' => false, 'message' => 'Critério de busca inválido'], 400);
            }

            if ($pessoa) {
                $cpf = $pessoaRepository->getCpfByPessoa($pessoa->getIdpessoa());
                $cnpj = $pessoaRepository->getCnpjByPessoa($pessoa->getIdpessoa());
                
                // Buscar dados múltiplos da pessoa
                $telefones = $this->getTelefonesByPessoa($pessoa->getIdpessoa(), $pessoaRepository->getEntityManager());
                $enderecos = $this->getEnderecosByPessoa($pessoa->getIdpessoa(), $pessoaRepository->getEntityManager());
                $emails = $this->getEmailsByPessoa($pessoa->getIdpessoa(), $pessoaRepository->getEntityManager());
                $documentos = $this->getDocumentosByPessoa($pessoa->getIdpessoa(), $pessoaRepository->getEntityManager());
                $chavesPix = $this->getChavesPixByPessoa($pessoa->getIdpessoa(), $pessoaRepository->getEntityManager());
                $profissoes = $this->getProfissoesByPessoa($pessoa->getIdpessoa(), $pessoaRepository->getEntityManager());
                $conjuge = $this->getConjugeByPessoa($pessoa->getIdpessoa(), $pessoaRepository->getEntityManager());
                
                return new JsonResponse([
                    'success' => true,
                    'pessoa' => [
                        'id' => $pessoa->getIdpessoa(),
                        'nome' => $pessoa->getNome(),
                        'cpf' => $cpf,
                        'cnpj' => $cnpj,
                        'fisicaJuridica' => $pessoa->getFisicaJuridica(),
                        'dataNascimento' => $pessoa->getDataNascimento() ? $pessoa->getDataNascimento()->format('Y-m-d') : null,
                        'estadoCivil' => $pessoa->getEstadoCivil() ? $pessoa->getEstadoCivil()->getId() : null,
                        'nacionalidade' => $pessoa->getNacionalidade() ? $pessoa->getNacionalidade()->getId() : null,
                        'naturalidade' => $pessoa->getNaturalidade() ? $pessoa->getNaturalidade()->getId() : null,
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
                        'conjuge' => $conjuge
                    ]
                ]);
            } else {
                return new JsonResponse([
                    'success' => true,
                    'pessoa' => null,
                    'message' => 'Pessoa não encontrada'
                ]);
            }

        } catch (\Exception $e) {
            error_log('Erro na busca de pessoa: ' . $e->getMessage());
            return new JsonResponse([
                'success' => false, 
                'message' => 'Erro interno: ' . $e->getMessage()
            ], 500);
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
                    $repository = $entityManager->getRepository(TiposTelefones::class);
                    break;
                case 'endereco':
                    $repository = $entityManager->getRepository(TiposEnderecos::class);
                    break;
                case 'email':
                    $repository = $entityManager->getRepository(TiposEmails::class);
                    break;
                case 'chave-pix':
                    $repository = $entityManager->getRepository(TiposChavesPix::class);
                    break;
                case 'documento':
                    $repository = $entityManager->getRepository(TiposDocumentos::class);
                    break;
                case 'profissao':
                    $repository = $entityManager->getRepository(Profissoes::class);
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
                    $novoTipo = new TiposTelefones();
                    break;
                case 'endereco':
                    $novoTipo = new TiposEnderecos();
                    break;
                case 'email':
                    $novoTipo = new TiposEmails();
                    break;
                case 'chave-pix':
                    $novoTipo = new TiposChavesPix();
                    break;
                case 'documento':
                    $novoTipo = new TiposDocumentos();
                    break;
                case 'profissao':
                    $novoTipo = new Profissoes();
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
                return new JsonResponse(['success' => false, 'message' => 'Digite pelo menos 3 caracteres para buscar'], 400);
            }

            $pessoas = $pessoaRepository->createQueryBuilder('p')
                ->where('p.nome LIKE :termo')
                ->setParameter('termo', '%'.$termo.'%')
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
    public function edit(Request $request, Pessoas $pessoa, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(PessoaFormType::class, $pessoa);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $requestData = $request->request->all();
            $tipoPessoa = $form->get('tipoPessoa')->getData();

            $entityManager->getConnection()->beginTransaction();
            try {
                // 1. Atualizar dados básicos da pessoa
                $pessoa->setNome($data['nome']);
                $pessoa->setDataNascimento($data['dataNascimento']);
                $pessoa->setEstadoCivil($data['estadoCivil']);
                $pessoa->setNacionalidade($data['nacionalidade']);
                $pessoa->setNaturalidade($data['naturalidade']);
                $pessoa->setNomePai($data['nomePai']);
                $pessoa->setNomeMae($data['nomeMae']);
                $pessoa->setRenda($data['renda']);
                $pessoa->setObservacoes($data['observacoes']);

                // Atualizar física/jurídica baseado no CPF/CNPJ se informado
                $cpfCnpj = $data['searchTerm'] ?? '';
                if (!empty($cpfCnpj)) {
                    $cpfCnpj = preg_replace('/[^\d]/', '', $cpfCnpj);
                    $fisicaJuridica = strlen($cpfCnpj) === 11 ? 'fisica' : 'juridica';
                    $pessoa->setFisicaJuridica($fisicaJuridica);
                }
                
                // Atualizar tipoPessoa se alterado
                if (is_string($tipoPessoa)) {
                    $tipoPessoaId = $this->convertTipoPessoaToId($tipoPessoa, $entityManager);
                } else {
                    $tipoPessoaId = (int)$tipoPessoa;
                }
                $pessoa->setTipoPessoa($tipoPessoaId);

                // 2. Persistir alterações da pessoa
                $entityManager->persist($pessoa);
                $entityManager->flush();

                // 3. LIMPAR dados múltiplos existentes antes de salvar novos
                $this->limparDadosMultiplosExistentes($pessoa->getIdpessoa(), $entityManager);

                // 4. Salvar CPF/CNPJ se informado (pode ser novo ou alterado)
                if (!empty($cpfCnpj)) {
                    $this->salvarDocumentoPrincipal($pessoa, $cpfCnpj, $entityManager);
                }

                // 5. Salvar novos dados múltiplos
                $this->salvarDadosMultiplosCorrigido($pessoa->getIdpessoa(), $requestData, $entityManager);

                // 6. Processar alterações do cônjuge
                $this->processarConjugeEdicao($pessoa, $requestData, $entityManager);

                // 7. FLUSH FINAL
                $entityManager->flush();
                $entityManager->getConnection()->commit();

                $this->addFlash('success', 'Pessoa atualizada com sucesso!');
                return $this->redirectToRoute('app_pessoa_index');

            } catch (\Exception $e) {
                $entityManager->getConnection()->rollBack();
                $this->addFlash('error', 'Erro ao atualizar pessoa: ' . $e->getMessage());
                
                error_log('Erro detalhado na edição de pessoa: ' . $e->getMessage());
                error_log('Trace: ' . $e->getTraceAsString());
            }
        }

        return $this->render('pessoa/edit.html.twig', [
            'pessoa' => $pessoa,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Limpa dados múltiplos existentes antes de salvar novos (estratégia replace)
     */
    private function limparDadosMultiplosExistentes(int $pessoaId, EntityManagerInterface $entityManager): void
    {
        // Limpar telefones existentes
        $telefonesExistentes = $entityManager->getRepository(PessoasTelefones::class)
            ->findBy(['idPessoa' => $pessoaId]);
        foreach ($telefonesExistentes as $pessoaTelefone) {
            $telefone = $entityManager->getRepository(Telefones::class)
                ->find($pessoaTelefone->getIdTelefone());
            if ($telefone) {
                $entityManager->remove($telefone);
            }
            $entityManager->remove($pessoaTelefone);
        }

        // Limpar emails existentes
        $emailsExistentes = $entityManager->getRepository(PessoasEmails::class)
            ->findBy(['idPessoa' => $pessoaId]);
        foreach ($emailsExistentes as $pessoaEmail) {
            $email = $entityManager->getRepository(Emails::class)
                ->find($pessoaEmail->getIdEmail());
            if ($email) {
                $entityManager->remove($email);
            }
            $entityManager->remove($pessoaEmail);
        }

        // Limpar endereços existentes
        $enderecosExistentes = $entityManager->getRepository(Enderecos::class)
            ->findBy(['idPessoa' => $pessoaId]);
        foreach ($enderecosExistentes as $endereco) {
            $entityManager->remove($endereco);
        }

        // Limpar chaves PIX existentes
        $pixExistentes = $entityManager->getRepository(ChavesPix::class)
            ->findBy(['idPessoa' => $pessoaId]);
        foreach ($pixExistentes as $chavePix) {
            $entityManager->remove($chavePix);
        }

        // Limpar documentos existentes (exceto CPF/CNPJ principal)
        $documentosExistentes = $entityManager->getRepository(PessoasDocumentos::class)
            ->createQueryBuilder('pd')
            ->join('pd.tipoDocumento', 'td')
            ->where('pd.idPessoa = :pessoaId')
            ->andWhere('td.tipo NOT IN (:tiposPrincipais)')
            ->setParameter('pessoaId', $pessoaId)
            ->setParameter('tiposPrincipais', ['CPF', 'CNPJ'])
            ->getQuery()
            ->getResult();
        foreach ($documentosExistentes as $documento) {
            $entityManager->remove($documento);
        }

        // Limpar profissões existentes
        $profissoesExistentes = $entityManager->getRepository(PessoasProfissoes::class)
            ->findBy(['idPessoa' => $pessoaId]);
        foreach ($profissoesExistentes as $pessoaProfissao) {
            $entityManager->remove($pessoaProfissao);
        }
    }


    #[Route('/{id}', name: 'delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, Pessoas $pessoa, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$pessoa->getIdpessoa(), $request->request->get('_token'))) {
            $entityManager->remove($pessoa);
            $entityManager->flush();
            $this->addFlash('success', 'Pessoa excluída com sucesso.');
        }

        return $this->redirectToRoute('app_pessoa_index');
    }

    // =========================================================================
    // MÉTODOS PRIVADOS PARA PERSISTÊNCIA (SERVICE LAYER PATTERN)
    // =========================================================================

    /**
     * MÉTODO CORRIGIDO: salvarDocumentoPrincipal sem flush interno
     */
    private function salvarDocumentoPrincipal(Pessoas $pessoa, string $documento, EntityManagerInterface $entityManager): void
    {
        $documento = preg_replace('/[^\d]/', '', $documento);
        $tipoDocumento = strlen($documento) === 11 ? 'CPF' : 'CNPJ';
        
        $tipoDocumentoEntity = $entityManager->getRepository(TiposDocumentos::class)
            ->findOneBy(['tipo' => $tipoDocumento]);
        
        if ($tipoDocumentoEntity) {
            $pessoaDocumento = new PessoasDocumentos();
            $pessoaDocumento->setIdPessoa($pessoa->getIdpessoa());
            $pessoaDocumento->setIdTipoDocumento($tipoDocumentoEntity->getId());
            $pessoaDocumento->setNumeroDocumento($documento);
            $pessoaDocumento->setAtivo(true);
            
            $entityManager->persist($pessoaDocumento);
        }
    }

    private function salvarTipoEspecifico(Pessoas $pessoa, string $tipoPessoa, array $data, EntityManagerInterface $entityManager): void
    {
        if (!$tipoPessoa) {
            return;
        }

        switch ($tipoPessoa) {
            case 'fiador':
                $fiador = new \App\Entity\PessoasFiadores();
                $fiador->setIdPessoa($pessoa->getIdpessoa());
                $entityManager->persist($fiador);
                break;
                
            case 'corretor':
                $corretor = new \App\Entity\PessoasCorretores();
                $corretor->setPessoa($pessoa);
                $entityManager->persist($corretor);
                break;
                
            case 'locador':
                $locador = new \App\Entity\PessoasLocadores();
                $locador->setPessoa($pessoa);
                $entityManager->persist($locador);
                break;
                
            case 'pretendente':
                $pretendente = new \App\Entity\PessoasPretendentes();
                $pretendente->setPessoa($pessoa);
                $entityManager->persist($pretendente);
                break;
                
            case 'contratante':
                $contratante = new \App\Entity\PessoasContratantes();
                $contratante->setPessoa($pessoa);
                $entityManager->persist($contratante);
                break;
                
            case 'corretora':
                $corretora = new \App\Entity\PessoasCorretoras();
                $corretora->setPessoa($pessoa);
                $entityManager->persist($corretora);
                break;
        }