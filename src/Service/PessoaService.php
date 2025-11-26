<?php

namespace App\Service;

use App\Entity\Pessoas;
use App\Entity\PessoasDocumentos;
use App\Entity\TiposDocumentos;
use App\Entity\Telefones;
use App\Entity\PessoasTelefones;
use App\Entity\Emails;
use App\Entity\PessoasEmails;
use App\Entity\Enderecos;
use App\Entity\ChavesPix;
use App\Entity\Logradouros;
use App\Entity\Bairros;
use App\Entity\Cidades;
use App\Entity\Estados;
use App\Entity\RelacionamentosFamiliares;
use App\Entity\PessoasFiadores;
use App\Entity\PessoasCorretores;
use App\Entity\PessoasLocadores;
use App\Entity\PessoasPretendentes;
use App\Entity\PessoasContratantes;
use App\Entity\PessoasCorretoras;
use App\Entity\PessoasProfissoes;
use App\Entity\PessoasTipos;
use App\Entity\ContasBancarias;
use App\Entity\Bancos;
use App\Entity\Agencias;
use App\Entity\TiposContasBancarias;
use App\Repository\PessoaRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class PessoaService
{
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;
    private PessoaRepository $pessoaRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        LoggerInterface $logger,
        PessoaRepository $pessoaRepository
    ) {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->pessoaRepository = $pessoaRepository;
    }

    public function criarPessoa(Pessoas $pessoa, array $formData, $tiposPessoa): void
    {
        $cpfCnpjNumero = $formData['searchTerm'] ?? null;
        $cpfNumero = null;
        
        if ($cpfCnpjNumero) {
            $cpfCnpjLimpo = preg_replace('/\D/', '', $cpfCnpjNumero);
            if (strlen($cpfCnpjLimpo) === 11) {
                $cpfNumero = $cpfCnpjLimpo;
            }
        }

        if ($cpfNumero) {
            $existente = $this->pessoaRepository->findByCpfDocumento($cpfNumero);
            if ($existente) {
                $this->logger->error('[CPF DUPLICADO] ' . $cpfNumero);
                throw new \RuntimeException('CPF já cadastrado.');
            }
        }

        $this->entityManager->getConnection()->beginTransaction();
        
        try {
            $this->entityManager->persist($pessoa);
            $this->entityManager->flush();

            $pessoaId = $pessoa->getIdpessoa();
            $this->logger->info('[PESSOA SALVA - ID] ' . $pessoaId);

            $this->salvarDadosMultiplos($pessoaId, $formData, '');
            
            $this->salvarMultiplosTipos($pessoa, $tiposPessoa, $formData);

            if (!empty($formData['temConjuge']) || !empty($formData['novo_conjuge']['nome'])) {
                $this->salvarConjuge($pessoa, $formData);
            }

            $this->entityManager->flush();
            $this->entityManager->getConnection()->commit();
            
        } catch (\Exception $e) {
            $this->entityManager->getConnection()->rollBack();
            $this->logger->error('🔴 ERRO CRÍTICO AO SALVAR NOVA PESSOA: ' . $e->getMessage());
            $this->logger->error('🔴 STACK TRACE: ' . $e->getTraceAsString());
            throw $e;
        }
    }

    public function atualizarPessoa(Pessoas $pessoa, array $formData, $tiposPessoa): void
    {
        $cpfCnpj = $formData['searchTerm'] ?? '';
        
        if ($cpfCnpj !== '') {
            $cpfCnpjDigits = preg_replace('/\D/', '', $cpfCnpj);

            $outra = null;
            if (strlen($cpfCnpjDigits) === 11 && method_exists($this->pessoaRepository, 'findByCpfDocumento')) {
                $outra = $this->pessoaRepository->findByCpfDocumento($cpfCnpjDigits);
            } elseif (strlen($cpfCnpjDigits) > 11 && method_exists($this->pessoaRepository, 'findByCnpjDocumento')) {
                $outra = $this->pessoaRepository->findByCnpjDocumento($cpfCnpjDigits);
            }

            if ($outra && $outra->getIdpessoa() !== $pessoa->getIdpessoa()) {
                throw new \RuntimeException('Documento informado já está cadastrado para outra pessoa.');
            }

            $pessoa->setFisicaJuridica(strlen($cpfCnpjDigits) === 11 ? 'fisica' : 'juridica');
        }

        $this->entityManager->getConnection()->beginTransaction();
        
        try {
            $this->entityManager->persist($pessoa);
            $this->entityManager->flush();

            $this->limparDadosMultiplosApenasSeEnviados($pessoa->getIdpessoa(), $formData, '');

            if (!empty($cpfCnpj)) {
                $this->salvarDocumentoPrincipal($pessoa, $cpfCnpj);
            }

            $this->salvarDadosMultiplos($pessoa->getIdpessoa(), $formData, '');
            
            $this->atualizarMultiplosTipos($pessoa, $tiposPessoa, $formData);
            
            $this->processarConjugeEdicao($pessoa, $formData);

            $this->entityManager->flush();
            $this->entityManager->getConnection()->commit();
            
        } catch (\Throwable $e) {
            $this->entityManager->getConnection()->rollBack();
            $this->logger->error('Erro detalhado na edição de pessoa: ' . $e->getMessage());
            $this->logger->error('Trace: ' . $e->getTraceAsString());
            throw $e;
        }
    }

    /**
     * ✅ REFATORADO: Método unificado para salvar dados múltiplos (Pessoa Principal OU Cônjuge)
     * @param int $pessoaId ID da pessoa (principal ou cônjuge)
     * @param array $requestData Dados do formulário
     * @param string $prefixo Prefixo dos campos ('conjuge_' para cônjuge, '' para pessoa principal)
     */
    private function salvarDadosMultiplos(int $pessoaId, array $requestData, string $prefixo = ''): void
    {
        // Telefones
        $chaveTelefones = $prefixo . 'telefones';
        if (isset($requestData[$chaveTelefones]) && is_array($requestData[$chaveTelefones])) {
            $telRepo = $this->entityManager->getRepository(Telefones::class);

            foreach ($requestData[$chaveTelefones] as $telefoneData) {
                if (!empty($telefoneData['tipo']) && !empty($telefoneData['numero'])) {
                    $numeroLimpo = preg_replace('/\D/', '', $telefoneData['numero']);

                    // ✅ CORREÇÃO: Sempre buscar antes de criar
                    $criteria = ['numero' => $numeroLimpo];
                    if (property_exists(Telefones::class, 'ativo')) {
                        $criteria['ativo'] = true;
                    }
                    $telefone = $telRepo->findOneBy($criteria);

                    if (!$telefone) {
                        $telefone = new Telefones();
                        $telefone->setTipo($this->entityManager->getReference(\App\Entity\TiposTelefones::class, (int)$telefoneData['tipo']));
                        $telefone->setNumero($numeroLimpo);
                        if (method_exists($telefone, 'setAtivo')) {
                            $telefone->setAtivo(true);
                        }
                        $this->entityManager->persist($telefone);
                        $this->entityManager->flush();
                    }

                    $pivot = new PessoasTelefones();
                    $pivot->setIdPessoa($pessoaId);
                    $pivot->setIdTelefone($telefone->getId());
                    $this->entityManager->persist($pivot);
                }
            }
        }

        // Emails
        $chaveEmails = $prefixo . 'emails';
        if (isset($requestData[$chaveEmails]) && is_array($requestData[$chaveEmails])) {
            $emailRepo = $this->entityManager->getRepository(Emails::class);

            foreach ($requestData[$chaveEmails] as $emailData) {
                if (!empty($emailData['tipo']) && !empty($emailData['email'])) {
                    $emailLimpo = strtolower(trim($emailData['email']));
                    
                    // ✅ CORREÇÃO: Sempre buscar antes de criar
                    $criteria = ['email' => $emailLimpo];
                    if (property_exists(Emails::class, 'ativo')) {
                        $criteria['ativo'] = true;
                    }
                    $email = $emailRepo->findOneBy($criteria);

                    if (!$email) {
                        $email = new Emails();
                        $email->setEmail($emailLimpo);
                        $email->setTipo($this->entityManager->getReference(\App\Entity\TiposEmails::class, (int)$emailData['tipo']));
                        if (method_exists($email, 'setAtivo')) {
                            $email->setAtivo(true);
                        }
                        $this->entityManager->persist($email);
                        $this->entityManager->flush();
                    }

                    $pivot = new PessoasEmails();
                    $pivot->setIdPessoa($pessoaId);
                    $pivot->setIdEmail($email->getId());
                    $this->entityManager->persist($pivot);
                }
            }
        }

        // Endereços
        $chaveEnderecos = $prefixo . 'enderecos';
        if (isset($requestData[$chaveEnderecos]) && is_array($requestData[$chaveEnderecos])) {
            foreach ($requestData[$chaveEnderecos] as $enderecoData) {
                if (!empty($enderecoData['tipo']) && !empty($enderecoData['numero'])) {
                    $logradouroId = $this->buscarOuCriarLogradouro($enderecoData);

                    $endereco = new Enderecos();
                    $endereco->setPessoa($this->entityManager->getReference(Pessoas::class, $pessoaId));
                    $endereco->setLogradouro($this->entityManager->getReference(Logradouros::class, $logradouroId));
                    $endereco->setTipo($this->entityManager->getReference(\App\Entity\TiposEnderecos::class, (int)$enderecoData['tipo']));
                    $endereco->setEndNumero((int)$enderecoData['numero']);
                    if (!empty($enderecoData['complemento'])) {
                        $endereco->setComplemento($enderecoData['complemento']);
                    }

                    $this->entityManager->persist($endereco);
                }
            }
        }

        // Chaves PIX
        $chavePixArray = $prefixo . 'chaves_pix';
        if (isset($requestData[$chavePixArray]) && is_array($requestData[$chavePixArray])) {
            foreach ($requestData[$chavePixArray] as $pixData) {
                if (!empty($pixData['tipo']) && !empty($pixData['chave'])) {
                    $chavePix = new ChavesPix();
                    $chavePix->setIdPessoa($pessoaId);
                    $chavePix->setIdTipoChave((int)$pixData['tipo']);
                    $chavePix->setChavePix($pixData['chave']);
                    $chavePix->setPrincipal(!empty($pixData['principal']));
                    if (method_exists($chavePix, 'setAtivo')) {
                        $chavePix->setAtivo(true);
                    }
                    $this->entityManager->persist($chavePix);
                }
            }
        }

        // Documentos
        $chaveDocumentos = $prefixo . 'documentos';
        if (isset($requestData[$chaveDocumentos]) && is_array($requestData[$chaveDocumentos])) {
            foreach ($requestData[$chaveDocumentos] as $documentoData) {
                if (!empty($documentoData['tipo']) && !empty($documentoData['numero'])) {
                    $documento = new PessoasDocumentos();
                    $pessoaRef = $this->entityManager->getReference(Pessoas::class, $pessoaId);
                    $documento->setPessoa($pessoaRef);

                    $tipoDocumentoEntity = $this->entityManager->getReference(\App\Entity\TiposDocumentos::class, (int)$documentoData['tipo']);
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

                    $this->entityManager->persist($documento);
                }
            }
        }

        // Profissões
        $chaveProfissoes = $prefixo . 'profissoes';
        if (isset($requestData[$chaveProfissoes]) && is_array($requestData[$chaveProfissoes])) {
            $this->logger->info("DEBUG - Salvando profissões com chave: $chaveProfissoes para pessoa: $pessoaId");
            $this->logger->info("DEBUG - Dados recebidos: " . json_encode($requestData[$chaveProfissoes]));

            // Primeiro, marcar todas as profissões existentes como inativas
            $profissoesExistentes = $this->entityManager->getRepository(PessoasProfissoes::class)
                ->findBy(['idPessoa' => $pessoaId, 'ativo' => true]);

            foreach ($profissoesExistentes as $profExistente) {
                $profExistente->setAtivo(false);
                $this->entityManager->persist($profExistente);
            }

            // Agora processar as novas profissões
            foreach ($requestData[$chaveProfissoes] as $index => $profissaoData) {
                $this->logger->info("DEBUG - Processando profissão $index: " . json_encode($profissaoData));

                if (!empty($profissaoData['profissao'])) {
                    // Verificar se já existe uma profissão inativa com os mesmos dados
                    $pessoaProfissao = $this->entityManager->getRepository(PessoasProfissoes::class)
                        ->findOneBy([
                            'idPessoa' => $pessoaId,
                            'idProfissao' => (int)$profissaoData['profissao'],
                            'empresa' => $profissaoData['empresa'] ?? null
                        ]);

                    if (!$pessoaProfissao) {
                        $pessoaProfissao = new PessoasProfissoes();
                        $pessoaProfissao->setIdPessoa($pessoaId);
                        $pessoaProfissao->setIdProfissao((int)$profissaoData['profissao']);
                    }

                    if (!empty($profissaoData['empresa'])) {
                        $pessoaProfissao->setEmpresa($profissaoData['empresa']);
                    }

                    // Log detalhado para data_admissao
                    $this->logger->info("DEBUG - Campo data_admissao: " . (isset($profissaoData['data_admissao']) ? $profissaoData['data_admissao'] : 'NÃO DEFINIDO'));
                    if (!empty($profissaoData['data_admissao'])) {
                        $this->logger->info("DEBUG - Definindo data_admissao: " . $profissaoData['data_admissao']);
                        $pessoaProfissao->setDataAdmissao(new \DateTime($profissaoData['data_admissao']));
                    } else {
                        $this->logger->info("DEBUG - data_admissao está vazia ou não definida");
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

                    $this->entityManager->persist($pessoaProfissao);
                }
            }
        }

        // Contas Bancárias
        $chaveContasBancarias = $prefixo . 'contas_bancarias';
        if (isset($requestData[$chaveContasBancarias]) && is_array($requestData[$chaveContasBancarias])) {
            $this->logger->info("DEBUG - Salvando contas bancárias com chave: $chaveContasBancarias para pessoa: $pessoaId");

            // Primeiro, marcar todas as contas existentes como inativas
            $contasExistentes = $this->entityManager->getRepository(ContasBancarias::class)
                ->findBy(['idPessoa' => $pessoaId, 'ativo' => true]);

            foreach ($contasExistentes as $contaExistente) {
                $contaExistente->setAtivo(false);
                $this->entityManager->persist($contaExistente);
            }

            // Processar as novas contas bancárias
            foreach ($requestData[$chaveContasBancarias] as $contaData) {
                if (!empty($contaData['banco']) && !empty($contaData['codigo'])) {
                    // Verificar se já existe uma conta com os mesmos dados
                    $contaBancaria = $this->entityManager->getRepository(ContasBancarias::class)
                        ->findOneBy([
                            'idPessoa' => $pessoaId,
                            'idBanco' => (int)$contaData['banco'],
                            'codigo' => $contaData['codigo']
                        ]);

                    if (!$contaBancaria) {
                        $contaBancaria = new ContasBancarias();
                        $contaBancaria->setIdPessoa($this->entityManager->getReference(Pessoas::class, $pessoaId));
                    }

                    // Definir banco
                    if (!empty($contaData['banco'])) {
                        $contaBancaria->setIdBanco($this->entityManager->getReference(Bancos::class, (int)$contaData['banco']));
                    }

                    // Definir agência
                    if (!empty($contaData['agencia'])) {
                        $contaBancaria->setIdAgencia($this->entityManager->getReference(Agencias::class, (int)$contaData['agencia']));
                    }

                    // Campos obrigatórios
                    $contaBancaria->setCodigo($contaData['codigo']);

                    // Campos opcionais
                    if (isset($contaData['digito_conta'])) {
                        $contaBancaria->setDigitoConta($contaData['digito_conta']);
                    }

                    if (!empty($contaData['tipo_conta'])) {
                        $contaBancaria->setIdTipoConta($this->entityManager->getReference(TiposContasBancarias::class, (int)$contaData['tipo_conta']));
                    }

                    if (isset($contaData['titular'])) {
                        $contaBancaria->setTitular($contaData['titular']);
                    }

                    if (isset($contaData['descricao'])) {
                        $contaBancaria->setDescricao($contaData['descricao']);
                    }

                    // Definir como principal se especificado
                    $contaBancaria->setPrincipal(!empty($contaData['principal']));

                    // Definir campos booleanos com valores padrão
                    $contaBancaria->setAtivo(true);
                    $contaBancaria->setRegistrada(false);
                    $contaBancaria->setAceitaMultipag(false);
                    $contaBancaria->setUsaEnderecoCobranca(false);
                    $contaBancaria->setCobrancaCompartilhada(false);

                    $this->entityManager->persist($contaBancaria);
                }
            }
        }
    }

    /**
     * ✅ REFATORADO: Método unificado para limpar dados múltiplos (Pessoa Principal OU Cônjuge)
     */
    private function limparDadosMultiplosApenasSeEnviados(int $pessoaId, array $formData, string $prefixo = ''): void
    {
        $pessoaRef = $this->entityManager->getReference(Pessoas::class, $pessoaId);

        $tem = static function (string $chave) use ($formData, $prefixo): bool {
            $chaveCompleta = $prefixo . $chave;
            return array_key_exists($chaveCompleta, $formData) && is_array($formData[$chaveCompleta]);
        };

        if (!($tem('telefones') || $tem('emails') || $tem('enderecos') || $tem('chaves_pix') || $tem('documentos') || $tem('profissoes'))) {
            return;
        }

        if ($tem('telefones')) {
            $pessoasTelefonesRepo = $this->entityManager->getRepository(PessoasTelefones::class);
            $telefonesRepo        = $this->entityManager->getRepository(Telefones::class);

            $pivotsTel = $pessoasTelefonesRepo->findBy(['idPessoa' => $pessoaId]);
            foreach ($pivotsTel as $pivot) {
                $telefoneId = $pivot->getIdTelefone();
                $this->entityManager->remove($pivot);

                $existeOutroVinculo = $pessoasTelefonesRepo->findOneBy(['idTelefone' => $telefoneId]);
                if (!$existeOutroVinculo) {
                    if ($tel = $telefonesRepo->find($telefoneId)) {
                        $this->entityManager->remove($tel);
                    }
                }
            }
        }

        if ($tem('emails')) {
            $pessoasEmailsRepo = $this->entityManager->getRepository(PessoasEmails::class);
            $emailsRepo        = $this->entityManager->getRepository(Emails::class);

            $pivotsEmail = $pessoasEmailsRepo->findBy(['idPessoa' => $pessoaId]);
            foreach ($pivotsEmail as $pivot) {
                $emailId = $pivot->getIdEmail();
                $this->entityManager->remove($pivot);

                $existeOutroVinculo = $pessoasEmailsRepo->findOneBy(['idEmail' => $emailId]);
                if (!$existeOutroVinculo) {
                    if ($email = $emailsRepo->find($emailId)) {
                        $this->entityManager->remove($email);
                    }
                }
            }
        }

        if ($tem('enderecos')) {
            $enderecosRepo = $this->entityManager->getRepository(Enderecos::class);
            $enderecos = $enderecosRepo->findBy(['pessoa' => $pessoaRef]);
            foreach ($enderecos as $end) {
                $this->entityManager->remove($end);
            }
        }

        if ($tem('chaves_pix')) {
            $pixRepo = $this->entityManager->getRepository(ChavesPix::class);
            $chaves = $pixRepo->findBy(['idPessoa' => $pessoaId]);
            foreach ($chaves as $ch) {
                $this->entityManager->remove($ch);
            }
        }

        if ($tem('documentos')) {
            $docRepo = $this->entityManager->getRepository(PessoasDocumentos::class);
            $docs = $docRepo->findBy(['pessoa' => $pessoaRef]);
            foreach ($docs as $doc) {
                $tipoDoc = $doc->getTipoDocumento();
                if ($tipoDoc && !in_array(strtoupper($tipoDoc->getTipo()), ['CPF', 'CNPJ'], true)) {
                    $this->entityManager->remove($doc);
                }
            }
        }

        if ($tem('profissoes')) {
            $profRepo = $this->entityManager->getRepository(PessoasProfissoes::class);
            $profs = $profRepo->findBy(['idPessoa' => $pessoaId]);
            foreach ($profs as $prof) {
                $this->entityManager->remove($prof);
            }
        }
    }

    /**
     * ✅ REFATORADO: Método unificado para salvar documento principal (CPF/CNPJ)
     * Agora serve tanto para Pessoa Principal quanto para Cônjuge
     */
    private function salvarDocumentoPrincipal(Pessoas $pessoa, string $documento): void
    {
        $numero = preg_replace('/\D/', '', (string) $documento);
        if ($numero === '' || $numero === null) {
            return;
        }

        $tipo = strlen($numero) === 11 ? 'CPF' : 'CNPJ';

        $tipoDocumentoEntity = $this->entityManager->getRepository(\App\Entity\TiposDocumentos::class)
            ->findOneBy(['tipo' => $tipo]);

        if (!$tipoDocumentoEntity) {
            return;
        }

        $docRepo = $this->entityManager->getRepository(PessoasDocumentos::class);

        $pessoaDocumento = $docRepo->findOneBy([
            'pessoa'        => $pessoa,
            'tipoDocumento' => $tipoDocumentoEntity,
            'ativo'         => true,
        ]);

        if (!$pessoaDocumento) {
            $pessoaDocumento = $docRepo->findOneBy([
                'pessoa'        => $pessoa,
                'tipoDocumento' => $tipoDocumentoEntity,
            ]);
        }

        if ($pessoaDocumento) {
            $pessoaDocumento->setNumeroDocumento($numero);
            $pessoaDocumento->setTipoDocumento($tipoDocumentoEntity);
            if (method_exists($pessoaDocumento, 'setAtivo')) {
                $pessoaDocumento->setAtivo(true);
            }
        } else {
            $pessoaDocumento = new PessoasDocumentos();
            $pessoaDocumento->setPessoa($pessoa);
            $pessoaDocumento->setTipoDocumento($tipoDocumentoEntity);
            $pessoaDocumento->setNumeroDocumento($numero);
            if (method_exists($pessoaDocumento, 'setAtivo')) {
                $pessoaDocumento->setAtivo(true);
            }
            $this->entityManager->persist($pessoaDocumento);
        }

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
     * ✅ Salvar múltiplos tipos de pessoa
     */
    private function salvarMultiplosTipos(Pessoas $pessoa, $tiposPessoa, array $formData): void
    {
        if (is_string($tiposPessoa)) {
            $tiposPessoa = [$tiposPessoa];
        }

        if (!is_array($tiposPessoa) || empty($tiposPessoa)) {
            return;
        }

        $pessoaId = $pessoa->getIdpessoa();
        $dataInicio = new \DateTime();

        foreach ($tiposPessoa as $tipo) {
            $tipoId = $this->convertTipoPessoaToId($tipo);
            
            $pessoaTipo = new PessoasTipos();
            $pessoaTipo->setIdPessoa($pessoaId);
            $pessoaTipo->setIdTipoPessoa($tipoId);
            $pessoaTipo->setDataInicio($dataInicio);
            $pessoaTipo->setAtivo(true);
            $this->entityManager->persist($pessoaTipo);

            $this->salvarTipoEspecifico($pessoa, $tipo, $formData);
        }
    }

    /**
     * ✅ Atualizar múltiplos tipos de pessoa
     */
    private function atualizarMultiplosTipos(Pessoas $pessoa, $tiposPessoa, array $formData): void
    {
        if (is_string($tiposPessoa)) {
            $tiposPessoa = [$tiposPessoa];
        }

        if (!is_array($tiposPessoa) || empty($tiposPessoa)) {
            return;
        }

        $pessoaId = $pessoa->getIdpessoa();
        
        $tiposAtivos = $this->entityManager->getRepository(PessoasTipos::class)
            ->findBy(['idPessoa' => $pessoaId, 'ativo' => true]);

        $tiposAtivosIds = array_map(fn($pt) => $pt->getIdTipoPessoa(), $tiposAtivos);
        
        $novosIds = array_map(fn($tipo) => $this->convertTipoPessoaToId($tipo), $tiposPessoa);

        foreach ($tiposAtivos as $tipoAtivo) {
            if (!in_array($tipoAtivo->getIdTipoPessoa(), $novosIds)) {
                $tipoAtivo->setAtivo(false);
                $tipoAtivo->setDataFim(new \DateTime());
            }
        }

        foreach ($tiposPessoa as $tipo) {
            $tipoId = $this->convertTipoPessoaToId($tipo);
            
            if (!in_array($tipoId, $tiposAtivosIds)) {
                $pessoaTipo = new PessoasTipos();
                $pessoaTipo->setIdPessoa($pessoaId);
                $pessoaTipo->setIdTipoPessoa($tipoId);
                $pessoaTipo->setDataInicio(new \DateTime());
                $pessoaTipo->setAtivo(true);
                $this->entityManager->persist($pessoaTipo);

                $this->salvarTipoEspecifico($pessoa, $tipo, $formData);
            } else {
                $this->atualizarTipoEspecifico($pessoa, $tipo, $formData);
            }
        }
    }

    private function convertTipoPessoaToId(string $tipoPessoaString): int
    {
        $tipoRepository = $this->entityManager->getRepository(\App\Entity\TiposPessoas::class);
        $tipo = $tipoRepository->findOneBy(['tipo' => $tipoPessoaString, 'ativo' => true]);

        if ($tipo) {
            return $tipo->getId();
        }

        $fiador = $tipoRepository->findOneBy(['tipo' => 'fiador', 'ativo' => true]);
        return $fiador ? $fiador->getId() : 1;
    }

    private function salvarTipoEspecifico(Pessoas $pessoa, string $tipoPessoa, array $data): void
    {
        if (!$tipoPessoa) {
            return;
        }

        switch ($tipoPessoa) {
            case 'fiador':
                $fiador = new PessoasFiadores();
                $fiador->setIdPessoa($pessoa->getIdpessoa());
                
                if (isset($data['fiador']) && is_array($data['fiador'])) {
                    $this->preencherDadosFiador($fiador, $data['fiador']);
                }
                
                $this->entityManager->persist($fiador);
                break;

            case 'corretor':
                $corretor = new PessoasCorretores();
                $corretor->setPessoa($pessoa);
                
                if (isset($data['corretor']) && is_array($data['corretor'])) {
                    $this->preencherDadosCorretor($corretor, $data['corretor']);
                }
                
                $this->entityManager->persist($corretor);
                break;

            case 'locador':
                $locador = new PessoasLocadores();
                $locador->setPessoa($pessoa);
                
                if (isset($data['locador']) && is_array($data['locador'])) {
                    $this->preencherDadosLocador($locador, $data['locador']);
                }
                
                $this->entityManager->persist($locador);
                break;

            case 'pretendente':
                $pretendente = new PessoasPretendentes();
                $pretendente->setPessoa($pessoa);
                
                if (isset($data['pretendente']) && is_array($data['pretendente'])) {
                    $this->preencherDadosPretendente($pretendente, $data['pretendente']);
                }
                
                $this->entityManager->persist($pretendente);
                break;

            case 'contratante':
                $contratante = new PessoasContratantes();
                $contratante->setPessoa($pessoa);
                $this->entityManager->persist($contratante);
                break;

            case 'corretora':
                $corretora = new PessoasCorretoras();
                $corretora->setPessoa($pessoa);
                $this->entityManager->persist($corretora);
                break;
        }
    }

    private function atualizarTipoEspecifico(Pessoas $pessoa, string $tipoPessoa, array $data): void
    {
        $pessoaId = $pessoa->getIdpessoa();

        switch ($tipoPessoa) {
            case 'fiador':
                $fiador = $this->entityManager->getRepository(PessoasFiadores::class)
                    ->findOneBy(['idPessoa' => $pessoaId]);
                    
                if ($fiador && isset($data['fiador']) && is_array($data['fiador'])) {
                    $this->preencherDadosFiador($fiador, $data['fiador']);
                }
                break;

            case 'corretor':
                $corretor = $this->entityManager->getRepository(PessoasCorretores::class)
                    ->findOneBy(['pessoa' => $pessoaId]);
                    
                if ($corretor && isset($data['corretor']) && is_array($data['corretor'])) {
                    $this->preencherDadosCorretor($corretor, $data['corretor']);
                }
                break;

            case 'locador':
                $locador = $this->entityManager->getRepository(PessoasLocadores::class)
                    ->findOneBy(['pessoa' => $pessoaId]);
                    
                if ($locador && isset($data['locador']) && is_array($data['locador'])) {
                    $this->preencherDadosLocador($locador, $data['locador']);
                }
                break;

            case 'pretendente':
                $pretendente = $this->entityManager->getRepository(PessoasPretendentes::class)
                    ->findOneBy(['pessoa' => $pessoaId]);
                    
                if ($pretendente && isset($data['pretendente']) && is_array($data['pretendente'])) {
                    $this->preencherDadosPretendente($pretendente, $data['pretendente']);
                }
                break;
        }
    }

    private function preencherDadosFiador(PessoasFiadores $fiador, array $data): void
    {
        if (isset($data['motivoFianca'])) {
            $fiador->setMotivoFianca($data['motivoFianca']);
        }
        if (isset($data['jaFoiFiador'])) {
            $fiador->setJaFoiFiador((bool)$data['jaFoiFiador']);
        }
        if (isset($data['conjugeTrabalha'])) {
            $fiador->setConjugeTrabalha((bool)$data['conjugeTrabalha']);
        }
        if (isset($data['outros'])) {
            $fiador->setOutros($data['outros']);
        }
        if (isset($data['idFormaRetirada'])) {
            $fiador->setIdFormaRetirada((int)$data['idFormaRetirada']);
        }
    }

    private function preencherDadosCorretor(PessoasCorretores $corretor, array $data): void
    {
        if (isset($data['creci'])) {
            $corretor->setCreci($data['creci']);
        }
        if (isset($data['usuario'])) {
            $corretor->setUsuario($data['usuario']);
        }
        if (isset($data['status'])) {
            $corretor->setStatus($data['status']);
        }
        if (isset($data['dataCadastro'])) {
            $corretor->setDataCadastro(new \DateTime($data['dataCadastro']));
        }
        if (isset($data['ativo'])) {
            $corretor->setAtivo((bool)$data['ativo']);
        }
    }

    private function preencherDadosLocador(PessoasLocadores $locador, array $data): void
    {
        if (isset($data['formaRetirada'])) {
            $formaRetirada = $this->entityManager->getReference(\App\Entity\FormasRetirada::class, (int)$data['formaRetirada']);
            $locador->setFormaRetirada($formaRetirada);
        }
        if (isset($data['dependentes'])) {
            $locador->setDependentes((int)$data['dependentes']);
        }
        if (isset($data['diaRetirada'])) {
            $locador->setDiaRetirada((int)$data['diaRetirada']);
        }
        if (isset($data['dataFechamento'])) {
            $locador->setDataFechamento(new \DateTime($data['dataFechamento']));
        }
        if (isset($data['carencia'])) {
            $locador->setCarencia((int)$data['carencia']);
        }
        if (isset($data['situacao'])) {
            $locador->setSituacao((int)$data['situacao']);
        }
        if (isset($data['codigoContabil'])) {
            $locador->setCodigoContabil((int)$data['codigoContabil']);
        }
        if (isset($data['protesto'])) {
            $locador->setProtesto((int)$data['protesto']);
        }
        if (isset($data['diasProtesto'])) {
            $locador->setDiasProtesto((int)$data['diasProtesto']);
        }
        
        $locador->setCobrarCpmf($data['cobrarCpmf'] ?? false);
        $locador->setEtiqueta($data['etiqueta'] ?? true);
        $locador->setCobrarTarifaRec($data['cobrarTarifaRec'] ?? false);
        $locador->setMultaItau($data['multaItau'] ?? false);
        $locador->setMoraDiaria($data['moraDiaria'] ?? false);
        $locador->setNaoGerarJudicial($data['naoGerarJudicial'] ?? false);
        $locador->setEnderecoCobranca($data['enderecoCobranca'] ?? false);
        $locador->setCondominioConta($data['condominioConta'] ?? false);
        $locador->setExtEmail($data['extEmail'] ?? false);
    }

    private function preencherDadosPretendente(PessoasPretendentes $pretendente, array $data): void
    {
        if (isset($data['tipoImovel'])) {
            $tipoImovel = $this->entityManager->getReference(\App\Entity\TiposImoveis::class, (int)$data['tipoImovel']);
            $pretendente->setTipoImovel($tipoImovel);
        }
        if (isset($data['quartosDesejados'])) {
            $pretendente->setQuartosDesejados((int)$data['quartosDesejados']);
        }
        if (isset($data['aluguelMaximo'])) {
            $pretendente->setAluguelMaximo($data['aluguelMaximo']);
        }
        if (isset($data['logradouroDesejado'])) {
            $logradouro = $this->entityManager->getReference(\App\Entity\Logradouros::class, (int)$data['logradouroDesejado']);
            $pretendente->setLogradouroDesejado($logradouro);
        }
        if (isset($data['atendente'])) {
            $atendente = $this->entityManager->getReference(\App\Entity\Users::class, (int)$data['atendente']);
            $pretendente->setAtendente($atendente);
        }
        if (isset($data['tipoAtendimento'])) {
            $tipoAtendimento = $this->entityManager->getReference(\App\Entity\TiposAtendimento::class, (int)$data['tipoAtendimento']);
            $pretendente->setTipoAtendimento($tipoAtendimento);
        }
        if (isset($data['dataCadastro'])) {
            $pretendente->setDataCadastro(new \DateTime($data['dataCadastro']));
        }
        if (isset($data['observacoes'])) {
            $pretendente->setObservacoes($data['observacoes']);
        }
        
        $pretendente->setDisponivel($data['disponivel'] ?? false);
        $pretendente->setProcuraAluguel($data['procuraAluguel'] ?? false);
        $pretendente->setProcuraCompra($data['procuraCompra'] ?? false);
    }

    /**
     * ✅ CORRIGIDO: Salvar/atualizar cônjuge com relacionamento bidirecional
     * Agora suporta criação, linkagem E alteração de cônjuge existente
     */
    private function salvarConjuge(Pessoas $pessoa, array $requestData): void
    {
        $this->logger->info('DEBUG salvarConjuge - requestData keys: ' . implode(', ', array_keys($requestData)));

        $conjugeParaRelacionar = null;

        // Verificar se foi selecionado cônjuge existente
        $conjugeId = $requestData['conjuge'] ?? $requestData['conjuge_id'] ?? null;
        if ($conjugeId) {
            $this->logger->info('DEBUG: Tentando encontrar cônjuge existente com ID: ' . $conjugeId);
            $conjugeParaRelacionar = $this->pessoaRepository->find($conjugeId);
        }

        // Ou criar novo cônjuge
        $novoConjugeData = $requestData['novo_conjuge'] ?? null;
        if (!$conjugeParaRelacionar && $novoConjugeData && !empty($novoConjugeData['nome']) && !empty($novoConjugeData['cpf'])) {
            $this->logger->info('DEBUG: Criando novo cônjuge');
            
            $novoConjuge = new Pessoas();
            $novoConjuge->setNome($novoConjugeData['nome']);

            if (!empty($novoConjugeData['data_nascimento'])) {
                $novoConjuge->setDataNascimento(new \DateTime($novoConjugeData['data_nascimento']));
            }
            if (!empty($novoConjugeData['estado_civil'])) {
                $estadoCivil = $this->entityManager->getReference(\App\Entity\EstadoCivil::class, $novoConjugeData['estado_civil']);
                $novoConjuge->setEstadoCivil($estadoCivil);
            }
            if (!empty($novoConjugeData['nacionalidade'])) {
                $nacionalidade = $this->entityManager->getReference(\App\Entity\Nacionalidade::class, $novoConjugeData['nacionalidade']);
                $novoConjuge->setNacionalidade($nacionalidade);
            }
            if (!empty($novoConjugeData['naturalidade'])) {
                $naturalidade = $this->entityManager->getReference(\App\Entity\Naturalidade::class, $novoConjugeData['naturalidade']);
                $novoConjuge->setNaturalidade($naturalidade);
            }

            $novoConjuge->setNomePai($novoConjugeData['nome_pai'] ?? null);
            $novoConjuge->setNomeMae($novoConjugeData['nome_mae'] ?? null);
            $novoConjuge->setRenda((float)($novoConjugeData['renda'] ?? 0.0));
            $novoConjuge->setObservacoes($novoConjugeData['observacoes'] ?? null);
            $novoConjuge->setFisicaJuridica('fisica');
            $novoConjuge->setDtCadastro(new \DateTime());
            $novoConjuge->setStatus(true);
            $novoConjuge->setTipoPessoa(1);

            $this->entityManager->persist($novoConjuge);
            $this->entityManager->flush();

            // ✅ CORREÇÃO: Usar método unificado
            $this->salvarDocumentoPrincipal($novoConjuge, $novoConjugeData['cpf']);

            $conjugeParaRelacionar = $novoConjuge;
        }

        // ✅ NOVO: Atualizar dados do cônjuge (seja ele novo ou existente)
        if ($conjugeParaRelacionar) {
            $conjugeId = $conjugeParaRelacionar->getIdpessoa();
            
            // Limpar dados antigos do cônjuge se houver dados novos no formulário
            $this->limparDadosMultiplosApenasSeEnviados($conjugeId, $requestData, 'conjuge_');
            
            // Salvar novos dados múltiplos do cônjuge
            $this->salvarDadosMultiplos($conjugeId, $requestData, 'conjuge_');
            
            // Estabelecer relacionamento bidirecional
            $this->logger->info('DEBUG: Estabelecendo relacionamento familiar bidirecional');
            $this->estabelecerRelacionamentoConjugal($pessoa->getIdpessoa(), $conjugeId);
        }
    }

    /**
     * ✅ Estabelecer relacionamento conjugal bidirecional
     */
    private function estabelecerRelacionamentoConjugal(int $pessoaId1, int $pessoaId2): void
    {
        $relacionamentoRepo = $this->entityManager->getRepository(RelacionamentosFamiliares::class);

        $existente1 = $relacionamentoRepo->findOneBy([
            'idPessoaOrigem' => $pessoaId1,
            'idPessoaDestino' => $pessoaId2,
            'tipoRelacionamento' => 'Cônjuge',
            'ativo' => true
        ]);

        $existente2 = $relacionamentoRepo->findOneBy([
            'idPessoaOrigem' => $pessoaId2,
            'idPessoaDestino' => $pessoaId1,
            'tipoRelacionamento' => 'Cônjuge',
            'ativo' => true
        ]);

        if (!$existente1) {
            $relacionamento1 = new RelacionamentosFamiliares();
            $relacionamento1->setIdPessoaOrigem($pessoaId1);
            $relacionamento1->setIdPessoaDestino($pessoaId2);
            $relacionamento1->setTipoRelacionamento('Cônjuge');
            $relacionamento1->setAtivo(true);
            $relacionamento1->setDataInicio(new \DateTime());
            $this->entityManager->persist($relacionamento1);
        }

        if (!$existente2) {
            $relacionamento2 = new RelacionamentosFamiliares();
            $relacionamento2->setIdPessoaOrigem($pessoaId2);
            $relacionamento2->setIdPessoaDestino($pessoaId1);
            $relacionamento2->setTipoRelacionamento('Cônjuge');
            $relacionamento2->setAtivo(true);
            $relacionamento2->setDataInicio(new \DateTime());
            $this->entityManager->persist($relacionamento2);
        }
    }

    /**
     * ✅ Processar cônjuge ao editar pessoa
     */
    private function processarConjugeEdicao(Pessoas $pessoa, array $requestData): void
    {
        // 🔍 DEBUG: Log dos dados recebidos
        $this->logger->info('🔍 DEBUG processarConjugeEdicao - Dados recebidos:', [
            'pessoa_id' => $pessoa->getIdpessoa(),
            'temConjuge_checkbox' => $requestData['temConjuge'] ?? 'não enviado',
            'conjuge_id' => $requestData['conjuge_id'] ?? 'não enviado',
            'novo_conjuge_presente' => isset($requestData['novo_conjuge']),
            'novo_conjuge_nome' => $requestData['novo_conjuge']['nome'] ?? 'não enviado',
            'novo_conjuge_cpf' => $requestData['novo_conjuge']['cpf'] ?? 'não enviado'
        ]);

        $relacionamentoRepo = $this->entityManager->getRepository(RelacionamentosFamiliares::class);

        $relacionamentoExistente = $relacionamentoRepo->findOneBy([
            'idPessoaOrigem' => $pessoa->getIdpessoa(),
            'tipoRelacionamento' => 'Cônjuge',
            'ativo' => true
        ]);

        // ✅ CORREÇÃO: Verificar também o checkbox temConjuge
        $temConjuge = !empty($requestData['temConjuge'])
                   || !empty($requestData['novo_conjuge']['nome'])
                   || !empty($requestData['conjuge'])
                   || !empty($requestData['conjuge_id']);

        $this->logger->info('🔍 DEBUG processarConjugeEdicao - Decisão:', [
            'temConjuge' => $temConjuge,
            'relacionamentoExistente' => $relacionamentoExistente ? 'SIM' : 'NÃO'
        ]);

        if ($relacionamentoExistente && !$temConjuge) {
            $this->logger->info('🗑️ Removendo relacionamento existente (cônjuge foi desmarcado)');

            $relacionamentoInverso = $relacionamentoRepo->findOneBy([
                'idPessoaOrigem' => $relacionamentoExistente->getIdPessoaDestino(),
                'idPessoaDestino' => $pessoa->getIdpessoa(),
                'tipoRelacionamento' => 'Cônjuge',
                'ativo' => true
            ]);

            $relacionamentoExistente->setAtivo(false);
            $relacionamentoExistente->setDataFim(new \DateTime());

            if ($relacionamentoInverso) {
                $relacionamentoInverso->setAtivo(false);
                $relacionamentoInverso->setDataFim(new \DateTime());
            }
        }

        if ($temConjuge) {
            $this->logger->info('✅ Chamando salvarConjuge()');
            $this->salvarConjuge($pessoa, $requestData);
        } else {
            $this->logger->info('⏭️ Pulando salvarConjuge() - temConjuge = false');
        }
    }

    private function buscarOuCriarLogradouro(array $enderecoData): int
    {
        $bairroRepository = $this->entityManager->getRepository(Bairros::class);
        $bairro = $bairroRepository->findOneBy(['nome' => $enderecoData['bairro']]);

        if (!$bairro) {
            $cidadeRepository = $this->entityManager->getRepository(Cidades::class);
            $cidade = $cidadeRepository->findOneBy(['nome' => $enderecoData['cidade']]);

            if (!$cidade) {
                $estadoRepository = $this->entityManager->getRepository(Estados::class);
                $estado = $estadoRepository->findOneBy(['uf' => $enderecoData['estado'] ?? 'SP']);

                if (!$estado) {
                    $estado = new Estados();
                    $estado->setUf($enderecoData['estado'] ?? 'SP');
                    $estado->setNome($enderecoData['estado'] ?? 'São Paulo');
                    $this->entityManager->persist($estado);
                    $this->entityManager->flush();
                }

                $cidade = new Cidades();
                $cidade->setNome($enderecoData['cidade']);
                $cidade->setEstado($estado);
                $this->entityManager->persist($cidade);
                $this->entityManager->flush();
            }

            $bairro = new Bairros();
            $bairro->setNome($enderecoData['bairro']);
            $bairro->setCidade($cidade);
            $this->entityManager->persist($bairro);
            $this->entityManager->flush();
        }

        $logradouroRepository = $this->entityManager->getRepository(Logradouros::class);
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

            $this->entityManager->persist($logradouro);
            $this->entityManager->flush();
        }

        return $logradouro->getId();
    }

    public function buscarDadosTiposPessoa(int $pessoaId): array
    {
        $dados = [];
        
        $fiador = $this->entityManager->getRepository(PessoasFiadores::class)
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
        
        $corretor = $this->entityManager->getRepository(PessoasCorretores::class)
            ->findOneBy(['pessoa' => $pessoaId]);
        if ($corretor) {
            $dados['corretor'] = [
                'id' => $corretor->getId(),
                'creci' => $corretor->getCreci(),
                'usuario' => $corretor->getUsuario(),
                'status' => $corretor->getStatus(),
                'dataCadastro' => $corretor->getDataCadastro() ? $corretor->getDataCadastro()->format('Y-m-d') : null,
                'ativo' => $corretor->isAtivo(),
            ];
        }

        $locador = $this->entityManager->getRepository(PessoasLocadores::class)
            ->findOneBy(['pessoa' => $pessoaId]);
        if ($locador) {
            $dados['locador'] = [
                'id' => $locador->getId(),
                'formaRetirada' => $locador->getFormaRetirada() ? $locador->getFormaRetirada()->getId() : null,
                'dependentes' => $locador->getDependentes(),
                'diaRetirada' => $locador->getDiaRetirada(),
                'cobrarCpmf' => $locador->isCobrarCpmf(),
                'situacao' => $locador->getSituacao(),
                'codigoContabil' => $locador->getCodigoContabil(),
                'etiqueta' => $locador->isEtiqueta(),
                'cobrarTarifaRec' => $locador->isCobrarTarifaRec(),
                'dataFechamento' => $locador->getDataFechamento() ? $locador->getDataFechamento()->format('Y-m-d') : null,
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

        $pretendente = $this->entityManager->getRepository(PessoasPretendentes::class)
            ->findOneBy(['pessoa' => $pessoaId]);
        if ($pretendente) {
            $dados['pretendente'] = [
                'id' => $pretendente->getId(),
                'tipoImovel' => $pretendente->getTipoImovel() ? $pretendente->getTipoImovel()->getId() : null,
                'quartosDesejados' => $pretendente->getQuartosDesejados(),
                'aluguelMaximo' => $pretendente->getAluguelMaximo(),
                'logradouroDesejado' => $pretendente->getLogradouroDesejado() ? $pretendente->getLogradouroDesejado()->getId() : null,
                'disponivel' => $pretendente->isDisponivel(),
                'procuraAluguel' => $pretendente->isProcuraAluguel(),
                'procuraCompra' => $pretendente->isProcuraCompra(),
                'atendente' => $pretendente->getAtendente() ? $pretendente->getAtendente()->getId() : null,
                'tipoAtendimento' => $pretendente->getTipoAtendimento() ? $pretendente->getTipoAtendimento()->getId() : null,
                'dataCadastro' => $pretendente->getDataCadastro() ? $pretendente->getDataCadastro()->format('Y-m-d') : null,
                'observacoes' => $pretendente->getObservacoes(),
            ];
        }

        $contratante = $this->entityManager->getRepository(PessoasContratantes::class)
            ->findOneBy(['pessoa' => $pessoaId]);
        if ($contratante) {
            $dados['contratante'] = [
                'id' => $contratante->getId(),
            ];
        }

        $corretora = $this->entityManager->getRepository(PessoasCorretoras::class)
            ->findOneBy(['pessoa' => $pessoaId]);
        if ($corretora) {
            $dados['corretora'] = [
                'id' => $corretora->getId(),
            ];
        }
        
        return $dados;
    }

    public function buscarTelefonesPessoa(int $pessoaId): array
    {
        $telefones = [];
        $pessoasTelefones = $this->entityManager->getRepository(PessoasTelefones::class)
            ->findBy(['idPessoa' => $pessoaId]);

        foreach ($pessoasTelefones as $pessoaTelefone) {
            $telefone = $this->entityManager->getRepository(Telefones::class)
                ->find($pessoaTelefone->getIdTelefone());

            if ($telefone) {
                $telefones[] = [
                    'id' => $telefone->getId(),
                    'tipo' => $telefone->getTipo()->getId(),
                    'numero' => $telefone->getNumero()
                ];
            }
        }

        return $telefones;
    }

    public function buscarEnderecosPessoa(int $pessoaId): array
    {
        $enderecos = [];
        $enderecosEntidade = $this->entityManager->getRepository(Enderecos::class)
            ->findBy(['pessoa' => $pessoaId]);

        foreach ($enderecosEntidade as $endereco) {
            $logradouro = $endereco->getLogradouro();
            $bairro = $logradouro ? $logradouro->getBairro() : null;
            $cidade = $bairro ? $bairro->getCidade() : null;
            $estado = $cidade ? $cidade->getEstado() : null;

            $enderecos[] = [
                'id' => $endereco->getId(),
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

    public function buscarEmailsPessoa(int $pessoaId): array
    {
        $emails = [];
        $pessoasEmails = $this->entityManager->getRepository(PessoasEmails::class)
            ->findBy(['idPessoa' => $pessoaId]);

        foreach ($pessoasEmails as $pessoaEmail) {
            $email = $this->entityManager->getRepository(Emails::class)
                ->find($pessoaEmail->getIdEmail());

            if ($email) {
                $emails[] = [
                    'id' => $email->getId(),
                    'tipo' => $email->getTipo()->getId(),
                    'email' => $email->getEmail()
                ];
            }
        }

        return $emails;
    }

    public function buscarDocumentosPessoa(int $pessoaId): array
    {
        $resultado = $this->entityManager->getRepository(Pessoas::class)
            ->buscarDocumentosSecundarios($pessoaId);

        return array_map(fn($doc) => [
            'id' => $doc['id'],
            'tipo' => $doc['tipo'], // ID do tipo para o select
            'tipoNome' => $doc['tipoNome'] ?? '', // Nome do tipo para exibição
            'numero' => $doc['numero'],
            'orgaoEmissor' => $doc['orgaoEmissor'],
            'dataEmissao' => $doc['dataEmissao'],
            'dataVencimento' => $doc['dataVencimento'],
            'observacoes' => $doc['observacoes'],
        ], $resultado);
    }

    public function buscarChavesPixPessoa(int $pessoaId): array
    {
        $chavesPix = [];
        $chavesPixEntidade = $this->entityManager->getRepository(ChavesPix::class)
            ->findBy(['idPessoa' => (int) $pessoaId, 'ativo' => true]);

        foreach ($chavesPixEntidade as $chavePix) {
            $chavesPix[] = [
                'id' => $chavePix->getId(),
                'tipo' => $chavePix->getIdTipoChave(),
                'chave' => $chavePix->getChavePix(),
                'principal' => $chavePix->getPrincipal()
            ];
        }

        return $chavesPix;
    }

    public function buscarProfissoesPessoa(int $pessoaId): array
    {
        $profissoes = [];
        $pessoasProfissoes = $this->entityManager->getRepository(PessoasProfissoes::class)
            ->findBy(['idPessoa' => $pessoaId, 'ativo' => true]);

        foreach ($pessoasProfissoes as $pessoaProfissao) {
            $profissoes[] = [
                'id' => $pessoaProfissao->getId(),
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

    public function buscarContasBancariasPessoa(int $pessoaId): array
    {
        $contas = [];
        $contasBancarias = $this->entityManager->getRepository(ContasBancarias::class)
            ->findBy(['idPessoa' => $pessoaId, 'ativo' => true]);

        foreach ($contasBancarias as $conta) {
            $contas[] = [
                'id' => $conta->getId(),
                'banco' => $conta->getIdBanco() ? $conta->getIdBanco()->getId() : null,
                'agencia' => $conta->getIdAgencia() ? $conta->getIdAgencia()->getId() : null,
                'codigo' => $conta->getCodigo(),
                'digitoConta' => $conta->getDigitoConta(),
                'tipoConta' => $conta->getIdTipoConta() ? $conta->getIdTipoConta()->getId() : null,
                'titular' => $conta->getTitular(),
                'principal' => $conta->getPrincipal(),
                'descricao' => $conta->getDescricao()
            ];
        }

        return $contas;
    }

    public function buscarConjugePessoa(int $pessoaId): ?array
    {
        $relacionamento = $this->entityManager->getRepository(RelacionamentosFamiliares::class)
            ->findOneBy([
                'idPessoaOrigem' => $pessoaId,
                'tipoRelacionamento' => 'Cônjuge',
                'ativo' => true
            ]);

        if (!$relacionamento) {
            return null;
        }

        $conjugeId = $relacionamento->getIdPessoaDestino();
        $conjuge = $this->pessoaRepository->find($conjugeId);

        if (!$conjuge) {
            return null;
        }

        $cpf = $this->pessoaRepository->getCpfByPessoa($conjugeId);

        // IMPORTANTE: Buscar profissões do cônjuge, não da pessoa principal!
        $profissoesConjuge = $this->buscarProfissoesPessoa($conjugeId);

        return [
            'id' => $conjuge->getIdpessoa(),
            'nome' => $conjuge->getNome(),
            'cpf' => $cpf,
            'dataNascimento' => $conjuge->getDataNascimento() ? $conjuge->getDataNascimento()->format('Y-m-d') : null,
            'estadoCivil' => $conjuge->getEstadoCivil() ? $conjuge->getEstadoCivil()->getId() : null,
            'nacionalidade' => $conjuge->getNacionalidade() ? $conjuge->getNacionalidade()->getId() : null,
            'naturalidade' => $conjuge->getNaturalidade() ? $conjuge->getNaturalidade()->getId() : null,
            'nomePai' => $conjuge->getNomePai(),
            'nomeMae' => $conjuge->getNomeMae(),
            'renda' => $conjuge->getRenda(),
            'observacoes' => $conjuge->getObservacoes(),
            'telefones' => $this->buscarTelefonesPessoa($conjugeId),
            'enderecos' => $this->buscarEnderecosPessoa($conjugeId),
            'emails' => $this->buscarEmailsPessoa($conjugeId),
            'documentos' => $this->buscarDocumentosPessoa($conjugeId),
            'chavesPix' => $this->buscarChavesPixPessoa($conjugeId),
            'profissoes' => $profissoesConjuge, // Usando a variável já debugada
            'contasBancarias' => $this->buscarContasBancariasPessoa($conjugeId),
        ];
    }

    public function excluirEndereco(int $id): void
    {
        $endereco = $this->entityManager->getRepository(Enderecos::class)->find($id);
        if (!$endereco) {
            throw new \RuntimeException('Endereço não encontrado');
        }

        $this->entityManager->remove($endereco);
        $this->entityManager->flush();
    }

    public function excluirTelefone(int $id): void
    {
        $telefone = $this->entityManager->getRepository(Telefones::class)->find($id);
        if (!$telefone) {
            throw new \RuntimeException('Telefone não encontrado');
        }

        $pivot = $this->entityManager->getRepository(PessoasTelefones::class)->findOneBy(['idTelefone' => $id]);
        if ($pivot) {
            $this->entityManager->remove($pivot);
        }

        $this->entityManager->remove($telefone);
        $this->entityManager->flush();
    }

    public function excluirEmail(int $id): void
    {
        $email = $this->entityManager->getRepository(Emails::class)->find($id);
        if (!$email) {
            throw new \RuntimeException('Email não encontrado');
        }

        $pivot = $this->entityManager->getRepository(PessoasEmails::class)->findOneBy(['idEmail' => $id]);
        if ($pivot) {
            $this->entityManager->remove($pivot);
        }

        $this->entityManager->remove($email);
        $this->entityManager->flush();
    }

    public function excluirChavePix(int $id): void
    {
        $chavePix = $this->entityManager->getRepository(ChavesPix::class)->find($id);
        if (!$chavePix) {
            throw new \RuntimeException('Chave PIX não encontrada');
        }

        $this->entityManager->remove($chavePix);
        $this->entityManager->flush();
    }

    public function excluirDocumento(int $id): void
    {
        $documento = $this->entityManager->getRepository(PessoasDocumentos::class)->find($id);
        if (!$documento) {
            throw new \RuntimeException('Documento não encontrado');
        }

        $this->entityManager->remove($documento);
        $this->entityManager->flush();
    }

    public function excluirProfissao(int $id): void
    {
        $profissao = $this->entityManager->getRepository(PessoasProfissoes::class)->find($id);
        if (!$profissao) {
            throw new \RuntimeException('Profissão não encontrada');
        }

        $this->entityManager->remove($profissao);
        $this->entityManager->flush();
    }

    public function excluirContaBancaria(int $id): void
    {
        $contaBancaria = $this->entityManager->getRepository(ContasBancarias::class)->find($id);
        if (!$contaBancaria) {
            throw new \RuntimeException('Conta bancária não encontrada');
        }

        $this->entityManager->remove($contaBancaria);
        $this->entityManager->flush();
    }

    public function buscaPorNome(string $nome, ?string $doc, ?string $docType): ?Pessoas
    {
        if ($doc && $docType) {
            $pessoa = stripos($docType, 'cpf') !== false
                ? $this->pessoaRepository->findByCpfDocumento($doc)
                : $this->pessoaRepository->findByCnpjDocumento($doc);

            if ($pessoa && stripos($pessoa->getNome(), $nome) !== false) {
                return $pessoa;
            }
            return null;
        }

        $pessoas = $this->pessoaRepository->findByNome($nome);
        return match (count($pessoas)) {
            1 => $pessoas[0],
            0 => null,
            default => throw new \RuntimeException('Múltiplas pessoas encontradas. Informe CPF/CNPJ.'),
        };
    }

    /**
     * Busca cônjuge por critério (CPF, Nome, ID)
     * Apenas pessoas físicas podem ser cônjuges
     * IMPORTANTE: Uma pessoa nunca pode ser cônjuge de si mesma
     *
     * @param string $criteria Critério de busca ('cpf', 'nome', 'id')
     * @param string $value Valor da busca
     * @param int|null $pessoaIdExcluir ID da pessoa principal (para evitar auto-relacionamento)
     * @return array Array de pessoas encontradas
     */
    public function buscarConjugePorCriterio(string $criteria, string $value, ?int $pessoaIdExcluir = null): array
    {
        $criteria = strtolower(trim($criteria));
        $value = trim($value);

        if (empty($value)) {
            return [];
        }

        $resultado = [];

        switch ($criteria) {
            case 'cpf':
                $pessoa = $this->pessoaRepository->findByCpfDocumento($value);
                if ($pessoa && $pessoa->getFisicaJuridica() === 'fisica') {
                    $resultado[] = $pessoa;
                }
                break;

            case 'nome':
                $pessoas = $this->pessoaRepository->findPessoasFisicasByNome($value);
                $resultado = $pessoas;
                break;

            case 'id':
                $pessoa = $this->pessoaRepository->find((int)$value);
                if ($pessoa && $pessoa->getFisicaJuridica() === 'fisica') {
                    $resultado[] = $pessoa;
                }
                break;

            default:
                return [];
        }

        // ✅ VALIDAÇÃO: Uma pessoa nunca pode ser cônjuge de si mesma
        if ($pessoaIdExcluir !== null) {
            $resultado = array_filter($resultado, function($pessoa) use ($pessoaIdExcluir) {
                return $pessoa->getIdpessoa() !== $pessoaIdExcluir;
            });

            // Reindexar array após filtro
            $resultado = array_values($resultado);
        }

        return $resultado;
    }

    /**
     * Lista todas as pessoas com dados enriquecidos (CPF/CNPJ e tipos)
     *
     * @return array<int, array{entidade: Pessoas, cpf: ?string, cnpj: ?string, tipos: array<string>}>
     */
    public function listarPessoasEnriquecidas(): array
    {
        $pessoas = $this->pessoaRepository->findAll();
        $pessoasEnriquecidas = [];

        foreach ($pessoas as $pessoa) {
            $pessoaId = $pessoa->getIdpessoa();

            // Buscar CPF ou CNPJ via Repository
            $cpf = $this->pessoaRepository->getCpfByPessoa($pessoaId);
            $cnpj = $this->pessoaRepository->getCnpjByPessoa($pessoaId);

            // Buscar tipos da pessoa via Repository
            $tiposComDados = $this->pessoaRepository->findTiposComDados($pessoaId);
            $tipos = $tiposComDados['tipos'] ?? [];

            // Filtrar apenas tipos ativos
            $tiposAtivos = array_filter($tipos, fn($ativo) => $ativo === true);

            $pessoasEnriquecidas[] = [
                'entidade' => $pessoa,
                'cpf' => $cpf,
                'cnpj' => $cnpj,
                'tipos' => array_keys($tiposAtivos)
            ];
        }

        return $pessoasEnriquecidas;
    }
}