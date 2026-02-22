<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Boletos;
use App\Entity\BoletosLogApi;
use App\Entity\ConfiguracoesApiBanco;
use App\Entity\Enderecos;
use App\Entity\LancamentosFinanceiros;
use App\Entity\Pessoas;
use App\Entity\PessoasDocumentos;
use App\Repository\BoletosRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service para operações de Boleto via API Santander
 *
 * Gerencia registro, consulta, alteração e baixa de boletos
 */
class BoletoSantanderService
{
    public function __construct(
        private EntityManagerInterface $em,
        private SantanderAuthService $authService,
        private BoletosRepository $boletosRepository,
        private LoggerInterface $logger
    ) {}

    /**
     * Gera próximo nosso número para o convênio
     */
    public function gerarNossoNumero(ConfiguracoesApiBanco $config): string
    {
        // Formato Santander: Convênio (7 dígitos) + Sequencial (13 dígitos) = 20 dígitos
        $convenio = str_pad(substr($config->getConvenio(), 0, 7), 7, '0', STR_PAD_LEFT);

        // Buscar último nosso número usado
        $ultimo = $this->boletosRepository->findUltimoNossoNumero($config->getId());

        if ($ultimo) {
            // Extrair sequencial do último nosso número
            $sequencial = intval(substr($ultimo, 7)) + 1;
        } else {
            $sequencial = 1;
        }

        // Formatar: 7 dígitos convênio + 13 dígitos sequencial
        return $convenio . str_pad((string) $sequencial, 13, '0', STR_PAD_LEFT);
    }

    /**
     * Cria um novo boleto (sem registrar na API ainda)
     */
    public function criarBoleto(
        ConfiguracoesApiBanco $config,
        Pessoas $pagador,
        float $valorNominal,
        \DateTimeInterface $dataVencimento,
        ?LancamentosFinanceiros $lancamento = null,
        array $opcoes = []
    ): Boletos {
        $boleto = new Boletos();

        $boleto->setConfiguracaoApi($config);
        $boleto->setPessoaPagador($pagador);
        $boleto->setNossoNumero($this->gerarNossoNumero($config));
        $boleto->setValorNominal(number_format($valorNominal, 2, '.', ''));
        $boleto->setDataVencimento($dataVencimento);
        $boleto->setDataEmissao(new \DateTime());
        $boleto->setStatus(Boletos::STATUS_PENDENTE);

        if ($lancamento) {
            $boleto->setLancamentoFinanceiro($lancamento);
            $boleto->setImovel($lancamento->getImovel());
            $boleto->setSeuNumero((string) $lancamento->getId());
        }

        // Opções adicionais
        if (!empty($opcoes['mensagem_pagador'])) {
            $boleto->setMensagemPagador($opcoes['mensagem_pagador']);
        }

        if (!empty($opcoes['data_limite_pagamento'])) {
            $boleto->setDataLimitePagamento($opcoes['data_limite_pagamento']);
        }

        // Multa
        if (!empty($opcoes['valor_multa'])) {
            $boleto->setValorMulta(number_format((float) $opcoes['valor_multa'], 2, '.', ''));
            $boleto->setTipoMulta($opcoes['tipo_multa'] ?? Boletos::MULTA_PERCENTUAL);
            $boleto->setDataMulta($dataVencimento);
        }

        // Juros
        if (!empty($opcoes['valor_juros_dia'])) {
            $boleto->setValorJurosDia(number_format((float) $opcoes['valor_juros_dia'], 2, '.', ''));
            $boleto->setTipoJuros($opcoes['tipo_juros'] ?? Boletos::JUROS_VALOR_DIA);
        }

        // Desconto
        if (!empty($opcoes['valor_desconto']) && !empty($opcoes['data_desconto'])) {
            $boleto->setValorDesconto(number_format((float) $opcoes['valor_desconto'], 2, '.', ''));
            $boleto->setDataDesconto($opcoes['data_desconto']);
            $boleto->setTipoDesconto($opcoes['tipo_desconto'] ?? Boletos::DESCONTO_VALOR_DATA_FIXA);
        }

        $this->em->persist($boleto);
        $this->em->flush();

        $this->logger->info('[BoletoSantander] Boleto criado', [
            'id' => $boleto->getId(),
            'nosso_numero' => $boleto->getNossoNumero(),
            'valor' => $boleto->getValorNominal()
        ]);

        return $boleto;
    }

