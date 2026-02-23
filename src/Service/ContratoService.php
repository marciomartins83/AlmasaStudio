<?php

namespace App\Service;

use App\Entity\ImoveisContratos;
use App\Entity\Imoveis;
use App\Entity\Pessoas;
use App\Repository\ImoveisContratosRepository;
use App\Repository\ImoveisRepository;
use App\Repository\PessoaRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * ContratoService - Fat Service
 * Contém TODA a lógica de negócio do módulo de contratos de locação
 *
 * Responsabilidades:
 * - Gerenciamento de transações
 * - Validações de negócio
 * - Operações de persistência (persist, flush, remove)
 * - Relacionamentos complexos (imóvel, locatário, fiador)
 * - Renovação e encerramento de contratos
 * - Reajustes de valores
 */
class ContratoService
{
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;
    private ImoveisContratosRepository $contratosRepository;
    private ImoveisRepository $imoveisRepository;
    private PessoaRepository $pessoaRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        LoggerInterface $logger,
        ImoveisContratosRepository $contratosRepository,
        ImoveisRepository $imoveisRepository,
        PessoaRepository $pessoaRepository
    ) {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->contratosRepository = $contratosRepository;
        $this->imoveisRepository = $imoveisRepository;
        $this->pessoaRepository = $pessoaRepository;
    }

    /**
     * Lista contratos com dados enriquecidos para exibição
     *
     * @param array $filtros
     * @return array
     */
    public function listarContratosEnriquecidos(array $filtros = []): array
    {
        $contratos = $this->contratosRepository->findByFiltros($filtros);
        $contratosEnriquecidos = [];

        foreach ($contratos as $contrato) {
            $contratosEnriquecidos[] = $this->enriquecerContrato($contrato);
        }

        return $contratosEnriquecidos;
    }

    /**
     * Busca contrato por ID com dados completos
     *
     * @param int $id
     * @return array|null
     */
    public function buscarContratoPorId(int $id): ?array
    {
        $contrato = $this->contratosRepository->find($id);

        if (!$contrato) {
            return null;
        }

        return $this->enriquecerContrato($contrato);
    }

    /**
     * Salva novo contrato com validações
     *
     * @param ImoveisContratos $contrato
     * @param array $dados
     * @return void
     * @throws \Exception
     */
    public function salvarContrato(ImoveisContratos $contrato, array $dados): void
    {
        $this->entityManager->getConnection()->beginTransaction();

        try {
            // Validar se já existe contrato vigente para o imóvel
            $contratoVigente = $this->contratosRepository->findContratoVigenteImovel(
                $dados['imovel_id']
            );

            if ($contratoVigente) {
                throw new \Exception('Já existe um contrato vigente para este imóvel.');
            }

            // Preencher dados do contrato
            $this->preencherContrato($contrato, $dados);

            // Validar datas
            if ($contrato->getDataFim() && $contrato->getDataFim() <= $contrato->getDataInicio()) {
                throw new \Exception('A data de fim deve ser posterior à data de início.');
            }

            // Calcular data do próximo reajuste se não informada
            if (!$contrato->getDataProximoReajuste() && $contrato->getDataInicio()) {
                $dataProximoReajuste = clone $contrato->getDataInicio();

                if ($contrato->getPeriodicidadeReajuste() === 'anual') {
                    $dataProximoReajuste->modify('+1 year');
                } elseif ($contrato->getPeriodicidadeReajuste() === 'semestral') {
                    $dataProximoReajuste->modify('+6 months');
                }

                $contrato->setDataProximoReajuste($dataProximoReajuste);
            }

            // Persistir contrato
            $this->entityManager->persist($contrato);
            $this->entityManager->flush();

            $this->entityManager->getConnection()->commit();

            $this->logger->info('Contrato criado com sucesso', [
                'contrato_id' => $contrato->getId(),
                'imovel_id' => $contrato->getImovel()->getId(),
            ]);
        } catch (\Exception $e) {
            $this->entityManager->getConnection()->rollBack();
            $this->logger->error('Erro ao salvar contrato', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Atualiza contrato existente
     *
     * @param ImoveisContratos $contrato
     * @param array $dados
     * @return void
     * @throws \Exception
     */
    public function atualizarContrato(ImoveisContratos $contrato, array $dados): void
    {
        $this->entityManager->getConnection()->beginTransaction();

        try {
            // Preencher dados atualizados
            $this->preencherContrato($contrato, $dados);

            // Validar datas
            if ($contrato->getDataFim() && $contrato->getDataFim() <= $contrato->getDataInicio()) {
                throw new \Exception('A data de fim deve ser posterior à data de início.');
            }

            $this->entityManager->flush();
            $this->entityManager->getConnection()->commit();

            $this->logger->info('Contrato atualizado com sucesso', [
                'contrato_id' => $contrato->getId(),
            ]);
        } catch (\Exception $e) {
            $this->entityManager->getConnection()->rollBack();
            $this->logger->error('Erro ao atualizar contrato', [
                'contrato_id' => $contrato->getId(),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Encerra contrato ativo
     *
     * @param int $contratoId
     * @param \DateTimeInterface $dataEncerramento
     * @param string|null $motivo
     * @return void
     * @throws \Exception
     */
    public function encerrarContrato(int $contratoId, \DateTimeInterface $dataEncerramento, ?string $motivo = null): void
    {
        $this->entityManager->getConnection()->beginTransaction();

        try {
            $contrato = $this->contratosRepository->find($contratoId);

            if (!$contrato) {
                throw new \Exception('Contrato não encontrado.');
            }

            if ($contrato->getStatus() !== 'ativo') {
                throw new \Exception('Apenas contratos ativos podem ser encerrados.');
            }

            $contrato->setStatus('encerrado');
            $contrato->setDataFim($dataEncerramento);
            $contrato->setAtivo(false);

            if ($motivo) {
                $observacoes = $contrato->getObservacoes() ?? '';
                $observacoes .= "\n[" . date('Y-m-d H:i:s') . "] Encerrado: " . $motivo;
                $contrato->setObservacoes($observacoes);
            }

            $this->entityManager->flush();
            $this->entityManager->getConnection()->commit();

            $this->logger->info('Contrato encerrado', [
                'contrato_id' => $contratoId,
                'data_encerramento' => $dataEncerramento->format('Y-m-d'),
            ]);
        } catch (\Exception $e) {
            $this->entityManager->getConnection()->rollBack();
            $this->logger->error('Erro ao encerrar contrato', [
                'contrato_id' => $contratoId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Renova contrato criando novo registro
     *
     * @param int $contratoAntigoId
     * @param array $dadosNovoContrato
     * @return ImoveisContratos
     * @throws \Exception
     */
    public function renovarContrato(int $contratoAntigoId, array $dadosNovoContrato): ImoveisContratos
    {
        $this->entityManager->getConnection()->beginTransaction();

        try {
            $contratoAntigo = $this->contratosRepository->find($contratoAntigoId);

            if (!$contratoAntigo) {
                throw new \Exception('Contrato original não encontrado.');
            }

            // Encerrar contrato antigo
            $contratoAntigo->setStatus('encerrado');
            $contratoAntigo->setAtivo(false);

            // Criar novo contrato baseado no antigo
            $novoContrato = new ImoveisContratos();
            $novoContrato->setImovel($contratoAntigo->getImovel());
            $novoContrato->setPessoaLocatario($contratoAntigo->getPessoaLocatario());
            $novoContrato->setPessoaFiador($contratoAntigo->getPessoaFiador());
            $novoContrato->setTipoContrato($contratoAntigo->getTipoContrato());
            $novoContrato->setDiaVencimento($contratoAntigo->getDiaVencimento());
            $novoContrato->setTaxaAdministracao($contratoAntigo->getTaxaAdministracao());
            $novoContrato->setTipoGarantia($contratoAntigo->getTipoGarantia());
            $novoContrato->setIndiceReajuste($contratoAntigo->getIndiceReajuste());
            $novoContrato->setPeriodicidadeReajuste($contratoAntigo->getPeriodicidadeReajuste());
            $novoContrato->setMultaRescisao($contratoAntigo->getMultaRescisao());
            $novoContrato->setCarenciaDias($contratoAntigo->getCarenciaDias());
            $novoContrato->setGeraBoleto($contratoAntigo->isGeraBoleto());
            $novoContrato->setEnviaEmail($contratoAntigo->isEnviaEmail());
            $novoContrato->setStatus('ativo');
            $novoContrato->setAtivo(true);

            // Sobrescrever com novos dados
            $this->preencherContrato($novoContrato, $dadosNovoContrato);

            // Calcular data do próximo reajuste
            if ($novoContrato->getDataInicio()) {
                $dataProximoReajuste = clone $novoContrato->getDataInicio();

                if ($novoContrato->getPeriodicidadeReajuste() === 'anual') {
                    $dataProximoReajuste->modify('+1 year');
                } elseif ($novoContrato->getPeriodicidadeReajuste() === 'semestral') {
                    $dataProximoReajuste->modify('+6 months');
                }

                $novoContrato->setDataProximoReajuste($dataProximoReajuste);
            }

            // Adicionar observação sobre renovação
            $observacoes = "Renovação do contrato #" . $contratoAntigo->getId();
            $novoContrato->setObservacoes($observacoes);

            $this->entityManager->persist($novoContrato);
            $this->entityManager->flush();
            $this->entityManager->getConnection()->commit();

            $this->logger->info('Contrato renovado', [
                'contrato_antigo_id' => $contratoAntigoId,
                'novo_contrato_id' => $novoContrato->getId(),
            ]);

            return $novoContrato;
        } catch (\Exception $e) {
            $this->entityManager->getConnection()->rollBack();
            $this->logger->error('Erro ao renovar contrato', [
                'contrato_antigo_id' => $contratoAntigoId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Busca contratos próximos ao vencimento
     *
     * @param int $dias
     * @return array
     */
    public function buscarContratosVencimentoProximo(int $dias = 30): array
    {
        $contratos = $this->contratosRepository->findContratosVencimentoProximo($dias);
        $contratosEnriquecidos = [];

        foreach ($contratos as $contrato) {
            $contratosEnriquecidos[] = $this->enriquecerContrato($contrato);
        }

        return $contratosEnriquecidos;
    }

    /**
     * Busca contratos que precisam de reajuste
     *
     * @return array
     */
    public function buscarContratosParaReajuste(): array
    {
        $contratos = $this->contratosRepository->findContratosParaReajuste();
        $contratosEnriquecidos = [];

        foreach ($contratos as $contrato) {
            $contratosEnriquecidos[] = $this->enriquecerContrato($contrato);
        }

        return $contratosEnriquecidos;
    }

    /**
     * Obtém estatísticas de contratos
     *
     * @return array
     */
    public function obterEstatisticas(): array
    {
        return $this->contratosRepository->getEstatisticas();
    }

    /**
     * Lista imóveis disponíveis para contrato
     *
     * @return array
     */
    public function listarImoveisDisponiveis(): array
    {
        // Buscar imóveis sem contrato vigente
        $todosImoveis = $this->imoveisRepository->findAll();
        $imoveisDisponiveis = [];

        foreach ($todosImoveis as $imovel) {
            $contratoVigente = $this->contratosRepository->findContratoVigenteImovel($imovel->getId());

            if (!$contratoVigente) {
                $imoveisDisponiveis[] = [
                    'id' => $imovel->getId(),
                    'codigo_interno' => $imovel->getCodigoInterno(),
                    'endereco' => $this->formatarEnderecoImovel($imovel),
                    'tipo' => $imovel->getTipoImovel()?->getTipo(),
                    'valor_aluguel' => $imovel->getValorAluguel(),
                ];
            }
        }

        return $imoveisDisponiveis;
    }

    /**
     * Lista locatários (pessoas que podem ser locatárias)
     *
     * @return array
     */
    public function listarLocatarios(): array
    {
        return $this->entityManager->createQuery(
            'SELECT p.idpessoa AS id, p.nome, p.tipoPessoa AS tipo
             FROM App\Entity\Pessoas p
             WHERE p.status = true
             ORDER BY p.nome ASC'
        )->getArrayResult();
    }

    /**
     * Lista fiadores (pessoas que podem ser fiadoras)
     *
     * @return array
     */
    public function listarFiadores(): array
    {
        return $this->entityManager->createQuery(
            'SELECT p.idpessoa AS id, p.nome, p.tipoPessoa AS tipo
             FROM App\Entity\Pessoas p
             WHERE p.status = true
             ORDER BY p.nome ASC'
        )->getArrayResult();
    }

    /**
     * Preenche contrato com dados do array
     *
     * @param ImoveisContratos $contrato
     * @param array $dados
     * @return void
     */
    private function preencherContrato(ImoveisContratos $contrato, array $dados): void
    {
        // Relacionamentos
        if (isset($dados['imovel_id'])) {
            $imovel = $this->imoveisRepository->find($dados['imovel_id']);
            if ($imovel) {
                $contrato->setImovel($imovel);
            }
        }

        if (isset($dados['locatario_id'])) {
            $locatario = $this->pessoaRepository->find($dados['locatario_id']);
            $contrato->setPessoaLocatario($locatario);
        }

        if (isset($dados['fiador_id'])) {
            $fiador = $this->pessoaRepository->find($dados['fiador_id']);
            $contrato->setPessoaFiador($fiador);
        }

        // Campos básicos
        if (isset($dados['tipo_contrato'])) {
            $contrato->setTipoContrato($dados['tipo_contrato']);
        }

        if (isset($dados['data_inicio'])) {
            $dataInicio = $dados['data_inicio'] instanceof \DateTimeInterface
                ? $dados['data_inicio']
                : new \DateTime($dados['data_inicio']);
            $contrato->setDataInicio($dataInicio);
        }

        if (isset($dados['data_fim'])) {
            $dataFim = $dados['data_fim'] instanceof \DateTimeInterface
                ? $dados['data_fim']
                : new \DateTime($dados['data_fim']);
            $contrato->setDataFim($dataFim);
        }

        if (isset($dados['valor_contrato'])) {
            $contrato->setValorContrato((string) $dados['valor_contrato']);
        }

        if (isset($dados['dia_vencimento'])) {
            $contrato->setDiaVencimento((int) $dados['dia_vencimento']);
        }

        if (isset($dados['status'])) {
            $contrato->setStatus($dados['status']);
        }

        if (isset($dados['observacoes'])) {
            $contrato->setObservacoes($dados['observacoes']);
        }

        // Campos adicionais
        if (isset($dados['taxa_administracao'])) {
            $contrato->setTaxaAdministracao((string) $dados['taxa_administracao']);
        }

        if (isset($dados['tipo_garantia'])) {
            $contrato->setTipoGarantia($dados['tipo_garantia']);
        }

        if (isset($dados['valor_caucao'])) {
            $contrato->setValorCaucao((string) $dados['valor_caucao']);
        }

        if (isset($dados['indice_reajuste'])) {
            $contrato->setIndiceReajuste($dados['indice_reajuste']);
        }

        if (isset($dados['periodicidade_reajuste'])) {
            $contrato->setPeriodicidadeReajuste($dados['periodicidade_reajuste']);
        }

        if (isset($dados['data_proximo_reajuste'])) {
            $dataReajuste = $dados['data_proximo_reajuste'] instanceof \DateTimeInterface
                ? $dados['data_proximo_reajuste']
                : new \DateTime($dados['data_proximo_reajuste']);
            $contrato->setDataProximoReajuste($dataReajuste);
        }

        if (isset($dados['multa_rescisao'])) {
            $contrato->setMultaRescisao((string) $dados['multa_rescisao']);
        }

        if (isset($dados['carencia_dias'])) {
            $contrato->setCarenciaDias((int) $dados['carencia_dias']);
        }

        if (isset($dados['gera_boleto'])) {
            $contrato->setGeraBoleto((bool) $dados['gera_boleto']);
        }

        if (isset($dados['envia_email'])) {
            $contrato->setEnviaEmail((bool) $dados['envia_email']);
        }

        if (isset($dados['ativo'])) {
            $contrato->setAtivo((bool) $dados['ativo']);
        }
    }

    /**
     * Enriquece dados do contrato para exibição
     *
     * @param ImoveisContratos $contrato
     * @return array
     */
    private function enriquecerContrato(ImoveisContratos $contrato): array
    {
        $imovel = $contrato->getImovel();
        $locatario = $contrato->getPessoaLocatario();
        $fiador = $contrato->getPessoaFiador();
        $proprietario = $imovel->getPessoaProprietario();

        return [
            'id' => $contrato->getId(),
            'imovel_id' => $imovel->getId(),
            'imovel_codigo' => $imovel->getCodigoInterno(),
            'imovel_endereco' => $this->formatarEnderecoImovel($imovel),
            'imovel_tipo' => $imovel->getTipoImovel()?->getTipo(),
            'locatario_id' => $locatario?->getIdpessoa(),
            'locatario_nome' => $locatario?->getNome(),
            'locatario_dados' => $this->extrairDadosPessoa($locatario),
            'fiador_id' => $fiador?->getIdpessoa(),
            'fiador_nome' => $fiador?->getNome(),
            'fiador_dados' => $this->extrairDadosPessoa($fiador),
            'proprietario_id' => $proprietario?->getIdpessoa(),
            'proprietario_nome' => $proprietario?->getNome(),
            'proprietario_dados' => $this->extrairDadosPessoa($proprietario),
            'tipo_contrato' => $contrato->getTipoContrato(),
            'tipo_contrato_label' => $this->getLabelTipoContrato($contrato->getTipoContrato()),
            'data_inicio' => $contrato->getDataInicio(),
            'data_fim' => $contrato->getDataFim(),
            'duracao_meses' => $contrato->getDuracaoMeses(),
            'valor_contrato' => $contrato->getValorContrato(),
            'valor_liquido_proprietario' => $contrato->getValorLiquidoProprietario(),
            'dia_vencimento' => $contrato->getDiaVencimento(),
            'status' => $contrato->getStatus(),
            'status_label' => $this->getLabelStatus($contrato->getStatus()),
            'is_vigente' => $contrato->isVigente(),
            'taxa_administracao' => $contrato->getTaxaAdministracao(),
            'tipo_garantia' => $contrato->getTipoGarantia(),
            'tipo_garantia_label' => $this->getLabelTipoGarantia($contrato->getTipoGarantia()),
            'valor_caucao' => $contrato->getValorCaucao(),
            'indice_reajuste' => $contrato->getIndiceReajuste(),
            'periodicidade_reajuste' => $contrato->getPeriodicidadeReajuste(),
            'data_proximo_reajuste' => $contrato->getDataProximoReajuste(),
            'multa_rescisao' => $contrato->getMultaRescisao(),
            'carencia_dias' => $contrato->getCarenciaDias(),
            'gera_boleto' => $contrato->isGeraBoleto(),
            'envia_email' => $contrato->isEnviaEmail(),
            'ativo' => $contrato->isAtivo(),
            'observacoes' => $contrato->getObservacoes(),
            'created_at' => $contrato->getCreatedAt(),
            'updated_at' => $contrato->getUpdatedAt(),
        ];
    }

    /**
     * Formata endereço completo do imóvel
     *
     * @param Imoveis $imovel
     * @return string
     */
    private function extrairDadosPessoa(?Pessoas $pessoa): ?array
    {
        if (!$pessoa) {
            return null;
        }

        try {
            $estadoCivil = $pessoa->getEstadoCivil()?->getNome();
        } catch (\Throwable) {
            $estadoCivil = null;
        }
        try {
            $nacionalidade = $pessoa->getNacionalidade()?->getNome();
        } catch (\Throwable) {
            $nacionalidade = null;
        }
        try {
            $naturalidade = $pessoa->getNaturalidade()?->getNome();
        } catch (\Throwable) {
            $naturalidade = null;
        }

        return [
            'idpessoa' => $pessoa->getIdpessoa(),
            'nome' => $pessoa->getNome(),
            'fisicaJuridica' => $pessoa->getFisicaJuridica(),
            'dataNascimento' => $pessoa->getDataNascimento(),
            'estadoCivil' => $estadoCivil,
            'status' => $pessoa->getStatus(),
            'nacionalidade' => $nacionalidade,
            'naturalidade' => $naturalidade,
            'nomePai' => $pessoa->getNomePai(),
            'nomeMae' => $pessoa->getNomeMae(),
            'renda' => $pessoa->getRenda(),
            'dtCadastro' => $pessoa->getDtCadastro(),
            'observacoes' => $pessoa->getObservacoes(),
        ];
    }

    private function formatarEnderecoImovel(Imoveis $imovel): string
    {
        $endereco = $imovel->getEndereco();

        if (!$endereco) {
            return 'Endereço não cadastrado';
        }

        $partes = [];

        $logradouro = $endereco->getLogradouro();
        if ($logradouro) {
            $partes[] = $logradouro->getLogradouro();
        }

        if ($endereco->getEndNumero()) {
            $partes[] = 'nº ' . $endereco->getEndNumero();
        }

        if ($endereco->getComplemento()) {
            $partes[] = $endereco->getComplemento();
        }

        if ($logradouro) {
            $bairro = $logradouro->getBairro();
            if ($bairro) {
                $partes[] = $bairro->getNome();
                $cidade = $bairro->getCidade();
                if ($cidade) {
                    $partes[] = $cidade->getNome() . '/' . $cidade->getEstado()->getUf();
                }
            }
        }

        return implode(', ', $partes);
    }

    /**
     * Retorna label amigável para tipo de contrato
     *
     * @param string $tipo
     * @return string
     */
    private function getLabelTipoContrato(string $tipo): string
    {
        $labels = [
            'locacao' => 'Locação',
            'temporada' => 'Temporada',
            'comercial' => 'Comercial',
            'residencial' => 'Residencial',
        ];

        return $labels[$tipo] ?? $tipo;
    }

    /**
     * Retorna label amigável para status
     *
     * @param string $status
     * @return string
     */
    private function getLabelStatus(string $status): string
    {
        $labels = [
            'ativo' => 'Ativo',
            'encerrado' => 'Encerrado',
            'rescindido' => 'Rescindido',
            'pendente' => 'Pendente',
        ];

        return $labels[$status] ?? $status;
    }

    /**
     * Retorna label amigável para tipo de garantia
     *
     * @param string $tipo
     * @return string
     */
    private function getLabelTipoGarantia(string $tipo): string
    {
        $labels = [
            'fiador' => 'Fiador',
            'caucao' => 'Caução',
            'seguro_fianca' => 'Seguro Fiança',
            'titulo_capitalizacao' => 'Título de Capitalização',
        ];

        return $labels[$tipo] ?? $tipo;
    }
}
