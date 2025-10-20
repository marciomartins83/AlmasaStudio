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
use Psr\Log\LoggerInterface;
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
    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(PessoaFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data        = $form->getData();           // objeto Pessoas
            $requestData = $request->request->all();
            $tipoPessoa  = $form->get('tipoPessoa')->getData();

            /* ---------- 1.  Verifica CPF duplicado ---------- */
            $cpfNumero = null;
            foreach ($data->getPessoasDocumentos() as $pd) {
                $doc = $pd->getIdDocumento();
                if ($doc && $doc->getTipo() === 1) {      // 1 = CPF
                    $cpfNumero = $doc->getNumero();
                    break;
                }
            }

            if ($cpfNumero) {
                $existente = $entityManager->getRepository(Pessoas::class)
                    ->findByCpfDocumento($cpfNumero);
                if ($existente) {
                    error_log('[CPF DUPLICADO] ' . $cpfNumero);
                    $this->addFlash('error', 'CPF já cadastrado.');
                    return $this->redirectToRoute('app_pessoa_new');
                }
            }

            /* ---------- 2.  Telefones – sem duplicar ---------- */
            $telRepo = $entityManager->getRepository(Telefones::class);
            foreach ($data->getPessoasTelefones() as $pt) {
                $tel = $pt->getIdTelefone();
                if (!$tel) continue;

                $exist = $telRepo->findOneBy(['numero' => $tel->getNumero(), 'ativo' => true]);
                if ($exist) {
                    $pt->setIdTelefone($exist);
                    $entityManager->remove($tel);
                    error_log('[TELEFONE REUTILIZADO] ' . $tel->getNumero());
                }
            }

            /* ---------- 3.  E-mails – sem duplicar ---------- */
            $emailRepo = $entityManager->getRepository(Emails::class);
            foreach ($data->getPessoasEmails() as $pe) {
                $email = $pe->getIdEmail();
                if (!$email) continue;

                $exist = $emailRepo->findOneBy(['email' => $email->getEmail(), 'ativo' => true]);
                if ($exist) {
                    $pe->setIdEmail($exist);
                    $entityManager->remove($email);
                    error_log('[EMAIL REUTILIZADO] ' . $email->getEmail());
                }
            }

            /* ---------- 4.  Endereços – sem duplicar ---------- */
            $endRepo = $entityManager->getRepository(Enderecos::class);
            foreach ($data->getPessoasEnderecos() as $pen) {
                $end = $pen->getIdEndereco();
                if (!$end) continue;

                $exist = $endRepo->findOneBy([
                    'logradouro' => $end->getLogradouro(),
                    'numero'     => $end->getNumero(),
                    'ativo'      => true
                ]);
                if ($exist) {
                    $pen->setIdEndereco($exist);
                    $entityManager->remove($end);
                    error_log('[ENDERECO REUTILIZADO] ' . $end->getLogradouro() . ', ' . $end->getNumero());
                }
            }

            /* ---------- 5.  Demais documentos – sem duplicar ---------- */
            $docRepo = $entityManager->getRepository(\App\Entity\PessoasDocumentos::class);
            foreach ($data->getPessoasDocumentos() as $pd) {
                $tipo = $pd->getIdTipoDocumento();
                $num  = $pd->getNumeroDocumento();

                $exist = $docRepo->findOneBy([
                    'idPessoa'         => $data->getIdpessoa(),
                    'idTipoDocumento'  => $tipo,
                    'numeroDocumento'  => $num,
                    'ativo'            => true
                ]);
                if ($exist) {
                    // reutiliza o existente e descarta o novo
                    $entityManager->remove($pd);   // remove o objeto duplicado
                    error_log('[DOCUMENTO REUTILIZADO] tipo=' . $tipo . ' numero=' . $num);
                }
            }

            /* ---------- 6.  Persiste ---------- */
            $entityManager->persist($data);
            $entityManager->flush();

            error_log('[PESSOA SALVA] ID=' . $data->getIdpessoa());
            $this->addFlash('success', 'Pessoa cadastrada com sucesso!');
            return $this->redirectToRoute('app_pessoa_index');
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
        // Salvar Telefones (SEM flush interno) - CORRIGIDO
        if (isset($requestData['telefones']) && is_array($requestData['telefones'])) {
            foreach ($requestData['telefones'] as $telefoneData) {
                if (!empty($telefoneData['tipo']) && !empty($telefoneData['numero'])) {
                    $telefone = new Telefones();
                    $telefone->setTipo($entityManager->getReference(\App\Entity\TiposTelefones::class, (int)$telefoneData['tipo']));
                    $telefone->setNumero($telefoneData['numero']);
                    $entityManager->persist($telefone);
                    $entityManager->flush(); // FLUSH para obter ID

                    $pessoaTelefone = new PessoasTelefones();
                    $pessoaTelefone->setIdPessoa($pessoaId);
                    $pessoaTelefone->setIdTelefone($telefone->getId()); // Agora tem ID
                    $entityManager->persist($pessoaTelefone);
                }
            }
        }

        // Salvar Emails (SEM flush interno) - CORRIGIDO
        if (isset($requestData['emails']) && is_array($requestData['emails'])) {
            foreach ($requestData['emails'] as $emailData) {
                if (!empty($emailData['tipo']) && !empty($emailData['email'])) {
                    $email = new Emails();
                    $email->setEmail($emailData['email']);
                    $email->setTipo($entityManager->getReference(\App\Entity\TiposEmails::class, (int)$emailData['tipo']));
                    $entityManager->persist($email);
                    $entityManager->flush(); // FLUSH para obter ID

                    $pessoaEmail = new PessoasEmails();
                    $pessoaEmail->setIdPessoa($pessoaId);
                    $pessoaEmail->setIdEmail($email->getId()); // Agora tem ID
                    $entityManager->persist($pessoaEmail);
                }
            }
        }

        // Salvar Endereços (SEM flush interno) - CORRIGIDO
        if (isset($requestData['enderecos']) && is_array($requestData['enderecos'])) {
            foreach ($requestData['enderecos'] as $enderecoData) {
                if (!empty($enderecoData['tipo']) && !empty($enderecoData['numero'])) {
                    $logradouroId = $this->buscarOuCriarLogradouro($enderecoData, $entityManager);

                    $endereco = new Enderecos();
                    $endereco->setPessoa($entityManager->getReference(Pessoas::class, $pessoaId)); // ✅ CORRIGIDO: Usar setPessoa
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
                    $pessoa = $entityManager->getRepository(Pessoas::class)->getIdpessoa($pessoaId);
                    $documento->setPessoa($pessoa);
                    $tipoDocumentoEntity = $entityManager->getRepository(TiposDocumentos::class)
                        ->findOneBy(['tipo' => 'CPF']); // ou 'CNPJ', depende do contexto

                    if ($tipoDocumentoEntity) {
                        $documento->setTipoDocumento($tipoDocumentoEntity);
                    }
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
    public function searchPessoaAdvanced(
        Request $request, 
        PessoaRepository $pessoaRepository, 
        EntityManagerInterface $entityManager,
        LoggerInterface $logger  // ✅ ADICIONE ISTO
    ): JsonResponse
    {
        try {
            $logger->info("🔵 DEBUG: Iniciando searchPessoaAdvanced");
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
                    $pessoa = $pessoaRepository->findByCpfDocumento($value);
                    break;

                case 'cnpj':
                case 'CNPJ':
                case 'CNPJ (Pessoa Jurídica)':
                    $pessoa = $pessoaRepository->findByCnpjDocumento($value);
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
                $logger->info("✅ Pessoa encontrada: " . $pessoa->getIdpessoa());
                
                $logger->info("🔵 DEBUG: Busca cpf");
                $cpf = $pessoaRepository->getCpfByPessoa($pessoa->getIdpessoa());
                $logger->info("🔵 DEBUG: busca cnpj");
                $cnpj = $pessoaRepository->getCnpjByPessoa($pessoa->getIdpessoa());

                // BUSCAR DADOS MÚLTIPLOS
                $logger->info("🔵 Iniciando buscarTelefonesPessoa");
                try {
                    $telefones = $this->buscarTelefonesPessoa($pessoa->getIdpessoa(), $entityManager);
                    $logger->info("✅ Telefones obtidos: " . count($telefones));
                } catch (\Exception $e) {
                    $logger->error("❌ ERRO em buscarTelefonesPessoa: " . $e->getMessage());
                    throw $e;
                }

                $logger->info("🔵 Iniciando buscarEmailsPessoa");
                try {
                    $emails = $this->buscarEmailsPessoa($pessoa->getIdpessoa(), $entityManager);
                    $logger->info("✅ Emails obtidos: " . count($emails));
                } catch (\Exception $e) {
                    $logger->error("❌ ERRO em buscarEmailsPessoa: " . $e->getMessage());
                    throw $e;
                }

                $logger->info("🔵 Iniciando buscarEnderecosPessoa");
                try {
                    $enderecos = $this->buscarEnderecosPessoa($pessoa->getIdpessoa(), $entityManager);
                    $logger->info("✅ Endereços obtidos: " . count($enderecos));
                } catch (\Exception $e) {
                    $logger->error("❌ ERRO em buscarEnderecosPessoa: " . $e->getMessage());
                    throw $e;
                }

                $logger->info("🔵 Iniciando buscarChavesPixPessoa");
                try {
                    $chavesPix = $this->buscarChavesPixPessoa($pessoa->getIdpessoa(), $entityManager);
                    $logger->info("✅ Chaves PIX obtidas: " . count($chavesPix));
                } catch (\Exception $e) {
                    $logger->error("❌ ERRO em buscarChavesPixPessoa: " . $e->getMessage());
                    throw $e;
                }

                $logger->info("🔵 Iniciando buscarDocumentosPessoa");
                try {
                    $documentos = $this->buscarDocumentosPessoa($pessoa->getIdpessoa(), $entityManager);
                    $logger->info("✅ Documentos obtidos: " . count($documentos));
                } catch (\Exception $e) {
                    $logger->error("❌ ERRO em buscarDocumentosPessoa: " . $e->getMessage());
                    throw $e;
                }

                $profissoes = [];
                $conjuge = null;

                $logger->info("✅ Retornando resposta com sucesso");
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
                $logger->info("⚠️ Pessoa não encontrada com critério: $criteria = $value");
                return new JsonResponse([
                    'success' => true,
                    'pessoa' => null,
                    'message' => 'Pessoa não encontrada'
                ]);
            }
        } catch (\Exception $e) {
            $logger->error("🔴 ERRO CRÍTICO em searchPessoaAdvanced: " . $e->getMessage() . " | Stack: " . $e->getTraceAsString());
            return new JsonResponse([
                'success' => false,
                'message' => 'Erro interno: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Busca telefones de uma pessoa
     */
    private function buscarTelefonesPessoa(int $pessoaId, EntityManagerInterface $entityManager): array
    {
        $telefones = [];

        $pessoasTelefones = $entityManager->getRepository(PessoasTelefones::class)
            ->findBy(['idPessoa' => $pessoaId]);

        foreach ($pessoasTelefones as $pessoaTelefone) {
            $telefone = $entityManager->getRepository(Telefones::class)
                ->find($pessoaTelefone->getIdTelefone());

            if ($telefone) {
                $telefones[] = [
                    'tipo' => $telefone->getTipo()->getId(),
                    'numero' => $telefone->getNumero()
                ];
            }
        }

        return $telefones;
    }

    /**
     * Busca endereços de uma pessoa
     */
    private function buscarEnderecosPessoa(int $pessoaId, EntityManagerInterface $entityManager): array
    {
        $enderecos = [];

        $enderecosEntidade = $entityManager->getRepository(Enderecos::class)
            ->findBy(['pessoa' => $pessoaId]);

        foreach ($enderecosEntidade as $endereco) {
            $logradouro = $endereco->getLogradouro();
            $bairro = $logradouro ? $logradouro->getBairro() : null;
            $cidade = $bairro ? $bairro->getCidade() : null;
            $estado = $cidade ? $cidade->getEstado() : null;

            $enderecos[] = [
                'tipo' => $endereco->getTipo()->getId(),
                'cep' => $logradouro ? $logradouro->getCep() : '',
                'logradouro' => $logradouro ? $logradouro->getLogradouro() : '',
                'numero' => $endereco->getEndNumero(),
                'complemento' => $endereco->getComplemento(),
                'bairro' => $bairro ? $bairro->getNome() : '',
                'cidade' => $cidade ? $cidade->getNome() : '',
                'estado' => $estado ? $estado->getUf() : ''
            ];
        }

        return $enderecos;
    }

    /**
     * Busca emails de uma pessoa
     */
    private function buscarEmailsPessoa(int $pessoaId, EntityManagerInterface $entityManager): array
    {
        $emails = [];

        $pessoasEmails = $entityManager->getRepository(PessoasEmails::class)
            ->findBy(['idPessoa' => $pessoaId]);

        foreach ($pessoasEmails as $pessoaEmail) {
            $email = $entityManager->getRepository(Emails::class)
                ->find($pessoaEmail->getIdEmail());

            if ($email) {
                $emails[] = [
                    'tipo' => $email->getTipo()->getId(),
                    'email' => $email->getEmail()
                ];
            }
        }

        return $emails;
    }

    /**
     * Busca documentos de uma pessoa (usando Repository)
     */
    private function buscarDocumentosPessoa(int $pessoaId, EntityManagerInterface $entityManager): array
    {
        error_log("🔍 DEBUG: Chamando Repository para pessoa ID: $pessoaId");

        $resultado = $entityManager->getRepository(Pessoas::class)
            ->buscarDocumentosSecundarios($pessoaId);
        // Debug via logs sem quebrar o JSON
        error_log("🔍 DEBUG: Repository retornou: " . print_r($resultado, true));
        error_log("🔍 DEBUG: Tipo do resultado: " . gettype($resultado));
        error_log("🔍 DEBUG: Count do resultado: " . count($resultado));

        return $resultado;
    }

    /**
     * Busca chaves PIX de uma pessoa
     */
    private function buscarChavesPixPessoa(int $pessoaId, EntityManagerInterface $entityManager): array
    {
        $chavesPix = [];
        error_log("Buscando chaves PIX para pessoa ID: " . var_export($pessoaId, true));
        $chavesPixEntidade = $entityManager->getRepository(ChavesPix::class)
            ->findBy([
                'idPessoa' => (int) $pessoaId, // ✅ Garantir int
                'ativo' => true
            ]);
        error_log("Chaves encontradas: " . count($chavesPixEntidade));
        foreach ($chavesPixEntidade as $chavePix) {
            $chavesPix[] = [
                'tipo' => $chavePix->getIdTipoChave(),
                'chave' => $chavePix->getChavePix(),
                'principal' => $chavePix->getPrincipal()
            ];
        }

        return $chavesPix;
    }

    /**
     * Busca profissões de uma pessoa
     */
    private function buscarProfissoesPessoa(int $pessoaId, EntityManagerInterface $entityManager): array
    {
        $profissoes = [];

        $pessoasProfissoes = $entityManager->getRepository(PessoasProfissoes::class)
            ->findBy(['idPessoa' => $pessoaId, 'ativo' => true]);

        foreach ($pessoasProfissoes as $pessoaProfissao) {
            $profissoes[] = [
                'profissao' => $pessoaProfissao->getIdProfissao(),
                'empresa' => $pessoaProfissao->getEmpresa(),
                'renda' => $pessoaProfissao->getRenda(),
                'dataAdmissao' => $pessoaProfissao->getDataAdmissao() ? $pessoaProfissao->getDataAdmissao()->format('Y-m-d') : null,
                'dataDemissao' => $pessoaProfissao->getDataDemissao() ? $pessoaProfissao->getDataDemissao()->format('Y-m-d') : null,
                'observacoes' => $pessoaProfissao->getObservacoes()
            ];
        }

        return $profissoes;
    }

    /**
     * Busca cônjuge de uma pessoa
     */
    private function buscarConjugePessoa(int $pessoaId, EntityManagerInterface $entityManager): ?array
    {
        $relacionamento = $entityManager->getRepository(RelacionamentosFamiliares::class)
            ->findOneBy([
                'idPessoaOrigem' => $pessoaId,
                'tipoRelacionamento' => 'Cônjuge',
                'ativo' => true
            ]);

        if (!$relacionamento) {
            return null;
        }

        $conjuge = $entityManager->getRepository(Pessoas::class)
            ->find($relacionamento->getIdPessoaDestino());

        if (!$conjuge) {
            return null;
        }

        $pessoaRepository = $entityManager->getRepository(Pessoas::class);
        $cpfConjuge = $pessoaRepository->getCpfByPessoa($conjuge->getIdpessoa());

        // Buscar todos os dados múltiplos do cônjuge também
        $telefonesConjuge = $this->buscarTelefonesPessoa($conjuge->getIdpessoa(), $entityManager);
        $enderecosConjuge = $this->buscarEnderecosPessoa($conjuge->getIdpessoa(), $entityManager);
        $emailsConjuge = $this->buscarEmailsPessoa($conjuge->getIdpessoa(), $entityManager);
        $documentosConjuge = $this->buscarDocumentosPessoa($conjuge->getIdpessoa(), $entityManager);
        $chavesPixConjuge = $this->buscarChavesPixPessoa($conjuge->getIdpessoa(), $entityManager);
        $profissoesConjuge = $this->buscarProfissoesPessoa($conjuge->getIdpessoa(), $entityManager);

        return [
            'id' => $conjuge->getIdpessoa(),
            'nome' => $conjuge->getNome(),
            'cpf' => $cpfConjuge,
            'dataNascimento' => $conjuge->getDataNascimento() ? $conjuge->getDataNascimento()->format('Y-m-d') : null,
            'estadoCivil' => $conjuge->getEstadoCivil() ? $conjuge->getEstadoCivil()->getId() : null,
            'nacionalidade' => $conjuge->getNacionalidade() ? $conjuge->getNacionalidade()->getId() : null,
            'naturalidade' => $conjuge->getNaturalidade() ? $conjuge->getNaturalidade()->getId() : null,
            'nomePai' => $conjuge->getNomePai(),
            'nomeMae' => $conjuge->getNomeMae(),
            'renda' => $conjuge->getRenda(),
            'observacoes' => $conjuge->getObservacoes(),
            'telefones' => $telefonesConjuge,
            'enderecos' => $enderecosConjuge,
            'emails' => $emailsConjuge,
            'documentos' => $documentosConjuge,
            'chavesPix' => $chavesPixConjuge,
            'profissoes' => $profissoesConjuge
        ];
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
        if ($this->isCsrfTokenValid('delete' . $pessoa->getIdpessoa(), $request->request->get('_token'))) {
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
            $pessoaDocumento->setPessoa($pessoa);
            $tipoDocumentoEntity = $entityManager->getRepository(TiposDocumentos::class)
                ->findOneBy(['tipo' => 'CPF']); // ou 'CNPJ', depende do contexto

            if ($tipoDocumentoEntity) {
                $pessoaDocumento->setTipoDocumento($tipoDocumentoEntity);
            }
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
                $contratante->setPessoa($pessoa); // CORRETO: Usar setPessoa
                $entityManager->persist($contratante);
                break;

            case 'corretora':
                $corretora = new \App\Entity\PessoasCorretoras();
                $corretora->setPessoa($pessoa); // CORRETO: Usar setPessoa
                $entityManager->persist($corretora);
                break;
        }
    }

    /**
     * Processa alterações específicas do cônjuge na edição
     */
    private function processarConjugeEdicao(Pessoas $pessoa, array $requestData, EntityManagerInterface $entityManager): void
    {
        // Verificar se há relacionamento de cônjuge existente
        $relacionamentoExistente = $entityManager->getRepository(RelacionamentosFamiliares::class)
            ->findOneBy([
                'idPessoaOrigem' => $pessoa->getIdpessoa(),
                'tipoRelacionamento' => 'Cônjuge',
                'ativo' => true
            ]);

        // Se existe cônjuge e usuário desmarcou, remover relacionamento
        $temConjuge = !empty($requestData['novo_conjuge']) || !empty($requestData['conjuge_id']);

        if ($relacionamentoExistente && !$temConjuge) {
            // Remover relacionamento bidirecional
            $relacionamentoInverso = $entityManager->getRepository(RelacionamentosFamiliares::class)
                ->findOneBy([
                    'idPessoaOrigem' => $relacionamentoExistente->getIdPessoaDestino(),
                    'idPessoaDestino' => $pessoa->getIdpessoa(),
                    'tipoRelacionamento' => 'Cônjuge'
                ]);

            $entityManager->remove($relacionamentoExistente);
            if ($relacionamentoInverso) {
                $entityManager->remove($relacionamentoInverso);
            }
        }

        // Se há dados de cônjuge, processar normalmente (novo ou alterado)
        if ($temConjuge) {
            $this->salvarConjuge($pessoa, $requestData, $entityManager);
        }
    }

    private function salvarConjuge(Pessoas $pessoa, array $requestData, EntityManagerInterface $entityManager): void
    {
        // DEBUG: Vamos ver o que está sendo enviado
        error_log('DEBUG salvarConjuge - requestData keys: ' . implode(', ', array_keys($requestData)));
        error_log('DEBUG salvarConjuge - novo_conjuge: ' . json_encode($requestData['novo_conjuge'] ?? 'não definido'));
        error_log('DEBUG salvarConjuge - conjuge_id: ' . json_encode($requestData['conjuge_id'] ?? 'não definido'));

        $pessoaRepository = $entityManager->getRepository(Pessoas::class);
        $conjugeParaRelacionar = null;

        // Caso 1: Tenta encontrar um cônjuge existente pelo ID vindo do formulário
        $conjugeId = $requestData['conjuge']['id'] ?? $requestData['conjuge_id'] ?? null;
        if ($conjugeId) {
            error_log('DEBUG: Tentando encontrar cônjuge existente com ID: ' . $conjugeId);
            $conjugeParaRelacionar = $pessoaRepository->find($conjugeId);
        }

        // Caso 2: Se não encontrou um existente, cria um novo cônjuge com os dados do formulário
        $novoConjugeData = $requestData['novo_conjuge'] ?? null;
        if (!$conjugeParaRelacionar && $novoConjugeData && !empty($novoConjugeData['nome']) && !empty($novoConjugeData['cpf'])) {
            error_log('DEBUG: Criando novo cônjuge com dados: ' . json_encode($novoConjugeData));
            $novoConjuge = new Pessoas();
            $novoConjuge->setNome($novoConjugeData['nome']);

            if (!empty($novoConjugeData['data_nascimento'])) {
                $novoConjuge->setDataNascimento(new \DateTime($novoConjugeData['data_nascimento']));
            }
            if (!empty($novoConjugeData['estado_civil'])) {
                $estadoCivil = $entityManager->getReference(EstadoCivil::class, $novoConjugeData['estado_civil']);
                $novoConjuge->setEstadoCivil($estadoCivil);
            }
            if (!empty($novoConjugeData['nacionalidade'])) {
                $nacionalidade = $entityManager->getReference(Nacionalidade::class, $novoConjugeData['nacionalidade']);
                $novoConjuge->setNacionalidade($nacionalidade);
            }
            if (!empty($novoConjugeData['naturalidade'])) {
                $naturalidade = $entityManager->getReference(Naturalidade::class, $novoConjugeData['naturalidade']);
                $novoConjuge->setNaturalidade($naturalidade);
            }

            $novoConjuge->setNomePai($novoConjugeData['nome_pai'] ?? null);
            $novoConjuge->setNomeMae($novoConjugeData['nome_mae'] ?? null);
            $novoConjuge->setRenda((float)($novoConjugeData['renda'] ?? 0.0));
            $novoConjuge->setObservacoes($novoConjugeData['observacoes'] ?? null);

            $novoConjuge->setFisicaJuridica('fisica');
            $novoConjuge->setDtCadastro(new \DateTime());
            $novoConjuge->setStatus(true);
            $novoConjuge->setTipoPessoa(1); // Tipo padrão para cônjuge

            $entityManager->persist($novoConjuge);
            $entityManager->flush(); // Flush para obter ID do cônjuge

            // Salva o documento principal (CPF) do novo cônjuge
            $this->salvarDocumentoPrincipalConjuge($novoConjuge->getIdpessoa(), $novoConjugeData['cpf'], $entityManager);

            // Salvar TODOS os dados do cônjuge como pessoa completa
            $this->salvarDadosMultiplosConjuge($novoConjuge->getIdpessoa(), $requestData, $entityManager);

            $conjugeParaRelacionar = $novoConjuge;
        }

        // Se encontrou ou criou um cônjuge, estabelece o relacionamento familiar
        if ($conjugeParaRelacionar) {
            error_log('DEBUG: Estabelecendo relacionamento familiar com cônjuge ID: ' . $conjugeParaRelacionar->getIdpessoa());
            $relacionamentoRepo = $entityManager->getRepository(RelacionamentosFamiliares::class);

            // Verifica se o relacionamento já existe para não duplicar
            $existente = $relacionamentoRepo->findOneBy([
                'idPessoaOrigem' => $pessoa->getIdpessoa(),
                'idPessoaDestino' => $conjugeParaRelacionar->getIdpessoa(),
                'tipoRelacionamento' => 'Cônjuge'
            ]);

            if (!$existente) {
                $dataInicioRelacionamento = new \DateTime();

                // Cria o relacionamento da pessoa principal para o cônjuge
                $relacionamento1 = new RelacionamentosFamiliares();
                $relacionamento1->setIdPessoaOrigem($pessoa->getIdpessoa());
                $relacionamento1->setIdPessoaDestino($conjugeParaRelacionar->getIdpessoa());
                $relacionamento1->setTipoRelacionamento('Cônjuge');
                $relacionamento1->setAtivo(true);
                $relacionamento1->setDataInicio($dataInicioRelacionamento);
                $entityManager->persist($relacionamento1);

                // Cria o relacionamento do cônjuge para a pessoa principal (bidirecional)
                $relacionamento2 = new RelacionamentosFamiliares();
                $relacionamento2->setIdPessoaOrigem($conjugeParaRelacionar->getIdpessoa());
                $relacionamento2->setIdPessoaDestino($pessoa->getIdpessoa());
                $relacionamento2->setTipoRelacionamento('Cônjuge');
                $relacionamento2->setAtivo(true);
                $relacionamento2->setDataInicio($dataInicioRelacionamento);
                $entityManager->persist($relacionamento2);
            }
        } else {
            error_log('DEBUG: Nenhum cônjuge para relacionar - saindo sem fazer nada');
        }
    }

    /**
     * Salvar documento principal do cônjuge
     */
    private function salvarDocumentoPrincipalConjuge(int $conjugeId, string $documento, EntityManagerInterface $entityManager): void
    {
        $documento = preg_replace('/[^\d]/', '', $documento);
        $tipoDocumento = strlen($documento) === 11 ? 'CPF' : 'CNPJ';

        $tipoDocumentoEntity = $entityManager->getRepository(TiposDocumentos::class)
            ->findOneBy(['tipo' => $tipoDocumento]);

        if ($tipoDocumentoEntity) {
            $pessoaDocumento = new PessoasDocumentos();
            $conjuge = $entityManager->getRepository(Pessoas::class)->getIdpessoa($conjugeId);
            $pessoaDocumento->setPessoa($conjuge);
            $tipoDocumentoEntity = $entityManager->getRepository(TiposDocumentos::class)
                ->findOneBy(['tipo' => 'CPF']); // ou 'CNPJ'

            if ($tipoDocumentoEntity) {
                $pessoaDocumento->setTipoDocumento($tipoDocumentoEntity);
            }
            $pessoaDocumento->setNumeroDocumento($documento);
            $pessoaDocumento->setAtivo(true);

            $entityManager->persist($pessoaDocumento);
        }
    }

    /**
     * Salva todos os dados múltiplos específicos do cônjuge
     */
    private function salvarDadosMultiplosConjuge(int $conjugeId, array $requestData, EntityManagerInterface $entityManager): void
    {
        // Salvar Telefones do Cônjuge
        if (isset($requestData['conjuge_telefones']) && is_array($requestData['conjuge_telefones'])) {
            foreach ($requestData['conjuge_telefones'] as $telefoneData) {
                if (!empty($telefoneData['tipo']) && !empty($telefoneData['numero'])) {
                    $telefone = new Telefones();
                    $telefone->setTipo($entityManager->getReference(\App\Entity\TiposTelefones::class, (int)$telefoneData['tipo']));
                    $telefone->setNumero($telefoneData['numero']);
                    $entityManager->persist($telefone);
                    $entityManager->flush(); // FLUSH para obter ID

                    $pessoaTelefone = new PessoasTelefones();
                    $pessoaTelefone->setIdPessoa($conjugeId);
                    $pessoaTelefone->setIdTelefone($telefone->getId());
                    $entityManager->persist($pessoaTelefone);
                }
            }
        }

        // Salvar Emails do Cônjuge
        if (isset($requestData['conjuge_emails']) && is_array($requestData['conjuge_emails'])) {
            foreach ($requestData['conjuge_emails'] as $emailData) {
                if (!empty($emailData['tipo']) && !empty($emailData['email'])) {
                    $email = new Emails();
                    $email->setEmail($emailData['email']);
                    $email->setTipo($entityManager->getReference(\App\Entity\TiposEmails::class, (int)$emailData['tipo']));
                    $entityManager->persist($email);
                    $entityManager->flush(); // FLUSH para obter ID

                    $pessoaEmail = new PessoasEmails();
                    $pessoaEmail->setIdPessoa($conjugeId);
                    $pessoaEmail->setIdEmail($email->getId());
                    $entityManager->persist($pessoaEmail);
                }
            }
        }

        // Salvar Endereços do Cônjuge - REMOVIDO TEMPORARIAMENTE
        // TODO: Corrigir quando souber o nome correto do setter
        /*
        if (isset($requestData['conjuge_enderecos']) && is_array($requestData['conjuge_enderecos'])) {
            foreach ($requestData['conjuge_enderecos'] as $enderecoData) {
                if (!empty($enderecoData['tipo']) && !empty($enderecoData['numero'])) {
                    $logradouroId = $this->buscarOuCriarLogradouro($enderecoData, $entityManager);
                    
                    $endereco = new Enderecos();
                    // $endereco->setIdPessoa($conjugeId); // CORRIGIR: verificar método correto
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
        */

        // Salvar Chaves PIX do Cônjuge
        if (isset($requestData['conjuge_chaves_pix']) && is_array($requestData['conjuge_chaves_pix'])) {
            foreach ($requestData['conjuge_chaves_pix'] as $pixData) {
                if (!empty($pixData['tipo']) && !empty($pixData['chave'])) {
                    $chavePix = new ChavesPix();
                    $chavePix->setIdPessoa($conjugeId);
                    $chavePix->setIdTipoChave((int)$pixData['tipo']);
                    $chavePix->setChavePix($pixData['chave']);
                    $chavePix->setPrincipal(!empty($pixData['principal']));
                    $chavePix->setAtivo(true);
                    $entityManager->persist($chavePix);
                }
            }
        }

        // Salvar Documentos do Cônjuge
        if (isset($requestData['conjuge_documentos']) && is_array($requestData['conjuge_documentos'])) {
            foreach ($requestData['conjuge_documentos'] as $documentoData) {
                if (!empty($documentoData['tipo']) && !empty($documentoData['numero'])) {
                    $documento = new PessoasDocumentos();
                    $conjuge = $entityManager->getRepository(Pessoas::class)->getIdpessoa($conjugeId);
                    $documento->setPessoa($conjuge);
                    $tipoDocumentoEntity = $entityManager->getRepository(TiposDocumentos::class)
                        ->findOneBy(['tipo' => 'CPF']); // ou 'CNPJ', depende do contexto

                    if ($tipoDocumentoEntity) {
                        $documento->setTipoDocumento($tipoDocumentoEntity);
                    }
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

        // Salvar Profissões do Cônjuge
        if (isset($requestData['conjuge_profissoes']) && is_array($requestData['conjuge_profissoes'])) {
            foreach ($requestData['conjuge_profissoes'] as $profissaoData) {
                if (!empty($profissaoData['profissao'])) {
                    $pessoaProfissao = new \App\Entity\PessoasProfissoes();
                    $pessoaProfissao->setIdPessoa($conjugeId);
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

    private function buscarOuCriarLogradouro(array $enderecoData, EntityManagerInterface $entityManager): int
    {
        // Buscar bairro ou criar
        $bairroRepository = $entityManager->getRepository(Bairros::class);
        $bairro = $bairroRepository->findOneBy(['nome' => $enderecoData['bairro']]);

        if (!$bairro) {
            // Buscar cidade ou criar
            $cidadeRepository = $entityManager->getRepository(Cidades::class);
            $cidade = $cidadeRepository->findOneBy(['nome' => $enderecoData['cidade']]);

            if (!$cidade) {
                // Buscar ou criar estado
                $estadoRepository = $entityManager->getRepository(Estados::class);
                $estado = $estadoRepository->findOneBy(['uf' => $enderecoData['estado'] ?? 'SP']);

                if (!$estado) {
                    $estado = new Estados();
                    $estado->setUf($enderecoData['estado'] ?? 'SP');
                    $estado->setNome($enderecoData['estado'] ?? 'São Paulo');
                    $entityManager->persist($estado);
                    $entityManager->flush();
                }

                // Criar cidade
                $cidade = new Cidades();
                $cidade->setNome($enderecoData['cidade']);
                $cidade->setEstado($estado);
                $entityManager->persist($cidade);
                $entityManager->flush();
            }

            // Criar bairro
            $bairro = new Bairros();
            $bairro->setNome($enderecoData['bairro']);
            $bairro->setCidade($cidade);
            $entityManager->persist($bairro);
            $entityManager->flush();
        }

        // Buscar logradouro ou criar
        $logradouroRepository = $entityManager->getRepository(Logradouros::class);
        $logradouro = $logradouroRepository->findOneBy([
            'logradouro' => $enderecoData['logradouro'],
            'bairro' => $bairro
        ]);

        if (!$logradouro) {
            $logradouro = new Logradouros();
            $logradouro->setLogradouro($enderecoData['logradouro']);
            $logradouro->setBairro($bairro);

            if (!empty($enderecoData['cep'])) {
                $logradouro->setCep(preg_replace('/\D/', '', $enderecoData['cep']));
            } else {
                $logradouro->setCep('00000000');
            }

            $entityManager->persist($logradouro);
            $entityManager->flush();
        }

        return $logradouro->getId();
    }
}
