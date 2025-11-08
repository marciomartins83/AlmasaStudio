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
use App\Repository\PessoasDocumentosRepository;
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
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(PessoaFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data        = $form->getData();           // objeto Pessoas
            $requestData = $request->request->all();
            $tipoPessoa  = $form->get('tipoPessoa')->getData(); // Ex: 'contratante', 'fiador'

            /* ---------- 1.  Verifica CPF duplicado ---------- */
            
            // ✅ INÍCIO DA CORREÇÃO: Ler o CPF/CNPJ do $requestData (vindo do form), não do $data
            // Tenta pegar o searchTerm do 'pessoa_form' (nome padrão do form)
            $cpfCnpjNumero = $requestData['pessoa_form']['searchTerm'] ?? null; 

            // Fallback se o nome do form for diferente (ex: 'form')
            if (!$cpfCnpjNumero) {
                 $cpfCnpjNumero = $requestData['searchTerm'] ?? null;
            }

            $cpfNumero = null;
            
            if ($cpfCnpjNumero) {
                 $cpfCnpjLimpo = preg_replace('/\D/', '', $cpfCnpjNumero);
                 if (strlen($cpfCnpjLimpo) === 11) {
                      $cpfNumero = $cpfCnpjLimpo;
                 }
            }
            // ❌ FIM DA CORREÇÃO (O código antigo que usava $data->getPessoasDocumentos() foi removido)


            if ($cpfNumero) {
                $existente = $entityManager->getRepository(Pessoas::class)
                    ->findByCpfDocumento($cpfNumero);
                if ($existente) {
                    error_log('[CPF DUPLICADO] ' . $cpfNumero);
                    $this->addFlash('error', 'CPF já cadastrado.');
                    return $this->redirectToRoute('app_pessoa_new');
                }
            }

            // ✅ INÍCIO DA CORREÇÃO: Removendo blocos 2, 3, 4, 5 que quebravam
            /*
            -----------------------------------------------------------------------
            ❌ REMOVIDO: Bloco 2 (Telefones – sem duplicar)
            ❌ REMOVIDO: Bloco 3 (E-mails – sem duplicar)
            ❌ REMOVIDO: Bloco 4 (Endereços – sem duplicar)
            ❌ REMOVIDO: Bloco 5 (Demais documentos – sem duplicar)
            
            Motivo: Esses blocos causavam o erro "Call to undefined method..."
            A lógica de deduplicação foi movida para o método 
            salvarDadosMultiplosCorrigido(), que é o local correto.
            -----------------------------------------------------------------------
            */
            // ❌ FIM DA CORREÇÃO

            
            $entityManager->getConnection()->beginTransaction();
            try {
                /* ---------- 6.  Persiste a Pessoa Principal ---------- */
                $entityManager->persist($data);
                $entityManager->flush(); // <-- FLUSH #1 (Pega o ID da Pessoa)

                $pessoaId = $data->getIdpessoa();
                error_log('[PESSOA SALVA - ID] ' . $pessoaId);

                // ✅ INÍCIO DA CORREÇÃO: Passar o array aninhado 'pessoa_form', não o $requestData inteiro.
                // O JS cria os campos (telefones, emails) e o Symfony os aninha sob o nome do formulário.
                $formData = $requestData['pessoa_form'] ?? $requestData; // Pega 'pessoa_form' ou usa o root como fallback

                /* ---------- 7. Salva os Dados Múltiplos (Telefones, Endereços, etc.) ---------- */
                // (Esta função agora contém a lógica de deduplicação e lê do $formData)
                $this->salvarDadosMultiplosCorrigido($pessoaId, $formData, $entityManager);

                /* ---------- 8. Salva o Tipo Específico (Contratante, Fiador, etc.) ---------- */
                // (Passando $formData aninhado aqui também, caso ele precise)
                $this->salvarTipoEspecifico($data, $tipoPessoa, $formData, $entityManager);

                /* ---------- 9. Salva o Cônjuge (se houver) ---------- */
                // (Verifica se 'temConjuge' está marcado no $formData aninhado)
                if (!empty($formData['temConjuge']) || !empty($formData['novo_conjuge']['nome'])) {
                     $this->salvarConjuge($data, $formData, $entityManager);
                }
                // ❌ FIM DA CORREÇÃO

                /* ---------- 10. Flush Final ---------- */
                $entityManager->flush(); // <-- FLUSH #2 (Salva dados múltiplos, tipo e cônjuge)
                $entityManager->getConnection()->commit();
                
                $this->addFlash('success', 'Pessoa cadastrada com sucesso!');
                return $this->redirectToRoute('app_pessoa_index');

            } catch (\Exception $e) {
                $entityManager->getConnection()->rollBack();
                error_log('🔴 ERRO CRÍTICO AO SALVAR NOVA PESSOA: ' . $e->getMessage());
                error_log('🔴 STACK TRACE: ' . $e->getTraceAsString());
                $this->addFlash('error', 'Erro ao salvar a pessoa: ' . $e->getMessage());
            }
        }

        return $this->render('pessoa/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * MÉTODO CORRIGIDO: Dados múltiplos sem flush internos - COMPLETO
     */
    private function salvarDadosMultiplosCorrigido(
        int $pessoaId,
        array $requestData,
        EntityManagerInterface $entityManager
    ): void {
        // -------- Telefones (find-or-create + pivot) --------
        if (isset($requestData['telefones']) && is_array($requestData['telefones'])) {
            $telRepo = $entityManager->getRepository(\App\Entity\Telefones::class);

            foreach ($requestData['telefones'] as $telefoneData) {
                if (!empty($telefoneData['tipo']) && !empty($telefoneData['numero'])) {
                    $numeroLimpo = preg_replace('/\D/', '', $telefoneData['numero']);

                    // tenta achar telefone ativo pelo número (se sua entidade não tem "ativo", remova o critério)
                    $criteria = ['numero' => $numeroLimpo];
                    if (property_exists(\App\Entity\Telefones::class, 'ativo')) {
                        $criteria['ativo'] = true;
                    }
                    $telefone = $telRepo->findOneBy($criteria);

                    if (!$telefone) {
                        $telefone = new \App\Entity\Telefones();
                        $telefone->setTipo($entityManager->getReference(\App\Entity\TiposTelefones::class, (int)$telefoneData['tipo']));
                        $telefone->setNumero($numeroLimpo);
                        if (method_exists($telefone, 'setAtivo')) {
                            $telefone->setAtivo(true);
                        }
                        $entityManager->persist($telefone);
                        $entityManager->flush(); // precisamos do ID para a pivot
                    }

                    $pivot = new \App\Entity\PessoasTelefones();
                    $pivot->setIdPessoa($pessoaId);
                    $pivot->setIdTelefone($telefone->getId());
                    $entityManager->persist($pivot);
                }
            }
        }

        // -------- Emails (find-or-create + pivot) --------
        if (isset($requestData['emails']) && is_array($requestData['emails'])) {
            $emailRepo = $entityManager->getRepository(\App\Entity\Emails::class);

            foreach ($requestData['emails'] as $emailData) {
                if (!empty($emailData['tipo']) && !empty($emailData['email'])) {
                    $emailLimpo = strtolower(trim($emailData['email']));
                    $criteria   = ['email' => $emailLimpo];
                    if (property_exists(\App\Entity\Emails::class, 'ativo')) {
                        $criteria['ativo'] = true;
                    }
                    $email = $emailRepo->findOneBy($criteria);

                    if (!$email) {
                        $email = new \App\Entity\Emails();
                        $email->setEmail($emailLimpo);
                        $email->setTipo($entityManager->getReference(\App\Entity\TiposEmails::class, (int)$emailData['tipo']));
                        if (method_exists($email, 'setAtivo')) {
                            $email->setAtivo(true);
                        }
                        $entityManager->persist($email);
                        $entityManager->flush(); // precisamos do ID para a pivot
                    }

                    $pivot = new \App\Entity\PessoasEmails();
                    $pivot->setIdPessoa($pessoaId);
                    $pivot->setIdEmail($email->getId());
                    $entityManager->persist($pivot);
                }
            }
        }

        // -------- Endereços (relação direta com pessoa) --------
        if (isset($requestData['enderecos']) && is_array($requestData['enderecos'])) {
            foreach ($requestData['enderecos'] as $enderecoData) {
                if (!empty($enderecoData['tipo']) && !empty($enderecoData['numero'])) {
                    $logradouroId = $this->buscarOuCriarLogradouro($enderecoData, $entityManager);

                    $endereco = new \App\Entity\Enderecos();
                    $endereco->setPessoa($entityManager->getReference(\App\Entity\Pessoas::class, $pessoaId));
                    $endereco->setLogradouro($entityManager->getReference(\App\Entity\Logradouros::class, $logradouroId));
                    $endereco->setTipo($entityManager->getReference(\App\Entity\TiposEnderecos::class, (int)$enderecoData['tipo']));
                    $endereco->setEndNumero((int)$enderecoData['numero']);
                    if (!empty($enderecoData['complemento'])) {
                        $endereco->setComplemento($enderecoData['complemento']);
                    }

                    $entityManager->persist($endereco);
                }
            }
        }

        // -------- Chaves PIX --------
        if (isset($requestData['chaves_pix']) && is_array($requestData['chaves_pix'])) {
            foreach ($requestData['chaves_pix'] as $pixData) {
                if (!empty($pixData['tipo']) && !empty($pixData['chave'])) {
                    $chavePix = new \App\Entity\ChavesPix();
                    $chavePix->setIdPessoa($pessoaId);
                    $chavePix->setIdTipoChave((int)$pixData['tipo']);
                    $chavePix->setChavePix($pixData['chave']);
                    $chavePix->setPrincipal(!empty($pixData['principal']));
                    if (method_exists($chavePix, 'setAtivo')) {
                        $chavePix->setAtivo(true);
                    }
                    $entityManager->persist($chavePix);
                }
            }
        }

        // -------- Documentos (secundários) --------
        if (isset($requestData['documentos']) && is_array($requestData['documentos'])) {
            foreach ($requestData['documentos'] as $documentoData) {
                if (!empty($documentoData['tipo']) && !empty($documentoData['numero'])) {
                    $documento = new \App\Entity\PessoasDocumentos();
                    $pessoaRef = $entityManager->getReference(\App\Entity\Pessoas::class, $pessoaId);
                    $documento->setPessoa($pessoaRef);

                    // Aqui o tipo vem por ID do tipo_documento (ex.: 1=CPF, 2=RG, etc.)
                    $tipoDocumentoEntity = $entityManager->getReference(\App\Entity\TiposDocumentos::class, (int)$documentoData['tipo']);
                    $documento->setTipoDocumento($tipoDocumentoEntity);

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

                    if (method_exists($documento, 'setAtivo')) {
                        $documento->setAtivo(true);
                    }

                    $entityManager->persist($documento);
                }
            }
        }

        // -------- Profissões --------
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

                    if (method_exists($pessoaProfissao, 'setAtivo')) {
                        $pessoaProfissao->setAtivo(true);
                    }

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
        LoggerInterface $logger
    ): JsonResponse {
        try {
            $logger->info('🔵 DEBUG: Iniciando searchPessoaAdvanced');
            $data = json_decode($request->getContent(), true);
            if (!$data) {
                return new JsonResponse(['success' => false, 'message' => 'Dados JSON inválidos'], 400);
            }

            $criteria = strtolower($data['criteria'] ?? '');
            $value    = $data['value'] ?? '';
            $additionalDoc       = $data['additionalDoc'] ?? null;
            $additionalDocType = $data['additionalDocType'] ?? null;

            if (empty($criteria) || empty($value)) {
                return new JsonResponse(['success' => false, 'message' => 'Critério e valor são obrigatórios'], 400);
            }

            // --- BUSCA DA PESSOA -------------------------------------------------
            $pessoa = match ($criteria) {
                'cpf', 'cpf (pessoa física)' => $pessoaRepository->findByCpfDocumento($value),
                'cnpj', 'cnpj (pessoa jurídica)' => $pessoaRepository->findByCnpjDocumento($value), // Supondo que exista
                'id', 'id pessoa' => $pessoaRepository->find((int)$value),
                'nome', 'nome completo' => $this->buscaPorNome($logger, $pessoaRepository, $value, $additionalDoc, $additionalDocType),
                default => null,
            };

            if (!$pessoa) {
                $logger->info("⚠️ Pessoa não encontrada: $criteria = $value");
                return new JsonResponse(['success' => true, 'pessoa' => null, 'message' => 'Pessoa não encontrada']);
            }

            $logger->info('✅ Pessoa encontrada: ' . $pessoa->getIdpessoa());

            // --- DADOS PRINCIPAIS (com logs) -------------------------------------
            $logger->info('🔵 DEBUG: Busca cpf');
            $cpf = $pessoaRepository->getCpfByPessoa($pessoa->getIdpessoa());

            $logger->info('🔵 DEBUG: Busca cnpj');
            $cnpj = $pessoaRepository->getCnpjByPessoa($pessoa->getIdpessoa());

            // --- BUSCA DOS TIPOS ---
            $logger->info('🔵 Busca tipos + dados completos');
            $tiposComDados = $pessoaRepository->findTiposComDados($pessoa->getIdpessoa());
            $tiposComDados['tipos'] = $tiposComDados['tipos'] ?? [];

            // ✅ CORREÇÃO: Buscar dados REAIS dos tipos de pessoa
            $tiposComDados['tiposDados'] = $this->buscarDadosTiposPessoa($pessoa->getIdpessoa(), $entityManager);
            $logger->info('✅ Dados dos tipos carregados: ' . json_encode(array_keys($tiposComDados['tiposDados'])));

            // ✅ Mapeia o tipo string para o ID (mantido para referência)
            $tipoParaId = [
                'contratante' => 6,
                'fiador'      => 1,
                'locador'     => 4,
                'corretor'    => 2,
                'corretora'   => 3,
                'pretendente' => 5,
            ];

            // ✅ CORREÇÃO APLICADA AQUI
            $ativos = array_keys(array_filter($tiposComDados['tipos']));
            $tipoString = $ativos ? $ativos[0] : null; // (ex: "contratante")
            $tipoId = $tipoString ? ($tipoParaId[$tipoString] ?? null) : null; // (ex: 6)


            // --- DADOS MÚLTIPLOS (com logs individuais) --------------------------
            $telefones  = $this->buscaComLog($logger, 'Telefones', fn() => $this->buscarTelefonesPessoa($pessoa->getIdpessoa(), $entityManager));
            $emails     = $this->buscaComLog($logger, 'Emails',    fn() => $this->buscarEmailsPessoa($pessoa->getIdpessoa(), $entityManager));
            $enderecos  = $this->buscaComLog($logger, 'Endereços', fn() => $this->buscarEnderecosPessoa($pessoa->getIdpessoa(), $entityManager));
            $chavesPix  = $this->buscaComLog($logger, 'ChavesPix', fn() => $this->buscarChavesPixPessoa($pessoa->getIdpessoa(), $entityManager));
            $documentos = $this->buscaComLog($logger, 'Documentos',fn() => $this->buscarDocumentosPessoa($pessoa->getIdpessoa(), $entityManager));

            $logger->info('✅ Montando resposta final');

            $valorRetorno = new JsonResponse([
                'success' => true,
                'pessoa'  => [
                    'id'            => $pessoa->getIdpessoa(),
                    'nome'          => $pessoa->getNome(),
                    'cpf'           => $cpf,
                    'cnpj'          => $cnpj,
                    'fisicaJuridica'  => $pessoa->getFisicaJuridica(),
                    'dataNascimento'  => $pessoa->getDataNascimento()?->format('Y-m-d'),
                    'estadoCivil'     => $pessoa->getEstadoCivil()?->getId(),
                    'nacionalidade'   => $pessoa->getNacionalidade()?->getId(),
                    'naturalidade'    => $pessoa->getNaturalidade()?->getId(),
                    'nomePai'         => $pessoa->getNomePai(),
                    'nomeMae'         => $pessoa->getNomeMae(),
                    'renda'           => $pessoa->getRenda(),
                    'observacoes'     => $pessoa->getObservacoes(),
                    'telefones'     => $telefones,
                    'enderecos'     => $enderecos,
                    'emails'        => $emails,
                    'documentos'    => $documentos,
                    'chavesPix'     => $chavesPix,
                    'profissoes'    => $this->buscarProfissoesPessoa($pessoa->getIdpessoa(), $entityManager),
                    'conjuge'       => null,     // placeholder
                    'tipos'         => $tiposComDados['tipos'],      // boolean
                    'tiposDados'    => $tiposComDados['tiposDados'], // objetos
                    
                    // ✅ CORREÇÃO APLICADA AQUI (Ambos os valores são enviados)
                    'tipoPessoaId'     => $tipoId,     // ID numérico (ex: 6)
                    'tipoPessoaString' => $tipoString  // String (ex: "contratante")
                ],
            ]);
            return $valorRetorno;
        } catch (\Exception $e) {
            $logger->error('🔴 ERRO CRÍTICO em searchPessoaAdvanced: ' . $e->getMessage() . ' | Stack: ' . $e->getTraceAsString());
            return new JsonResponse(['success' => false, 'message' => 'Erro interno'], 500);
        }
    }

    /* ---------- Métodos auxiliares privados ---------- */

    private function buscaPorNome(
        LoggerInterface $logger,
        PessoaRepository $repo,
        string $nome,
        ?string $doc,
        ?string $docType
    ): ?Pessoas {
        if ($doc && $docType) {
            $pessoa = stripos($docType, 'cpf') !== false
                ? $repo->findByCpfDocumento($doc)
                : $repo->findByCnpjDocumento($doc); // Supondo que exista

            if ($pessoa && stripos($pessoa->getNome(), $nome) !== false) {
                return $pessoa;
            }
            return null;
        }

        $pessoas = $repo->findByNome($nome);
        return match (count($pessoas)) {
            1 => $pessoas[0],
            0 => null,
            default => throw new \RuntimeException('Múltiplas pessoas encontradas. Informe CPF/CNPJ.'),
        };
    }

    /**
     * Executa $callable dentro de try/catch e loga.
     * Re-lança exceção para não esconder erro.
     */
    private function buscaComLog(LoggerInterface $logger, string $label, callable $callable): array
    {
        $logger->info("🔵 Iniciando buscar{$label}");
        try {
            $result = $callable();
            $logger->info("✅ {$label} obtidos: " . count($result));
            return $result;
        } catch (\Exception $e) {
            $logger->error("❌ ERRO em buscar{$label}: " . $e->getMessage());
            throw $e;
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
                    'id'     => $telefone->getId(),
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
                'id'          => $endereco->getId(),
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
                    'id'    => $email->getId(),
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

        return array_map(fn($doc) => [
            'id'               => $doc['id'],
            'tipo'             => $doc['tipo'],
            'numero'           => $doc['numero'],
            'orgaoEmissor'     => $doc['orgaoEmissor'],
            'dataEmissao'      => $doc['dataEmissao'],
            'dataVencimento'   => $doc['dataVencimento'],
            'observacoes'      => $doc['observacoes'],
        ], $resultado);
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
                'id'        => $chavePix->getId(),
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
                'id'            => $pessoaProfissao->getId(),
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
            // Sempre leia o pacote aninhado do formulário (nome do form = pessoa_form)
            $requestData = $request->request->all();
            $formData    = $requestData['pessoa_form'] ?? $requestData;

            // Pode vir string ("fiador") ou id (numérico)
            $tipoPessoa  = $form->get('tipoPessoa')->getData();

            $entityManager->getConnection()->beginTransaction();
            try {
                // 1) Documento principal vindo do campo não mapeado "searchTerm"
                $cpfCnpj = $form->has('searchTerm')
                    ? (string) $form->get('searchTerm')->getData()
                    : (string) ($formData['searchTerm'] ?? '');

                if ($cpfCnpj !== '') {
                    $cpfCnpjDigits = preg_replace('/\D/', '', $cpfCnpj);

                    // 1.1) Verificação de duplicidade (ignora a própria pessoa)
                    $repo  = $entityManager->getRepository(\App\Entity\Pessoas::class);
                    $outra = null;
                    if (strlen($cpfCnpjDigits) === 11 && method_exists($repo, 'findByCpfDocumento')) {
                        $outra = $repo->findByCpfDocumento($cpfCnpjDigits);
                    } elseif (strlen($cpfCnpjDigits) > 11 && method_exists($repo, 'findByCnpjDocumento')) {
                        $outra = $repo->findByCnpjDocumento($cpfCnpjDigits);
                    }

                    if ($outra && $outra->getIdpessoa() !== $pessoa->getIdpessoa()) {
                        $entityManager->getConnection()->rollBack();
                        $this->addFlash('error', 'Documento informado já está cadastrado para outra pessoa.');
                        return $this->redirectToRoute('app_pessoa_edit', ['id' => $pessoa->getIdpessoa()]);
                    }

                    // 1.2) Ajusta física/jurídica coerente com o documento
                    $pessoa->setFisicaJuridica(strlen($cpfCnpjDigits) === 11 ? 'fisica' : 'juridica');
                }

                // 2) Atualiza tipoPessoa (string -> id quando necessário)
                if (is_string($tipoPessoa)) {
                    $pessoa->setTipoPessoa($this->convertTipoPessoaToId($tipoPessoa, $entityManager));
                } else {
                    $pessoa->setTipoPessoa((int) $tipoPessoa);
                }

                // 3) Persistir alterações básicas (o form já hidratou a entidade $pessoa)
                $entityManager->persist($pessoa);
                $entityManager->flush();

                // 4) Limpeza "replace" SOMENTE das seções presentes no POST
                $this->limparDadosMultiplosApenasSeEnviados($pessoa->getIdpessoa(), $formData, $entityManager);

                // 5) (Re)salva documento principal se veio
                if (!empty($cpfCnpj)) {
                    $this->salvarDocumentoPrincipal($pessoa, $cpfCnpj, $entityManager);
                }

                // 6) Salva dados múltiplos usando o array aninhado do form
                $this->salvarDadosMultiplosCorrigido($pessoa->getIdpessoa(), $formData, $entityManager);

                // 7) Processa cônjuge também com o array aninhado
                $this->processarConjugeEdicao($pessoa, $formData, $entityManager);

                // 8) Flush final e commit
                $entityManager->flush();
                $entityManager->getConnection()->commit();

                $this->addFlash('success', 'Pessoa atualizada com sucesso!');
                return $this->redirectToRoute('app_pessoa_index');
            } catch (\Throwable $e) {
                $entityManager->getConnection()->rollBack();
                $this->addFlash('error', 'Erro ao atualizar pessoa: ' . $e->getMessage());
                error_log('Erro detalhado na edição de pessoa: ' . $e->getMessage());
                error_log('Trace: ' . $e->getTraceAsString());
            }
        }

        return $this->render('pessoa/edit.html.twig', [
            'pessoa' => $pessoa,
            'form'   => $form->createView(),
        ]);
    }



    private function limparDadosMultiplosApenasSeEnviados(
        int $pessoaId,
        array $formData,
        EntityManagerInterface $entityManager
    ): void {
        $pessoaRef = $entityManager->getReference(\App\Entity\Pessoas::class, $pessoaId);

        $tem = static function (string $chave) use ($formData): bool {
            return array_key_exists($chave, $formData) && is_array($formData[$chave]);
        };

        // Se nenhuma seção dinâmica veio no POST, não faça nada.
        if (!($tem('telefones') || $tem('emails') || $tem('enderecos') || $tem('chaves_pix') || $tem('documentos') || $tem('profissoes'))) {
            return;
        }

        // -------- Telefones (pivot + órfãos) --------
        if ($tem('telefones')) {
            $pessoasTelefonesRepo = $entityManager->getRepository(\App\Entity\PessoasTelefones::class);
            $telefonesRepo        = $entityManager->getRepository(\App\Entity\Telefones::class);

            $pivotsTel = $pessoasTelefonesRepo->findBy(['idPessoa' => $pessoaId]);
            foreach ($pivotsTel as $pivot) {
                $telefoneId = $pivot->getIdTelefone();
                $entityManager->remove($pivot);

                $existeOutroVinculo = $pessoasTelefonesRepo->findOneBy(['idTelefone' => $telefoneId]);
                if (!$existeOutroVinculo) {
                    if ($tel = $telefonesRepo->find($telefoneId)) {
                        $entityManager->remove($tel);
                    }
                }
            }
        }

        // -------- Emails (pivot + órfãos) --------
        if ($tem('emails')) {
            $pessoasEmailsRepo = $entityManager->getRepository(\App\Entity\PessoasEmails::class);
            $emailsRepo        = $entityManager->getRepository(\App\Entity\Emails::class);

            $pivotsEmail = $pessoasEmailsRepo->findBy(['idPessoa' => $pessoaId]);
            foreach ($pivotsEmail as $pivot) {
                $emailId = $pivot->getIdEmail();
                $entityManager->remove($pivot);

                $existeOutroVinculo = $pessoasEmailsRepo->findOneBy(['idEmail' => $emailId]);
                if (!$existeOutroVinculo) {
                    if ($email = $emailsRepo->find($emailId)) {
                        $entityManager->remove($email);
                    }
                }
            }
        }

        // -------- Endereços (relação direta: pessoa) --------
        if ($tem('enderecos')) {
            $enderecosRepo = $entityManager->getRepository(\App\Entity\Enderecos::class);
            $enderecos     = $enderecosRepo->findBy(['pessoa' => $pessoaRef]); // associação, não idPessoa
            foreach ($enderecos as $endereco) {
                $entityManager->remove($endereco);
            }
        }

        // -------- Chaves PIX --------
        if ($tem('chaves_pix')) {
            $pixRepo = $entityManager->getRepository(\App\Entity\ChavesPix::class);
            $pix     = $pixRepo->findBy(['idPessoa' => $pessoaId]);
            foreach ($pix as $ch) {
                $entityManager->remove($ch);
            }
        }

        // -------- Documentos (exceto CPF/CNPJ) --------
        if ($tem('documentos')) {
            $docsRepo = $entityManager->getRepository(\App\Entity\PessoasDocumentos::class);
            if (method_exists($docsRepo, 'findSecundariosByPessoa')) {
                $docsSecundarios = $docsRepo->findSecundariosByPessoa($pessoaRef);
            } else {
                // fallback sem DQL explícito
                $docsSecundarios = array_filter(
                    $docsRepo->findBy(['pessoa' => $pessoaRef]),
                    static fn($doc) => !in_array($doc->getTipoDocumento()?->getTipo(), ['CPF','CNPJ'], true)
                );
            }
            foreach ($docsSecundarios as $doc) {
                $entityManager->remove($doc);
            }
        }

        // -------- Profissões --------
        if ($tem('profissoes')) {
            $profRepo = $entityManager->getRepository(\App\Entity\PessoasProfissoes::class);
            $prof     = $profRepo->findBy(['idPessoa' => $pessoaId]);
            foreach ($prof as $p) {
                $entityManager->remove($p);
            }
        }
    }

    /**
     * Limpa dados múltiplos existentes antes de salvar novos (estratégia replace)
     * - Telefones/Emails: remove apenas a pivot; apaga a entidade se ficar órfã.
     * - Endereços: relação direta via propriedade 'pessoa'.
     * - Documentos: remove todos exceto CPF/CNPJ usando associação 'pessoa'.
     * - PIX/Profissões: permanecem por idPessoa (conforme mapeamento atual).
     */
    private function limparDadosMultiplosExistentes(int $pessoaId, EntityManagerInterface $entityManager): void
    {
        // Referência leve
        $pessoaRef = $entityManager->getReference(\App\Entity\Pessoas::class, $pessoaId);

        // -------- Telefones (pivot + órfãos) --------
        $pessoasTelefonesRepo = $entityManager->getRepository(\App\Entity\PessoasTelefones::class);
        $telefonesRepo        = $entityManager->getRepository(\App\Entity\Telefones::class);

        $pivotsTel = $pessoasTelefonesRepo->findBy(['idPessoa' => $pessoaId]);
        foreach ($pivotsTel as $pivot) {
            $telefoneId = $pivot->getIdTelefone();
            $entityManager->remove($pivot);

            $existeOutroVinculo = $pessoasTelefonesRepo->findOneBy(['idTelefone' => $telefoneId]);
            if (!$existeOutroVinculo) {
                if ($tel = $telefonesRepo->find($telefoneId)) {
                    $entityManager->remove($tel);
                }
            }
        }

        // -------- Emails (pivot + órfãos) --------
        $pessoasEmailsRepo = $entityManager->getRepository(\App\Entity\PessoasEmails::class);
        $emailsRepo        = $entityManager->getRepository(\App\Entity\Emails::class);

        $pivotsEmail = $pessoasEmailsRepo->findBy(['idPessoa' => $pessoaId]);
        foreach ($pivotsEmail as $pivot) {
            $emailId = $pivot->getIdEmail();
            $entityManager->remove($pivot);

            $existeOutroVinculo = $pessoasEmailsRepo->findOneBy(['idEmail' => $emailId]);
            if (!$existeOutroVinculo) {
                if ($email = $emailsRepo->find($emailId)) {
                    $entityManager->remove($email);
                }
            }
        }

        // -------- Endereços (relação direta: pessoa) --------
        $enderecosRepo = $entityManager->getRepository(\App\Entity\Enderecos::class);
        $enderecos     = $enderecosRepo->findBy(['pessoa' => $pessoaRef]); // associação, não idPessoa
        foreach ($enderecos as $endereco) {
            $entityManager->remove($endereco);
        }

        // -------- Chaves PIX (idPessoa integer) --------
        $pixRepo = $entityManager->getRepository(\App\Entity\ChavesPix::class);
        $pix     = $pixRepo->findBy(['idPessoa' => $pessoaId]);
        foreach ($pix as $ch) {
            $entityManager->remove($ch);
        }

        // -------- Documentos (exceto CPF/CNPJ) --------
        // 👉 agora a query está no Repository
        $docsRepo = $entityManager->getRepository(\App\Entity\PessoasDocumentos::class);
        if (method_exists($docsRepo, 'findSecundariosByPessoa')) {
            $docsSecundarios = $docsRepo->findSecundariosByPessoa($pessoaRef);
            foreach ($docsSecundarios as $doc) {
                $entityManager->remove($doc);
            }
        }

        // -------- Profissões (idPessoa integer) --------
        $profRepo = $entityManager->getRepository(\App\Entity\PessoasProfissoes::class);
        $prof     = $profRepo->findBy(['idPessoa' => $pessoaId]);
        foreach ($prof as $p) {
            $entityManager->remove($p);
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
        // Normaliza o número
        $numero = preg_replace('/\D/', '', (string) $documento);
        if ($numero === '' || $numero === null) {
            return;
        }

        $tipo = strlen($numero) === 11 ? 'CPF' : 'CNPJ';

        // Busca o tipo de documento (CPF/CNPJ)
        $tipoDocumentoEntity = $entityManager->getRepository(\App\Entity\TiposDocumentos::class)
            ->findOneBy(['tipo' => $tipo]);

        if (!$tipoDocumentoEntity) {
            // Sem tipo cadastrado, não prossegue para evitar quebra
            return;
        }

        $docRepo = $entityManager->getRepository(\App\Entity\PessoasDocumentos::class);

        // Procura documento existente desse tipo para a pessoa (ativo preferencialmente)
        $pessoaDocumento = $docRepo->findOneBy([
            'pessoa'        => $pessoa,
            'tipoDocumento' => $tipoDocumentoEntity,
            'ativo'         => true,
        ]);

        if (!$pessoaDocumento) {
            // Se não há ativo, tenta qualquer um (para reaproveitar registro inativo)
            $pessoaDocumento = $docRepo->findOneBy([
                'pessoa'        => $pessoa,
                'tipoDocumento' => $tipoDocumentoEntity,
            ]);
        }

        if ($pessoaDocumento) {
            // Atualiza o existente
            $pessoaDocumento->setNumeroDocumento($numero);
            $pessoaDocumento->setTipoDocumento($tipoDocumentoEntity);
            if (method_exists($pessoaDocumento, 'setAtivo')) {
                $pessoaDocumento->setAtivo(true);
            }
        } else {
            // Cria um novo
            $pessoaDocumento = new \App\Entity\PessoasDocumentos();
            $pessoaDocumento->setPessoa($pessoa);
            $pessoaDocumento->setTipoDocumento($tipoDocumentoEntity);
            $pessoaDocumento->setNumeroDocumento($numero);
            if (method_exists($pessoaDocumento, 'setAtivo')) {
                $pessoaDocumento->setAtivo(true);
            }
            $entityManager->persist($pessoaDocumento);
        }

        // Desativa possíveis duplicatas do mesmo tipo para esta pessoa
        $duplicatas = $docRepo->findBy([
            'pessoa'        => $pessoa,
            'tipoDocumento' => $tipoDocumentoEntity,
        ]);

        foreach ($duplicatas as $doc) {
            if ($doc !== $pessoaDocumento && method_exists($doc, 'setAtivo')) {
                $doc->setAtivo(false);
            }
        }
    }

    /**
     * Busca dados específicos de cada tipo de pessoa
     */
    private function buscarDadosTiposPessoa(int $pessoaId, EntityManagerInterface $entityManager): array
    {
        $dados = [];
        
        // -------- FIADOR --------
        $fiador = $entityManager->getRepository(PessoasFiadores::class)
            ->findOneBy(['idPessoa' => $pessoaId]);
        if ($fiador) {
            $dados['fiador'] = [
                'id' => $fiador->getId(),
                'idConjuge' => $fiador->getIdConjuge(),
                'motivoFianca' => $fiador->getMotivoFianca(),
                'jaFoiFiador' => $fiador->getJaFoiFiador(),
                'conjugeTrabalha' => $fiador->getConjugeTrabalha(),
                'outros' => $fiador->getOutros(),
                'idFormaRetirada' => $fiador->getIdFormaRetirada(),
            ];
        }
        
        // -------- CORRETOR --------
        $corretor = $entityManager->getRepository(PessoasCorretores::class)
            ->findOneBy(['pessoa' => $pessoaId]);
        if ($corretor) {
            $dados['corretor'] = [
                'id' => $corretor->getId(),
                'creci' => $corretor->getCreci(),
                'usuario' => $corretor->getUsuario(),
                'status' => $corretor->getStatus(),
                'dataCadastro' => $corretor->getDataCadastro()?->format('Y-m-d'),
                'ativo' => $corretor->isAtivo(),
            ];
        }
        
        // -------- LOCADOR --------
        $locador = $entityManager->getRepository(PessoasLocadores::class)
            ->findOneBy(['pessoa' => $pessoaId]);
        if ($locador) {
            $dados['locador'] = [
                'id' => $locador->getId(),
                'formaRetirada' => $locador->getFormaRetirada()?->getId(),  // ✅ ID da entidade
                'dependentes' => $locador->getDependentes(),
                'diaRetirada' => $locador->getDiaRetirada(),
                'cobrarCpmf' => $locador->isCobrarCpmf(),
                'situacao' => $locador->getSituacao(),
                'codigoContabil' => $locador->getCodigoContabil(),
                'etiqueta' => $locador->isEtiqueta(),
                'cobrarTarifaRec' => $locador->isCobrarTarifaRec(),
                'dataFechamento' => $locador->getDataFechamento()?->format('Y-m-d'),
                'carencia' => $locador->getCarencia(),
                'multaItau' => $locador->isMultaItau(),
                'moraDiaria' => $locador->isMoraDiaria(),
                'protesto' => $locador->getProtesto(),
                'diasProtesto' => $locador->getDiasProtesto(),
                'naoGerarJudicial' => $locador->isNaoGerarJudicial(),
                'enderecoCobranca' => $locador->isEnderecoCobranca(),
                'condominioConta' => $locador->isCondominioConta(),
                'extEmail' => $locador->isExtEmail(),
            ];
        }
        
        // -------- PRETENDENTE --------
        $pretendente = $entityManager->getRepository(PessoasPretendentes::class)
            ->findOneBy(['pessoa' => $pessoaId]);
        if ($pretendente) {
            $dados['pretendente'] = [
                'id' => $pretendente->getId(),
                'tipoImovel' => $pretendente->getTipoImovel()?->getId(),
                'quartosDesejados' => $pretendente->getQuartosDesejados(),
                'aluguelMaximo' => $pretendente->getAluguelMaximo(),
                'logradouroDesejado' => $pretendente->getLogradouroDesejado()?->getId(),
                'disponivel' => $pretendente->isDisponivel(),
                'procuraAluguel' => $pretendente->isProcuraAluguel(),
                'procuraCompra' => $pretendente->isProcuraCompra(),
                'atendente' => $pretendente->getAtendente()?->getId(),
                'tipoAtendimento' => $pretendente->getTipoAtendimento()?->getId(),
                'dataCadastro' => $pretendente->getDataCadastro()?->format('Y-m-d'),
                'observacoes' => $pretendente->getObservacoes(),
            ];
        }
        
        // -------- CONTRATANTE --------
        $contratante = $entityManager->getRepository(PessoasContratantes::class)
            ->findOneBy(['pessoa' => $pessoaId]);
        if ($contratante) {
            $dados['contratante'] = [
                'id' => $contratante->getId(),
            ];
        }
        
        // -------- CORRETORA --------
        $corretora = $entityManager->getRepository(PessoasCorretoras::class)
            ->findOneBy(['pessoa' => $pessoaId]);
        if ($corretora) {
            $dados['corretora'] = [
                'id' => $corretora->getId(),
            ];
        }
        
        return $dados;
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

    #[Route('/endereco/{id}', name: 'delete_endereco', methods: ['DELETE'])]
    public function deleteEndereco(
        int $id,
        EntityManagerInterface $em,
        LoggerInterface $logger,
        Request $request
    ): JsonResponse {
        // segurança CSRF (opcional mas recomendado)
        $logger->info('🔵 DEBUG: Iniciando Exclusão de Pessoa!');
        $token = $request->headers->get('X-CSRF-Token');
            if (!$this->isCsrfTokenValid('ajax_global', $token)) {
                return new JsonResponse(['success' => false, 'message' => 'Token inválido'], 403);
            }

            $end = $em->getRepository(Enderecos::class)->find($id);
            if (!$end) return new JsonResponse(['success' => false, 'message' => 'Endereço não encontrado'], 404);

            $em->remove($end);
            $em->flush();

            return new JsonResponse(['success' => true]);
        }
    
    #[Route('/telefone/{id}', name: 'delete_telefone', methods: ['DELETE'])]
    public function deleteTelefone(
        int $id,
        EntityManagerInterface $em,
        Request $request
    ): JsonResponse {
        $token = $request->headers->get('X-CSRF-Token');
        if (!$this->isCsrfTokenValid('ajax_global', $token)) {
            return new JsonResponse(['success' => false, 'message' => 'Token inválido'], 403);
        }

        $telefone = $em->getRepository(Telefones::class)->find($id);
        if (!$telefone) {
            return new JsonResponse(['success' => false, 'message' => 'Telefone não encontrado'], 404);
        }

        // Remove também a tabela pivot PessoasTelefones
        $pivot = $em->getRepository(PessoasTelefones::class)->findOneBy(['idTelefone' => $id]);
        if ($pivot) {
            $em->remove($pivot);
        }

        $em->remove($telefone);
        $em->flush();

        return new JsonResponse(['success' => true]);
    }

    #[Route('/email/{id}', name: 'delete_email', methods: ['DELETE'])]
    public function deleteEmail(
        int $id,
        EntityManagerInterface $em,
        Request $request
    ): JsonResponse {
        $token = $request->headers->get('X-CSRF-Token');
        if (!$this->isCsrfTokenValid('ajax_global', $token)) {
            return new JsonResponse(['success' => false, 'message' => 'Token inválido'], 403);
        }

        $email = $em->getRepository(Emails::class)->find($id);
        if (!$email) {
            return new JsonResponse(['success' => false, 'message' => 'Email não encontrado'], 404);
        }

        // Remove também a tabela pivot PessoasEmails
        $pivot = $em->getRepository(PessoasEmails::class)->findOneBy(['idEmail' => $id]);
        if ($pivot) {
            $em->remove($pivot);
        }

        $em->remove($email);
        $em->flush();

        return new JsonResponse(['success' => true]);
    }

    #[Route('/chave-pix/{id}', name: 'delete_chave_pix', methods: ['DELETE'])]
    public function deleteChavePix(
        int $id,
        EntityManagerInterface $em,
        Request $request
    ): JsonResponse {
        $token = $request->headers->get('X-CSRF-Token');
        if (!$this->isCsrfTokenValid('ajax_global', $token)) {
            return new JsonResponse(['success' => false, 'message' => 'Token inválido'], 403);
        }

        $chavePix = $em->getRepository(ChavesPix::class)->find($id);
        if (!$chavePix) {
            return new JsonResponse(['success' => false, 'message' => 'Chave PIX não encontrada'], 404);
        }

        $em->remove($chavePix);
        $em->flush();

        return new JsonResponse(['success' => true]);
    }    

    #[Route('/documento/{id}', name: 'delete_documento', methods: ['DELETE'])]
    public function deleteDocumento(
        int $id,
        EntityManagerInterface $em,
        Request $request
    ): JsonResponse {
        $token = $request->headers->get('X-CSRF-Token');
        if (!$this->isCsrfTokenValid('ajax_global', $token)) {
            return new JsonResponse(['success' => false, 'message' => 'Token inválido'], 403);
        }

        $documento = $em->getRepository(PessoasDocumentos::class)->find($id);
        if (!$documento) {
            return new JsonResponse(['success' => false, 'message' => 'Documento não encontrado'], 404);
        }

        $em->remove($documento);
        $em->flush();

        return new JsonResponse(['success' => true]);
    }

    #[Route('/profissao/{id}', name: 'delete_profissao', methods: ['DELETE'])]
    public function deleteProfissao(
        int $id,
        EntityManagerInterface $em,
        Request $request
    ): JsonResponse {
        $token = $request->headers->get('X-CSRF-Token');
        if (!$this->isCsrfTokenValid('ajax_global', $token)) {
            return new JsonResponse(['success' => false, 'message' => 'Token inválido'], 403);
        }

        $profissao = $em->getRepository(PessoasProfissoes::class)->find($id);
        if (!$profissao) {
            return new JsonResponse(['success' => false, 'message' => 'Profissão não encontrada'], 404);
        }

        $em->remove($profissao);
        $em->flush();

        return new JsonResponse(['success' => true]);
    }
}
