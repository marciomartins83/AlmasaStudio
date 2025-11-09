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

    public function criarPessoa(Pessoas $pessoa, array $formData, string $tipoPessoa): void
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

            $this->salvarDadosMultiplosCorrigido($pessoaId, $formData);
            $this->salvarTipoEspecifico($pessoa, $tipoPessoa, $formData);

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

    public function atualizarPessoa(Pessoas $pessoa, array $formData, string $tipoPessoa): void
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
            if (is_string($tipoPessoa)) {
                $pessoa->setTipoPessoa($this->convertTipoPessoaToId($tipoPessoa));
            } else {
                $pessoa->setTipoPessoa((int) $tipoPessoa);
            }

            $this->entityManager->persist($pessoa);
            $this->entityManager->flush();

            $this->limparDadosMultiplosApenasSeEnviados($pessoa->getIdpessoa(), $formData);

            if (!empty($cpfCnpj)) {
                $this->salvarDocumentoPrincipal($pessoa, $cpfCnpj);
            }

            $this->salvarDadosMultiplosCorrigido($pessoa->getIdpessoa(), $formData);
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

    private function salvarDadosMultiplosCorrigido(int $pessoaId, array $requestData): void
    {
        if (isset($requestData['telefones']) && is_array($requestData['telefones'])) {
            $telRepo = $this->entityManager->getRepository(Telefones::class);

            foreach ($requestData['telefones'] as $telefoneData) {
                if (!empty($telefoneData['tipo']) && !empty($telefoneData['numero'])) {
                    $numeroLimpo = preg_replace('/\D/', '', $telefoneData['numero']);

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

        if (isset($requestData['emails']) && is_array($requestData['emails'])) {
            $emailRepo = $this->entityManager->getRepository(Emails::class);

            foreach ($requestData['emails'] as $emailData) {
                if (!empty($emailData['tipo']) && !empty($emailData['email'])) {
                    $emailLimpo = strtolower(trim($emailData['email']));
                    $criteria   = ['email' => $emailLimpo];
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

        if (isset($requestData['enderecos']) && is_array($requestData['enderecos'])) {
            foreach ($requestData['enderecos'] as $enderecoData) {
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

        if (isset($requestData['chaves_pix']) && is_array($requestData['chaves_pix'])) {
            foreach ($requestData['chaves_pix'] as $pixData) {
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

        if (isset($requestData['documentos']) && is_array($requestData['documentos'])) {
            foreach ($requestData['documentos'] as $documentoData) {
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

        if (isset($requestData['profissoes']) && is_array($requestData['profissoes'])) {
            foreach ($requestData['profissoes'] as $profissaoData) {
                if (!empty($profissaoData['profissao'])) {
                    $pessoaProfissao = new PessoasProfissoes();
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

                    $this->entityManager->persist($pessoaProfissao);
                }
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

    private function salvarTipoEspecifico(Pessoas $pessoa, string $tipoPessoa, array $data): void
    {
        if (!$tipoPessoa) {
            return;
        }

        switch ($tipoPessoa) {
            case 'fiador':
                $fiador = new PessoasFiadores();
                $fiador->setIdPessoa($pessoa->getIdpessoa());
                $this->entityManager->persist($fiador);
                break;

            case 'corretor':
                $corretor = new PessoasCorretores();
                $corretor->setPessoa($pessoa);
                $this->entityManager->persist($corretor);
                break;

            case 'locador':
                $locador = new PessoasLocadores();
                $locador->setPessoa($pessoa);
                $this->entityManager->persist($locador);
                break;

            case 'pretendente':
                $pretendente = new PessoasPretendentes();
                $pretendente->setPessoa($pessoa);
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

    private function salvarConjuge(Pessoas $pessoa, array $requestData): void
    {
        $this->logger->info('DEBUG salvarConjuge - requestData keys: ' . implode(', ', array_keys($requestData)));

        $conjugeParaRelacionar = null;

        $conjugeId = $requestData['conjuge']['id'] ?? $requestData['conjuge_id'] ?? null;
        if ($conjugeId) {
            $this->logger->info('DEBUG: Tentando encontrar cônjuge existente com ID: ' . $conjugeId);
            $conjugeParaRelacionar = $this->pessoaRepository->find($conjugeId);
        }

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

            $this->salvarDocumentoPrincipalConjuge($novoConjuge->getIdpessoa(), $novoConjugeData['cpf']);
            $this->salvarDadosMultiplosConjuge($novoConjuge->getIdpessoa(), $requestData);

            $conjugeParaRelacionar = $novoConjuge;
        }

        if ($conjugeParaRelacionar) {
            $this->logger->info('DEBUG: Estabelecendo relacionamento familiar');
            $relacionamentoRepo = $this->entityManager->getRepository(RelacionamentosFamiliares::class);

            $existente = $relacionamentoRepo->findOneBy([
                'idPessoaOrigem' => $pessoa->getIdpessoa(),
                'idPessoaDestino' => $conjugeParaRelacionar->getIdpessoa(),
                'tipoRelacionamento' => 'Cônjuge'
            ]);

            if (!$existente) {
                $dataInicioRelacionamento = new \DateTime();

                $relacionamento1 = new RelacionamentosFamiliares();
                $relacionamento1->setIdPessoaOrigem($pessoa->getIdpessoa());
                $relacionamento1->setIdPessoaDestino($conjugeParaRelacionar->getIdpessoa());
                $relacionamento1->setTipoRelacionamento('Cônjuge');
                $relacionamento1->setAtivo(true);
                $relacionamento1->setDataInicio($dataInicioRelacionamento);
                $this->entityManager->persist($relacionamento1);

                $relacionamento2 = new RelacionamentosFamiliares();
                $relacionamento2->setIdPessoaOrigem($conjugeParaRelacionar->getIdpessoa());
                $relacionamento2->setIdPessoaDestino($pessoa->getIdpessoa());
                $relacionamento2->setTipoRelacionamento('Cônjuge');
                $relacionamento2->setAtivo(true);
                $relacionamento2->setDataInicio($dataInicioRelacionamento);
                $this->entityManager->persist($relacionamento2);
            }
        }
    }

    private function salvarDocumentoPrincipalConjuge(int $conjugeId, string $documento): void
    {
        $documento = preg_replace('/[^\d]/', '', $documento);
        $tipoDocumento = strlen($documento) === 11 ? 'CPF' : 'CNPJ';

        $tipoDocumentoEntity = $this->entityManager->getRepository(TiposDocumentos::class)
            ->findOneBy(['tipo' => $tipoDocumento]);

        if ($tipoDocumentoEntity) {
            $pessoaDocumento = new PessoasDocumentos();
            $conjuge = $this->entityManager->getRepository(Pessoas::class)->find($conjugeId);
            $pessoaDocumento->setPessoa($conjuge);
            $pessoaDocumento->setTipoDocumento($tipoDocumentoEntity);
            $pessoaDocumento->setNumeroDocumento($documento);
            $pessoaDocumento->setAtivo(true);

            $this->entityManager->persist($pessoaDocumento);
        }
    }

    private function salvarDadosMultiplosConjuge(int $conjugeId, array $requestData): void
    {
        if (isset($requestData['conjuge_telefones']) && is_array($requestData['conjuge_telefones'])) {
            foreach ($requestData['conjuge_telefones'] as $telefoneData) {
                if (!empty($telefoneData['tipo']) && !empty($telefoneData['numero'])) {
                    $telefone = new Telefones();
                    $telefone->setTipo($this->entityManager->getReference(\App\Entity\TiposTelefones::class, (int)$telefoneData['tipo']));
                    $telefone->setNumero($telefoneData['numero']);
                    $this->entityManager->persist($telefone);
                    $this->entityManager->flush();

                    $pessoaTelefone = new PessoasTelefones();
                    $pessoaTelefone->setIdPessoa($conjugeId);
                    $pessoaTelefone->setIdTelefone($telefone->getId());
                    $this->entityManager->persist($pessoaTelefone);
                }
            }
        }

        if (isset($requestData['conjuge_emails']) && is_array($requestData['conjuge_emails'])) {
            foreach ($requestData['conjuge_emails'] as $emailData) {
                if (!empty($emailData['tipo']) && !empty($emailData['email'])) {
                    $email = new Emails();
                    $email->setEmail($emailData['email']);
                    $email->setTipo($this->entityManager->getReference(\App\Entity\TiposEmails::class, (int)$emailData['tipo']));
                    $this->entityManager->persist($email);
                    $this->entityManager->flush();

                    $pessoaEmail = new PessoasEmails();
                    $pessoaEmail->setIdPessoa($conjugeId);
                    $pessoaEmail->setIdEmail($email->getId());
                    $this->entityManager->persist($pessoaEmail);
                }
            }
        }

        if (isset($requestData['conjuge_enderecos']) && is_array($requestData['conjuge_enderecos'])) {
            foreach ($requestData['conjuge_enderecos'] as $enderecoData) {
                if (!empty($enderecoData['tipo']) && !empty($enderecoData['numero'])) {
                    $logradouroId = $this->buscarOuCriarLogradouro($enderecoData);

                    $endereco = new Enderecos();
                    $endereco->setPessoa($this->entityManager->getReference(Pessoas::class, $conjugeId));
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

        if (isset($requestData['conjuge_chaves_pix']) && is_array($requestData['conjuge_chaves_pix'])) {
            foreach ($requestData['conjuge_chaves_pix'] as $pixData) {
                if (!empty($pixData['tipo']) && !empty($pixData['chave'])) {
                    $chavePix = new ChavesPix();
                    $chavePix->setIdPessoa($conjugeId);
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

        if (isset($requestData['conjuge_documentos']) && is_array($requestData['conjuge_documentos'])) {
            foreach ($requestData['conjuge_documentos'] as $documentoData) {
                if (!empty($documentoData['tipo']) && !empty($documentoData['numero'])) {
                    $documento = new PessoasDocumentos();
                    $conjugeRef = $this->entityManager->getReference(Pessoas::class, $conjugeId);
                    $documento->setPessoa($conjugeRef);

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

        if (isset($requestData['conjuge_profissoes']) && is_array($requestData['conjuge_profissoes'])) {
            foreach ($requestData['conjuge_profissoes'] as $profissaoData) {
                if (!empty($profissaoData['profissao'])) {
                    $pessoaProfissao = new PessoasProfissoes();
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

                    if (method_exists($pessoaProfissao, 'setAtivo')) {
                        $pessoaProfissao->setAtivo(true);
                    }

                    $this->entityManager->persist($pessoaProfissao);
                }
            }
        }
    }

    private function processarConjugeEdicao(Pessoas $pessoa, array $requestData): void
    {
        $relacionamentoExistente = $this->entityManager->getRepository(RelacionamentosFamiliares::class)
            ->findOneBy([
                'idPessoaOrigem' => $pessoa->getIdpessoa(),
                'tipoRelacionamento' => 'Cônjuge',
                'ativo' => true
            ]);

        $temConjuge = !empty($requestData['novo_conjuge']) || !empty($requestData['conjuge_id']);

        if ($relacionamentoExistente && !$temConjuge) {
            $relacionamentoInverso = $this->entityManager->getRepository(RelacionamentosFamiliares::class)
                ->findOneBy([
                    'idPessoaOrigem' => $relacionamentoExistente->getIdPessoaDestino(),
                    'idPessoaDestino' => $pessoa->getIdpessoa(),
                    'tipoRelacionamento' => 'Cônjuge'
                ]);

            $this->entityManager->remove($relacionamentoExistente);
            if ($relacionamentoInverso) {
                $this->entityManager->remove($relacionamentoInverso);
            }
        }

        if ($temConjuge) {
            $this->salvarConjuge($pessoa, $requestData);
        }
    }

    private function limparDadosMultiplosApenasSeEnviados(int $pessoaId, array $formData): void
    {
        $pessoaRef = $this->entityManager->getReference(Pessoas::class, $pessoaId);

        $tem = static function (string $chave) use ($formData): bool {
            return array_key_exists($chave, $formData) && is_array($formData[$chave]);
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
            ];
        }

        $locador = $this->entityManager->getRepository(PessoasLocadores::class)
            ->findOneBy(['pessoa' => $pessoaId]);
        if ($locador) {
            $dados['locador'] = [
                'id' => $locador->getId(),
            ];
        }

        $pretendente = $this->entityManager->getRepository(PessoasPretendentes::class)
            ->findOneBy(['pessoa' => $pessoaId]);
        if ($pretendente) {
            $dados['pretendente'] = [
                'id' => $pretendente->getId(),
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
            'tipo' => $doc['tipo'],
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
}