    /**
     * Registra boleto na API Santander
     */
    public function registrarBoleto(Boletos $boleto): array
    {
        $config = $boleto->getConfiguracaoApi();
        $pagador = $boleto->getPessoaPagador();

        // Validações
        if ($boleto->isRegistrado()) {
            return [
                'sucesso' => false,
                'boleto' => $boleto,
                'mensagem' => 'Boleto já está registrado'
            ];
        }

        // Montar payload
        $payload = $this->montarPayloadRegistro($boleto, $pagador, $config);

        // Criar log
        $log = new BoletosLogApi();
        $log->setBoleto($boleto);
        $log->setOperacao(BoletosLogApi::OPERACAO_REGISTRO);
        $log->setRequestPayload(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $this->em->getConnection()->beginTransaction();

        try {
            // Fazer requisição
            $endpoint = '/workspaces/' . ($config->getWorkspaceId() ?? 'default') . '/bank_slips';
            $response = $this->authService->request($config, 'POST', $endpoint, $payload);

            $log->setHttpCode($response['httpCode']);
            $log->setResponsePayload(json_encode($response['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            if ($response['httpCode'] === 201 || $response['httpCode'] === 200) {
                // Sucesso
                $log->setSucesso(true);
                $data = $response['data'];

                // Atualizar boleto com dados retornados
                $boleto->setStatus(Boletos::STATUS_REGISTRADO);
                $boleto->setCodigoBarras($data['barCode'] ?? null);
                $boleto->setLinhaDigitavel($data['digitableLine'] ?? null);
                $boleto->setIdTituloBanco($data['id'] ?? null);
                $boleto->setConvenioBanco($config->getConvenio());

                // QR Code PIX (boleto híbrido)
                if (!empty($data['qrCode'])) {
                    $boleto->setTxidPix($data['qrCode']['txId'] ?? null);
                    $boleto->setQrcodePix($data['qrCode']['emv'] ?? null);
                }

                $boleto->setDataRegistro(new \DateTime());
                $boleto->setUltimoErro(null);

            } else {
                // Erro
                $log->setSucesso(false);
                $errorMsg = $this->extrairMensagemErro($response['data']);
                $log->setMensagemErro($errorMsg);

                $boleto->setStatus(Boletos::STATUS_ERRO);
                $boleto->setUltimoErro($errorMsg);
            }

            $boleto->setTentativasRegistro($boleto->getTentativasRegistro() + 1);

        } catch (\Exception $e) {
            $log->setSucesso(false);
            $log->setMensagemErro($e->getMessage());

            $boleto->setStatus(Boletos::STATUS_ERRO);
            $boleto->setUltimoErro($e->getMessage());
            $boleto->setTentativasRegistro($boleto->getTentativasRegistro() + 1);

            $this->logger->error('[BoletoSantander] Erro ao registrar', [
                'boleto_id' => $boleto->getId(),
                'error' => $e->getMessage()
            ]);
        }

        $this->em->persist($log);
        $this->em->persist($boleto);
        $this->em->flush();
        $this->em->getConnection()->commit();

        return [
            'sucesso' => $log->isSucesso(),
            'boleto' => $boleto,
            'mensagem' => $log->isSucesso()
                ? 'Boleto registrado com sucesso'
                : ($log->getMensagemErro() ?? 'Erro ao registrar boleto')
        ];
    }

    /**
     * Consulta status do boleto na API
     */
    public function consultarBoleto(Boletos $boleto): array
    {
        $config = $boleto->getConfiguracaoApi();

        if (!$boleto->getIdTituloBanco()) {
            return [
                'sucesso' => false,
                'boleto' => $boleto,
                'dados' => [],
                'mensagem' => 'Boleto não possui ID do banco para consulta'
            ];
        }

        // Criar log
        $log = new BoletosLogApi();
        $log->setBoleto($boleto);
        $log->setOperacao(BoletosLogApi::OPERACAO_CONSULTA);

        try {
            $endpoint = '/workspaces/' . ($config->getWorkspaceId() ?? 'default') . '/bank_slips/' . $boleto->getIdTituloBanco();
            $response = $this->authService->request($config, 'GET', $endpoint);

            $log->setHttpCode($response['httpCode']);
            $log->setResponsePayload(json_encode($response['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            if ($response['httpCode'] === 200) {
                $log->setSucesso(true);
                $data = $response['data'];

                // Atualizar status se mudou
                $this->atualizarStatusPorConsulta($boleto, $data);

                $this->em->persist($boleto);

            } else {
                $log->setSucesso(false);
                $log->setMensagemErro($this->extrairMensagemErro($response['data']));
            }

        } catch (\Exception $e) {
            $log->setSucesso(false);
            $log->setMensagemErro($e->getMessage());

            $this->logger->error('[BoletoSantander] Erro ao consultar', [
                'boleto_id' => $boleto->getId(),
                'error' => $e->getMessage()
            ]);
        }

        $this->em->persist($log);
        $this->em->flush();

        return [
            'sucesso' => $log->isSucesso(),
            'boleto' => $boleto,
            'dados' => $response['data'] ?? [],
            'mensagem' => $log->isSucesso() ? 'Consulta realizada com sucesso' : $log->getMensagemErro()
        ];
    }

    /**
     * Solicita baixa do boleto
     */
    public function baixarBoleto(Boletos $boleto, string $motivo = 'SOLICITACAO_BENEFICIARIO'): array
    {
        $config = $boleto->getConfiguracaoApi();

        if (!$boleto->getIdTituloBanco()) {
            return [
                'sucesso' => false,
                'boleto' => $boleto,
                'mensagem' => 'Boleto não possui ID do banco para baixa'
            ];
        }

        if ($boleto->isPago()) {
            return [
                'sucesso' => false,
                'boleto' => $boleto,
                'mensagem' => 'Boleto já está pago e não pode ser baixado'
            ];
        }

        // Criar log
        $log = new BoletosLogApi();
        $log->setBoleto($boleto);
        $log->setOperacao(BoletosLogApi::OPERACAO_BAIXA);

        $payload = [
            'status' => 'BAIXADO',
            'reason' => $motivo
        ];

        $log->setRequestPayload(json_encode($payload, JSON_PRETTY_PRINT));

        try {
            $endpoint = '/workspaces/' . ($config->getWorkspaceId() ?? 'default') . '/bank_slips/' . $boleto->getIdTituloBanco();
            $response = $this->authService->request($config, 'PATCH', $endpoint, $payload);

            $log->setHttpCode($response['httpCode']);
            $log->setResponsePayload(json_encode($response['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            if ($response['httpCode'] === 200 || $response['httpCode'] === 204) {
                $log->setSucesso(true);

                $boleto->setStatus(Boletos::STATUS_BAIXADO);
                $boleto->setDataBaixa(new \DateTime());
                $boleto->setMotivoBaixa($motivo);

                $this->em->persist($boleto);

            } else {
                $log->setSucesso(false);
                $log->setMensagemErro($this->extrairMensagemErro($response['data']));
            }

        } catch (\Exception $e) {
            $log->setSucesso(false);
            $log->setMensagemErro($e->getMessage());

            $this->logger->error('[BoletoSantander] Erro ao baixar', [
                'boleto_id' => $boleto->getId(),
                'error' => $e->getMessage()
            ]);
        }

        $this->em->persist($log);
        $this->em->flush();

        return [
            'sucesso' => $log->isSucesso(),
            'boleto' => $boleto,
            'mensagem' => $log->isSucesso() ? 'Boleto baixado com sucesso' : $log->getMensagemErro()
        ];
    }

    /**
     * Registra múltiplos boletos em lote
     */
    public function registrarLote(array $boletos): array
    {
        $resultados = [
            'total' => count($boletos),
            'sucesso' => 0,
            'erro' => 0,
            'detalhes' => []
        ];

        foreach ($boletos as $boleto) {
            $resultado = $this->registrarBoleto($boleto);
            $resultados['detalhes'][] = [
                'boleto_id' => $boleto->getId(),
                'nosso_numero' => $boleto->getNossoNumero(),
                'sucesso' => $resultado['sucesso'],
                'mensagem' => $resultado['mensagem']
            ];

            if ($resultado['sucesso']) {
                $resultados['sucesso']++;
            } else {
                $resultados['erro']++;
            }
        }

        return $resultados;
    }

    /**
     * Consulta e atualiza status de boletos registrados
     */
    public function atualizarStatusBoletos(int $limite = 100): array
    {
        $boletos = $this->boletosRepository->findParaConsultaStatus();
        $boletos = array_slice($boletos, 0, $limite);

        $resultados = [
            'total' => count($boletos),
            'atualizados' => 0,
            'erros' => 0
        ];

        foreach ($boletos as $boleto) {
            $resultado = $this->consultarBoleto($boleto);

            if ($resultado['sucesso']) {
                $resultados['atualizados']++;
            } else {
                $resultados['erros']++;
            }
        }

        return $resultados;
    }

    /**
     * Monta payload para registro de boleto na API
     */
    private function montarPayloadRegistro(Boletos $boleto, Pessoas $pagador, ConfiguracoesApiBanco $config): array
    {
        // Dados do pagador
        $documento = $this->getDocumentoPagador($pagador);
        $documentoLimpo = preg_replace('/\D/', '', $documento);
        $tipoPessoa = strlen($documentoLimpo) === 11 ? 'F' : 'J';

        $payload = [
            'environment' => [
                'type' => $config->getAmbiente() === 'producao' ? 'PRODUCAO' : 'SANDBOX'
            ],
            'covenantCode' => $config->getConvenio(),
            'bankNumber' => '033',
            'clientNumber' => $config->getContaBancaria()->getCodigo(),
            'nsuCode' => $boleto->getNossoNumero(),
            'nsuDate' => (new \DateTime())->format('Y-m-d'),
            'documentKind' => 'DUPLICATA_MERCANTIL',
            'issueDate' => $boleto->getDataEmissao()->format('Y-m-d'),
            'dueDate' => $boleto->getDataVencimento()->format('Y-m-d'),
            'nominalValue' => (float) $boleto->getValorNominal(),
            'payer' => [
                'name' => mb_substr($pagador->getNome(), 0, 40),
                'documentType' => $tipoPessoa === 'F' ? 'CPF' : 'CNPJ',
                'documentNumber' => $documentoLimpo,
                'address' => $this->getEnderecoPagador($pagador),
                'city' => $this->getCidadePagador($pagador),
                'state' => $this->getEstadoPagador($pagador),
                'zipCode' => preg_replace('/\D/', '', $this->getCepPagador($pagador)),
            ],
        ];

        // Desconto
        if ($boleto->getTipoDesconto() !== Boletos::DESCONTO_ISENTO) {
            $payload['discountType'] = $boleto->getTipoDesconto();
            $payload['discountValue'] = (float) $boleto->getValorDesconto();
            if ($boleto->getDataDesconto()) {
                $payload['discountLimitDate'] = $boleto->getDataDesconto()->format('Y-m-d');
            }
        } else {
            $payload['discountType'] = 'ISENTO';
        }

        // Multa
        if ($boleto->getTipoMulta() !== Boletos::MULTA_ISENTO) {
            $payload['fineType'] = $boleto->getTipoMulta();
            $payload['fineValue'] = (float) $boleto->getValorMulta();
            if ($boleto->getDataMulta()) {
                $payload['fineDate'] = $boleto->getDataMulta()->format('Y-m-d');
            }
        } else {
            $payload['fineType'] = 'ISENTO';
        }

        // Juros
        if ($boleto->getTipoJuros() !== Boletos::JUROS_ISENTO) {
            $payload['interestType'] = $boleto->getTipoJuros();
            $payload['interestValue'] = (float) $boleto->getValorJurosDia();
        } else {
            $payload['interestType'] = 'ISENTO';
        }

        // Mensagens
        if ($boleto->getMensagemPagador()) {
            $payload['messages'] = $this->formatarMensagens($boleto->getMensagemPagador());
        }

        // Número do documento do cliente
        if ($boleto->getSeuNumero()) {
            $payload['documentNumber'] = $boleto->getSeuNumero();
        }

        return $payload;
    }

    /**
     * Atualiza status do boleto baseado na consulta
     */
    private function atualizarStatusPorConsulta(Boletos $boleto, array $data): void
    {
        $statusApi = strtoupper($data['status'] ?? '');

        switch ($statusApi) {
            case 'PAGO':
            case 'LIQUIDADO':
                $boleto->setStatus(Boletos::STATUS_PAGO);
                if (!empty($data['paymentDate'])) {
                    $boleto->setDataPagamento(new \DateTime($data['paymentDate']));
                }
                if (!empty($data['paidValue'])) {
                    $boleto->setValorPago(number_format((float) $data['paidValue'], 2, '.', ''));
                }
                break;

            case 'BAIXADO':
                $boleto->setStatus(Boletos::STATUS_BAIXADO);
                if (!$boleto->getDataBaixa()) {
                    $boleto->setDataBaixa(new \DateTime());
                }
                break;

            case 'PROTESTADO':
                $boleto->setStatus(Boletos::STATUS_PROTESTADO);
                break;

            case 'VENCIDO':
                // Só marca como vencido se realmente estiver vencido
                if ($boleto->isVencido()) {
                    $boleto->setStatus(Boletos::STATUS_VENCIDO);
                }
                break;
        }

        // Atualizar código de barras e linha digitável se retornados
        if (!empty($data['barCode']) && empty($boleto->getCodigoBarras())) {
            $boleto->setCodigoBarras($data['barCode']);
        }

        if (!empty($data['digitableLine']) && empty($boleto->getLinhaDigitavel())) {
            $boleto->setLinhaDigitavel($data['digitableLine']);
        }
    }

    /**
     * Extrai mensagem de erro da resposta da API
     */
    private function extrairMensagemErro(array $data): string
    {
        return $data['message']
            ?? $data['error_description']
            ?? $data['error']
            ?? $data['errors'][0]['message']
            ?? 'Erro desconhecido';
    }

    /**
     * Formata mensagens para o padrão da API (máximo 4 linhas de 40 caracteres)
     */
    private function formatarMensagens(string $mensagem): array
    {
        $linhas = [];
        $palavras = explode(' ', $mensagem);
        $linhaAtual = '';

        foreach ($palavras as $palavra) {
            if (strlen($linhaAtual . ' ' . $palavra) <= 40) {
                $linhaAtual = trim($linhaAtual . ' ' . $palavra);
            } else {
                if ($linhaAtual) {
                    $linhas[] = $linhaAtual;
                }
                $linhaAtual = mb_substr($palavra, 0, 40);

                if (count($linhas) >= 4) {
                    break;
                }
            }
        }

        if ($linhaAtual && count($linhas) < 4) {
            $linhas[] = $linhaAtual;
        }

        return array_slice($linhas, 0, 4);
    }

    // === MÉTODOS PARA OBTER DADOS DO PAGADOR ===

    /**
     * Obtém documento (CPF/CNPJ) do pagador
     */
    private function getDocumentoPagador(Pessoas $pagador): string
    {
        foreach ($pagador->getPessoasDocumentos() as $doc) {
            $tipo = strtoupper($doc->getTipoDocumento()?->getTipo() ?? '');
            if (in_array($tipo, ['CPF', 'CNPJ'])) {
                return $doc->getNumeroDocumento();
            }
        }

        // Fallback: documento não encontrado
        return '00000000000';
    }

    /**
     * Obtém endereço formatado do pagador
     */
    private function getEnderecoPagador(Pessoas $pagador): string
    {
        $endereco = $this->getEnderecoPrincipal($pagador);

        if (!$endereco) {
            return 'Endereco nao informado';
        }

        $logradouro = $endereco->getLogradouro();
        $rua = $logradouro?->getLogradouro() ?? '';
        $numero = $endereco->getEndNumero();
        $complemento = $endereco->getComplemento();

        $enderecoFormatado = $rua . ', ' . $numero;
        if ($complemento) {
            $enderecoFormatado .= ' - ' . $complemento;
        }

        return mb_substr($enderecoFormatado, 0, 40);
    }

    /**
     * Obtém cidade do pagador
     */
    private function getCidadePagador(Pessoas $pagador): string
    {
        $endereco = $this->getEnderecoPrincipal($pagador);

        if (!$endereco) {
            return 'Nao informada';
        }

        $cidade = $endereco->getLogradouro()?->getBairro()?->getCidade();

        return mb_substr($cidade?->getNome() ?? 'Nao informada', 0, 30);
    }

    /**
     * Obtém estado (UF) do pagador
     */
    private function getEstadoPagador(Pessoas $pagador): string
    {
        $endereco = $this->getEnderecoPrincipal($pagador);

        if (!$endereco) {
            return 'SP';
        }

        $estado = $endereco->getLogradouro()?->getBairro()?->getCidade()?->getEstado();

        return $estado?->getSigla() ?? 'SP';
    }

    /**
     * Obtém CEP do pagador
     */
    private function getCepPagador(Pessoas $pagador): string
    {
        $endereco = $this->getEnderecoPrincipal($pagador);

        if (!$endereco) {
            return '00000000';
        }

        return $endereco->getLogradouro()?->getCep() ?? '00000000';
    }

    /**
     * Obtém endereço principal do pagador
     */
    private function getEnderecoPrincipal(Pessoas $pagador): ?Enderecos
    {
        // Buscar endereço da pessoa
        $enderecos = $this->em->getRepository(Enderecos::class)->findBy(
            ['pessoa' => $pagador],
            ['id' => 'ASC']
        );

        return $enderecos[0] ?? null;
    }

    // ========================================================
    // MÉTODOS ADICIONAIS PARA O CRUD (Fase 2B)
    // ========================================================

    /**
     * Lista boletos com filtros
     *
     * @param array $filtros Filtros disponíveis:
     *   - status: string|array - Status do boleto
     *   - data_vencimento_inicio: \DateTimeInterface
     *   - data_vencimento_fim: \DateTimeInterface
     *   - pagador_id: int
     *   - configuracao_id: int
     *   - imovel_id: int
     *   - nosso_numero: string
     *   - seu_numero: string
     * @param int $limit
     * @param int $offset
     * @return array ['boletos' => [], 'total' => int]
     */
    public function listarBoletos(array $filtros = [], int $limit = 50, int $offset = 0): array
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('b', 'p', 'c', 'i')
            ->from(Boletos::class, 'b')
            ->leftJoin('b.pessoaPagador', 'p')
            ->leftJoin('b.configuracaoApi', 'c')
            ->leftJoin('b.imovel', 'i')
            ->orderBy('b.dataVencimento', 'DESC')
            ->addOrderBy('b.id', 'DESC');

        // Aplicar filtros
        if (!empty($filtros['status'])) {
            $status = is_array($filtros['status']) ? $filtros['status'] : [$filtros['status']];
            $qb->andWhere('b.status IN (:status)')
                ->setParameter('status', $status);
        }

        if (!empty($filtros['data_vencimento_inicio'])) {
            $qb->andWhere('b.dataVencimento >= :dataInicio')
                ->setParameter('dataInicio', $filtros['data_vencimento_inicio']);
        }

        if (!empty($filtros['data_vencimento_fim'])) {
            $qb->andWhere('b.dataVencimento <= :dataFim')
                ->setParameter('dataFim', $filtros['data_vencimento_fim']);
        }

        if (!empty($filtros['pagador_id'])) {
            $qb->andWhere('b.pessoaPagador = :pagadorId')
                ->setParameter('pagadorId', $filtros['pagador_id']);
        }

        if (!empty($filtros['configuracao_id'])) {
            $qb->andWhere('b.configuracaoApi = :configId')
                ->setParameter('configId', $filtros['configuracao_id']);
        }

        if (!empty($filtros['imovel_id'])) {
            $qb->andWhere('b.imovel = :imovelId')
                ->setParameter('imovelId', $filtros['imovel_id']);
        }

        if (!empty($filtros['nosso_numero'])) {
            $qb->andWhere('b.nossoNumero LIKE :nossoNumero')
                ->setParameter('nossoNumero', '%' . $filtros['nosso_numero'] . '%');
        }

        if (!empty($filtros['seu_numero'])) {
            $qb->andWhere('b.seuNumero LIKE :seuNumero')
                ->setParameter('seuNumero', '%' . $filtros['seu_numero'] . '%');
        }

        // Contar total
        $countQb = clone $qb;
        $countQb->select('COUNT(b.id)');
        $total = (int) $countQb->getQuery()->getSingleScalarResult();

        // Aplicar paginação
        $qb->setMaxResults($limit)
            ->setFirstResult($offset);

        $boletos = $qb->getQuery()->getResult();

        return [
            'boletos' => $boletos,
            'total' => $total
        ];
    }

    /**
     * Busca boleto por ID com dados enriquecidos
     */
    public function buscarPorId(int $id): ?array
    {
        $boleto = $this->boletosRepository->find($id);

        if (!$boleto) {
            return null;
        }

        // Buscar logs
        $logs = $this->em->getRepository(BoletosLogApi::class)->findBy(
            ['boleto' => $boleto],
            ['createdAt' => 'DESC']
        );

        // Dados do pagador
        $pagador = $boleto->getPessoaPagador();
        $documento = $this->getDocumentoPagador($pagador);
        $endereco = $this->getEnderecoPrincipal($pagador);

        return [
            'boleto' => $boleto,
            'logs' => $logs,
            'pagador' => [
                'id' => $pagador->getIdPessoa(),
                'nome' => $pagador->getNome(),
                'documento' => $documento,
                'documento_formatado' => $this->formatarDocumento($documento),
                'endereco' => $endereco ? $this->getEnderecoPagador($pagador) : null,
                'cidade' => $endereco ? $this->getCidadePagador($pagador) : null,
                'estado' => $endereco ? $this->getEstadoPagador($pagador) : null,
                'cep' => $endereco ? $this->getCepPagador($pagador) : null,
            ],
            'configuracao' => [
                'id' => $boleto->getConfiguracaoApi()->getId(),
                'banco' => $boleto->getConfiguracaoApi()->getBanco()->getNome(),
                'convenio' => $boleto->getConfiguracaoApi()->getConvenio(),
            ]
        ];
    }

    /**
     * Cria boleto a partir de array de dados (para o formulário)
     */
    public function criarBoletoFromArray(array $dados): Boletos
    {
        $config = $this->em->getRepository(ConfiguracoesApiBanco::class)->find($dados['configuracao_api_id']);
        $pagador = $this->em->getRepository(Pessoas::class)->find($dados['pessoa_pagador_id']);

        if (!$config) {
            throw new \InvalidArgumentException('Configuração de API não encontrada');
        }

        if (!$pagador) {
            throw new \InvalidArgumentException('Pagador não encontrado');
        }

        $boleto = new Boletos();

        $boleto->setConfiguracaoApi($config);
        $boleto->setPessoaPagador($pagador);
        $boleto->setNossoNumero($this->gerarNossoNumero($config));
        $boleto->setValorNominal(number_format((float) $dados['valor_nominal'], 2, '.', ''));
        $boleto->setDataVencimento($dados['data_vencimento']);
        $boleto->setDataEmissao(new \DateTime());
        $boleto->setStatus(Boletos::STATUS_PENDENTE);

        // Campos opcionais
        if (!empty($dados['seu_numero'])) {
            $boleto->setSeuNumero($dados['seu_numero']);
        }

        if (!empty($dados['imovel_id'])) {
            $imovel = $this->em->getRepository(\App\Entity\Imoveis::class)->find($dados['imovel_id']);
            if ($imovel) {
                $boleto->setImovel($imovel);
            }
        }

        if (!empty($dados['lancamento_financeiro_id'])) {
            $lancamento = $this->em->getRepository(LancamentosFinanceiros::class)->find($dados['lancamento_financeiro_id']);
            if ($lancamento) {
                $boleto->setLancamentoFinanceiro($lancamento);
            }
        }

        if (!empty($dados['data_limite_pagamento'])) {
            $boleto->setDataLimitePagamento($dados['data_limite_pagamento']);
        }

        if (!empty($dados['mensagem_pagador'])) {
            $boleto->setMensagemPagador($dados['mensagem_pagador']);
        }

        // Desconto
        $tipoDesconto = $dados['tipo_desconto'] ?? Boletos::DESCONTO_ISENTO;
        $boleto->setTipoDesconto($tipoDesconto);
        if ($tipoDesconto !== Boletos::DESCONTO_ISENTO) {
            $boleto->setValorDesconto(number_format((float) ($dados['valor_desconto'] ?? 0), 2, '.', ''));
            if (!empty($dados['data_desconto'])) {
                $boleto->setDataDesconto($dados['data_desconto']);
            }
        }

        // Juros
        $tipoJuros = $dados['tipo_juros'] ?? Boletos::JUROS_ISENTO;
        $boleto->setTipoJuros($tipoJuros);
        if ($tipoJuros !== Boletos::JUROS_ISENTO) {
            $boleto->setValorJurosDia(number_format((float) ($dados['valor_juros_dia'] ?? 0), 2, '.', ''));
        }

        // Multa
        $tipoMulta = $dados['tipo_multa'] ?? Boletos::MULTA_ISENTO;
        $boleto->setTipoMulta($tipoMulta);
        if ($tipoMulta !== Boletos::MULTA_ISENTO) {
            $boleto->setValorMulta(number_format((float) ($dados['valor_multa'] ?? 0), 2, '.', ''));
            if (!empty($dados['data_multa'])) {
                $boleto->setDataMulta($dados['data_multa']);
            }
        }

        $this->em->persist($boleto);
        $this->em->flush();

        $this->logger->info('[BoletoSantander] Boleto criado via formulário', [
            'id' => $boleto->getId(),
            'nosso_numero' => $boleto->getNossoNumero(),
            'valor' => $boleto->getValorNominal()
        ]);

        return $boleto;
    }

    /**
     * Registra múltiplos boletos por IDs
     */
    public function registrarLotePorIds(array $boletoIds): array
    {
        $boletos = $this->boletosRepository->findBy(['id' => $boletoIds]);

        $resultados = [
            'total' => count($boletos),
            'sucesso' => 0,
            'erro' => 0,
            'detalhes' => []
        ];

        foreach ($boletos as $boleto) {
            if ($boleto->getStatus() !== Boletos::STATUS_PENDENTE) {
                $resultados['detalhes'][] = [
                    'boleto_id' => $boleto->getId(),
                    'nosso_numero' => $boleto->getNossoNumero(),
                    'sucesso' => false,
                    'mensagem' => 'Boleto não está pendente para registro'
                ];
                $resultados['erro']++;
                continue;
            }

            $resultado = $this->registrarBoleto($boleto);
            $resultados['detalhes'][] = [
                'boleto_id' => $boleto->getId(),
                'nosso_numero' => $boleto->getNossoNumero(),
                'sucesso' => $resultado['sucesso'],
                'mensagem' => $resultado['mensagem']
            ];

            if ($resultado['sucesso']) {
                $resultados['sucesso']++;
            } else {
                $resultados['erro']++;
            }
        }

        return $resultados;
    }

    /**
     * Consulta status de múltiplos boletos por IDs
     */
    public function consultarLotePorIds(array $boletoIds): array
    {
        $boletos = $this->boletosRepository->findBy(['id' => $boletoIds]);

        $resultados = [
            'total' => count($boletos),
            'sucesso' => 0,
            'erro' => 0,
            'detalhes' => []
        ];

        foreach ($boletos as $boleto) {
            if (!$boleto->getIdTituloBanco()) {
                $resultados['detalhes'][] = [
                    'boleto_id' => $boleto->getId(),
                    'nosso_numero' => $boleto->getNossoNumero(),
                    'sucesso' => false,
                    'mensagem' => 'Boleto não está registrado no banco'
                ];
                $resultados['erro']++;
                continue;
            }

            $resultado = $this->consultarBoleto($boleto);
            $resultados['detalhes'][] = [
                'boleto_id' => $boleto->getId(),
                'nosso_numero' => $boleto->getNossoNumero(),
                'sucesso' => $resultado['sucesso'],
                'status' => $boleto->getStatus(),
                'mensagem' => $resultado['mensagem']
            ];

            if ($resultado['sucesso']) {
                $resultados['sucesso']++;
            } else {
                $resultados['erro']++;
            }
        }

        return $resultados;
    }

    /**
     * Retorna estatísticas de boletos para dashboard
     */
    public function getEstatisticas(?int $configId = null): array
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select(
            'COUNT(b.id) as total',
            "SUM(CASE WHEN b.status = 'PENDENTE' THEN 1 ELSE 0 END) as pendentes",
            "SUM(CASE WHEN b.status = 'REGISTRADO' THEN 1 ELSE 0 END) as registrados",
            "SUM(CASE WHEN b.status = 'PAGO' THEN 1 ELSE 0 END) as pagos",
            "SUM(CASE WHEN b.status = 'VENCIDO' THEN 1 ELSE 0 END) as vencidos",
            "SUM(CASE WHEN b.status = 'BAIXADO' THEN 1 ELSE 0 END) as baixados",
            "SUM(CASE WHEN b.status = 'ERRO' THEN 1 ELSE 0 END) as erros",
            "SUM(CASE WHEN b.status IN ('PENDENTE', 'REGISTRADO', 'VENCIDO') THEN b.valorNominal ELSE 0 END) as valor_total_aberto",
            "SUM(CASE WHEN b.status = 'PAGO' THEN b.valorPago ELSE 0 END) as valor_total_pago"
        )
        ->from(Boletos::class, 'b');

        if ($configId) {
            $qb->where('b.configuracaoApi = :configId')
                ->setParameter('configId', $configId);
        }

        $result = $qb->getQuery()->getSingleResult();

        return [
            'total' => (int) ($result['total'] ?? 0),
            'pendentes' => (int) ($result['pendentes'] ?? 0),
            'registrados' => (int) ($result['registrados'] ?? 0),
            'pagos' => (int) ($result['pagos'] ?? 0),
            'vencidos' => (int) ($result['vencidos'] ?? 0),
            'baixados' => (int) ($result['baixados'] ?? 0),
            'erros' => (int) ($result['erros'] ?? 0),
            'valor_total_aberto' => (float) ($result['valor_total_aberto'] ?? 0),
            'valor_total_pago' => (float) ($result['valor_total_pago'] ?? 0),
        ];
    }

    /**
     * Deleta boleto (apenas se PENDENTE)
     */
    public function deletarBoleto(int $id): array
    {
        $boleto = $this->boletosRepository->find($id);

        if (!$boleto) {
            return [
                'sucesso' => false,
                'mensagem' => 'Boleto não encontrado'
            ];
        }

        if ($boleto->getStatus() !== Boletos::STATUS_PENDENTE) {
            return [
                'sucesso' => false,
                'mensagem' => 'Apenas boletos pendentes podem ser excluídos'
            ];
        }

        $this->em->remove($boleto);
        $this->em->flush();

        $this->logger->info('[BoletoSantander] Boleto excluído', [
            'id' => $id,
            'nosso_numero' => $boleto->getNossoNumero()
        ]);

        return [
            'sucesso' => true,
            'mensagem' => 'Boleto excluído com sucesso'
        ];
    }

    /**
     * Formata documento (CPF/CNPJ)
     */
    private function formatarDocumento(string $documento): string
    {
        $documento = preg_replace('/\D/', '', $documento);

        if (strlen($documento) === 11) {
            return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $documento);
        } elseif (strlen($documento) === 14) {
            return preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $documento);
        }

        return $documento;
    }
}
