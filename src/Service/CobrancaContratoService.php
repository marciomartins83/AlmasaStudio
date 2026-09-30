<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\ContratosCobrancas;
use App\Entity\ContratosItensCobranca;
use App\Entity\ImoveisContratos;
use App\Repository\ContratosCobrancasRepository;
use App\Repository\ContratosItensCobrancaRepository;
use App\Repository\ImoveisContratosRepository;
use App\Repository\ConfiguracoesApiBancoRepository;
use App\Repository\LancamentosFinanceirosRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service para gerenciamento de cobranças de contratos.
 *
 * Funcionalidades:
 * - Cálculo de competência baseado no período de locação
 * - Cálculo de valores (aluguel + IPTU + condomínio + taxas)
 * - Criação de cobranças com verificação de duplicidade
 * - Geração e envio de boletos
 * - Processamento automático (rotina diária)
 */
class CobrancaContratoService
{
    public function __construct(
        private EntityManagerInterface $em,
        private BoletoSantanderService $boletoService,
        private EmailService $emailService,
        private ContratosCobrancasRepository $cobrancasRepo,
        private ContratosItensCobrancaRepository $itensRepo,
        private ImoveisContratosRepository $contratosRepo,
        private ConfiguracoesApiBancoRepository $configApiBancoRepo,
        private LancamentosFinanceirosRepository $lancamentosFinanceirosRepo,
        private LoggerInterface $logger,
        private string $projectDir
    ) {}

    /**
     * Calcula competência baseada no período de locação.
     *
     * Regra: Se dia atual < dia de vencimento, competência = mês atual
     *        Se dia atual >= dia de vencimento, competência = próximo mês
     *
     * @param ImoveisContratos $contrato Contrato de locação
     * @param \DateTime|null $dataReferencia Data de referência (default: hoje)
     * @return string Competência no formato 'YYYY-MM'
     */
    public function calcularCompetencia(
        ImoveisContratos $contrato,
        ?\DateTime $dataReferencia = null
    ): string {
        $dataRef = $dataReferencia ?? new \DateTime();
        $diaVencimento = $contrato->getDiaVencimento() ?? 10;
        $diaAtual = (int) $dataRef->format('d');

        if ($diaAtual < $diaVencimento) {
            return $dataRef->format('Y-m');
        }

        $proximoMes = clone $dataRef;
        $proximoMes->modify('+1 month');
        return $proximoMes->format('Y-m');
    }

    /**
     * Calcula o período de locação para uma competência.
     *
     * Ex: Competência 2025-12, dia vencimento 10
     * - Período início: 11/11/2025 (dia após vencimento anterior)
     * - Período fim: 10/12/2025 (dia do vencimento)
     * - Data vencimento: 10/12/2025
     *
     * @return array{periodo_inicio: \DateTime, periodo_fim: \DateTime, data_vencimento: \DateTime}
     */
    public function calcularPeriodo(ImoveisContratos $contrato, string $competencia): array
    {
        $diaVencimento = $contrato->getDiaVencimento() ?? 10;
        [$ano, $mes] = explode('-', $competencia);

        // Data de vencimento da competência
        $dataVencimento = $this->criarDataComDiaAjustado(
            (int) $ano,
            (int) $mes,
            $diaVencimento
        );

        // Período início: dia após vencimento do mês anterior
        $inicioMes = (int) $mes - 1;
        $inicioAno = (int) $ano;
        if ($inicioMes < 1) {
            $inicioMes = 12;
            $inicioAno--;
        }

        $periodoInicio = $this->criarDataComDiaAjustado($inicioAno, $inicioMes, $diaVencimento);
        $periodoInicio->modify('+1 day');

        // Período fim: data de vencimento
        $periodoFim = clone $dataVencimento;

        return [
            'periodo_inicio' => $periodoInicio,
            'periodo_fim' => $periodoFim,
            'data_vencimento' => $dataVencimento
        ];
    }

    /**
     * Cria DateTime ajustando dia para último dia do mês se necessário
     */
    private function criarDataComDiaAjustado(int $ano, int $mes, int $dia): \DateTime
    {
        $ultimoDia = cal_days_in_month(CAL_GREGORIAN, $mes, $ano);
        $diaFinal = min($dia, $ultimoDia);

        return new \DateTime(sprintf('%d-%02d-%02d', $ano, $mes, $diaFinal));
    }

    /**
     * Calcula valores da cobrança baseado nos itens configurados no contrato.
     *
     * @return array{
     *     aluguel: float,
     *     iptu: float,
     *     condominio: float,
     *     taxa_admin: float,
     *     outros: float,
     *     total: float,
     *     itens_detalhados: array
     * }
     */
    public function calcularValores(ImoveisContratos $contrato): array
    {
        $valores = [
            'aluguel' => 0,
            'iptu' => 0,
            'condominio' => 0,
            'taxa_admin' => 0,
            'outros' => 0,
            'total' => 0,
            'itens_detalhados' => []
        ];

        $valorAluguel = (float) $contrato->getValorContrato();

        // Buscar itens de cobrança do contrato
        $itens = $contrato->getItensCobrancaAtivos();

        // Se não há itens configurados, usar apenas o valor do contrato como aluguel
        if ($itens->isEmpty()) {
            $valores['aluguel'] = $valorAluguel;
            $valores['total'] = $valorAluguel;
            $valores['itens_detalhados'][] = [
                'tipo' => ContratosItensCobranca::TIPO_ALUGUEL,
                'descricao' => 'Aluguel',
                'valor' => $valorAluguel
            ];
            return $valores;
        }

        foreach ($itens as $item) {
            $valorItem = $item->calcularValorEfetivo($valorAluguel);

            switch ($item->getTipoItem()) {
                case ContratosItensCobranca::TIPO_ALUGUEL:
                    $valores['aluguel'] += $valorItem;
                    break;
                case ContratosItensCobranca::TIPO_IPTU:
                    $valores['iptu'] += $valorItem;
                    break;
                case ContratosItensCobranca::TIPO_CONDOMINIO:
                    $valores['condominio'] += $valorItem;
                    break;
                case ContratosItensCobranca::TIPO_TAXA_ADMIN:
                    $valores['taxa_admin'] += $valorItem;
                    break;
                default:
                    $valores['outros'] += $valorItem;
            }

            $valores['itens_detalhados'][] = [
                'tipo' => $item->getTipoItem(),
                'descricao' => $item->getDescricao(),
                'valor' => $valorItem
            ];
        }

        $valores['total'] = $valores['aluguel']
            + $valores['iptu']
            + $valores['condominio']
            + $valores['taxa_admin']
            + $valores['outros'];

        return $valores;
    }

    /**
     * Verifica se já existe cobrança para contrato/competência.
     */
    public function existeCobranca(int $contratoId, string $competencia): bool
    {
        return $this->cobrancasRepo->findByContratoCompetencia($contratoId, $competencia) !== null;
    }

    /**
     * Cria cobrança para um contrato/competência.
     *
     * @throws \RuntimeException Se já existir cobrança para a competência
     */
    public function criarCobranca(
        ImoveisContratos $contrato,
        string $competencia
    ): ContratosCobrancas {
        // Verificar duplicidade
        if ($this->existeCobranca($contrato->getId(), $competencia)) {
            throw new \RuntimeException(
                "Já existe cobrança para este contrato na competência {$competencia}"
            );
        }

        // Calcular período e valores
        $periodo = $this->calcularPeriodo($contrato, $competencia);
        $valores = $this->calcularValores($contrato);

        // Criar cobrança
        $cobranca = new ContratosCobrancas();
        $cobranca->setContrato($contrato);
        $cobranca->setCompetencia($competencia);
        $cobranca->setPeriodoInicio($periodo['periodo_inicio']);
        $cobranca->setPeriodoFim($periodo['periodo_fim']);
        $cobranca->setDataVencimento($periodo['data_vencimento']);
        $cobranca->setValorAluguel($valores['aluguel']);
        $cobranca->setValorIptu($valores['iptu']);
        $cobranca->setValorCondominio($valores['condominio']);
        $cobranca->setValorTaxaAdmin($valores['taxa_admin']);
        $cobranca->setValorOutros($valores['outros']);
        $cobranca->setValorTotal($valores['total']);
        $cobranca->setItensDetalhados($valores['itens_detalhados']);
        $cobranca->setStatus(ContratosCobrancas::STATUS_PENDENTE);

        $this->em->persist($cobranca);
        $this->em->flush();

        $this->logger->info('Cobrança criada', [
            'contrato_id' => $contrato->getId(),
            'competencia' => $competencia,
            'valor_total' => $valores['total']
        ]);

        return $cobranca;
    }

    /**
     * Gera boleto e envia email para uma cobrança.
     *
     * @return array{sucesso: bool, cobranca: ContratosCobrancas, boleto?: \App\Entity\Boletos, mensagem: string}
     */
    public function gerarEEnviarBoleto(
        ContratosCobrancas $cobranca,
        string $tipoEnvio = ContratosCobrancas::TIPO_ENVIO_MANUAL
    ): array {
        $contrato = $cobranca->getContrato();
        $locatario = $contrato->getPessoaLocatario();

        if (!$locatario) {
            return [
                'sucesso' => false,
                'cobranca' => $cobranca,
                'mensagem' => 'Contrato sem locatário definido'
            ];
        }

        // Buscar configuração de API padrão
        $configApi = $this->getConfiguracaoApiPadrao();
        if (!$configApi) {
            return [
                'sucesso' => false,
                'cobranca' => $cobranca,
                'mensagem' => 'Nenhuma configuração de API bancária ativa encontrada'
            ];
        }

        try {
            // 1. Criar boleto
            $boleto = $this->boletoService->criarBoletoFromArray([
                'configuracao_api_id' => $configApi->getId(),
                'pessoa_pagador_id' => $locatario->getIdpessoa(),
                'imovel_id' => $contrato->getImovel()->getId(),
                'valor_nominal' => $cobranca->getValorTotalFloat(),
                'data_vencimento' => $cobranca->getDataVencimento(),
                'mensagem_pagador' => $this->montarMensagemBoleto($cobranca),
            ]);

            // 2. Registrar boleto na API Santander
            $resultadoRegistro = $this->boletoService->registrarBoleto($boleto);

            if (!$resultadoRegistro['sucesso']) {
                return [
                    'sucesso' => false,
                    'cobranca' => $cobranca,
                    'mensagem' => 'Falha ao registrar boleto: ' . $resultadoRegistro['mensagem']
                ];
            }

            // 3. Atualizar cobrança com boleto
            $cobranca->setBoleto($boleto);
            $cobranca->setStatus(ContratosCobrancas::STATUS_BOLETO_GERADO);
            $canalEnvio = $contrato->getCanalEnvio();
            $cobranca->setCanalEnvio($canalEnvio);

            // Canais Administração/Correio: boleto fica pronto para impressão manual
            // (templates/boleto/_imprimir.html.twig), sem disparar e-mail.
            if ($canalEnvio !== ImoveisContratos::CANAL_EMAIL) {
                $cobranca->setStatus(ContratosCobrancas::STATUS_AGUARDANDO_ENTREGA);
                $cobranca->setTipoEnvio($tipoEnvio);

                $this->em->persist($cobranca);
                $this->em->flush();

                return [
                    'sucesso' => true,
                    'cobranca' => $cobranca,
                    'boleto' => $boleto,
                    'mensagem' => sprintf('Boleto gerado, aguardando entrega via %s', $cobranca->getCanalEnvioLabel()),
                ];
            }

            // 4. Gerar PDF do boleto (simulado - usa template de impressão)
            $pdfPath = $this->gerarPdfBoleto($boleto);

            // 5. Enviar email
            $resultadoEmail = $this->emailService->enviarBoletoLocatario($cobranca, $pdfPath);

            if ($resultadoEmail['sucesso']) {
                $cobranca->setStatus(ContratosCobrancas::STATUS_ENVIADO);
                $cobranca->setTipoEnvio($tipoEnvio);
                $cobranca->setEnviadoEm(new \DateTime());
                $cobranca->setEmailDestino($resultadoEmail['email'] ?? null);

                // Se foi envio manual, bloquear rotina automática
                if ($tipoEnvio === ContratosCobrancas::TIPO_ENVIO_MANUAL) {
                    $cobranca->setBloqueadoRotinaAuto(true);
                }

                $mensagem = 'Boleto gerado e enviado com sucesso';
            } else {
                $mensagem = 'Boleto gerado, mas falha no envio: ' . ($resultadoEmail['erro'] ?? 'erro desconhecido');
            }

            $this->em->persist($cobranca);
            $this->em->flush();

            // Limpar PDF temporário
            if (file_exists($pdfPath)) {
                unlink($pdfPath);
            }

            return [
                'sucesso' => $resultadoEmail['sucesso'],
                'cobranca' => $cobranca,
                'boleto' => $boleto,
                'mensagem' => $mensagem
            ];

        } catch (\Exception $e) {
            $this->logger->error('Erro ao gerar/enviar boleto', [
                'cobranca_id' => $cobranca->getId(),
                'erro' => $e->getMessage()
            ]);

            return [
                'sucesso' => false,
                'cobranca' => $cobranca,
                'mensagem' => 'Erro: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Processa envio automático de boletos (chamado pelo Command/Cron).
     *
     * @return array{sucesso: int, falha: int, ignorados: int, detalhes: array}
     */
    public function processarEnvioAutomatico(): array
    {
        $resultados = [
            'sucesso' => 0,
            'falha' => 0,
            'ignorados' => 0,
            'detalhes' => []
        ];

        $hoje = new \DateTime();

        // Buscar contratos ativos com envio automático
        $contratos = $this->contratosRepo->findContratosParaEnvioAutomatico();

        $this->logger->info('Iniciando envio automático', [
            'contratos_encontrados' => count($contratos)
        ]);

        foreach ($contratos as $contrato) {
            try {
                $competencia = $this->calcularCompetencia($contrato);
                $diasAntecedencia = $contrato->getDiasAntecedenciaBoleto();

                // Calcular data de vencimento
                $periodo = $this->calcularPeriodo($contrato, $competencia);
                $dataVencimento = $periodo['data_vencimento'];

                // Verificar se está no período de antecedência
                $diasAteVencimento = (int) $hoje->diff($dataVencimento)->format('%r%a');

                if ($diasAteVencimento > $diasAntecedencia || $diasAteVencimento < 0) {
                    $resultados['ignorados']++;
                    $resultados['detalhes'][] = [
                        'contrato_id' => $contrato->getId(),
                        'status' => 'ignorado',
                        'motivo' => "Fora do período de antecedência ({$diasAteVencimento} dias)"
                    ];
                    continue;
                }

                // Verificar se já existe cobrança
                $cobrancaExistente = $this->cobrancasRepo->findByContratoCompetencia(
                    $contrato->getId(),
                    $competencia
                );

                if ($cobrancaExistente) {
                    // Se já foi enviado ou está bloqueado, ignorar
                    if ($cobrancaExistente->isEnviado() || $cobrancaExistente->isBloqueadoRotinaAuto()) {
                        $resultados['ignorados']++;
                        $resultados['detalhes'][] = [
                            'contrato_id' => $contrato->getId(),
                            'status' => 'ignorado',
                            'motivo' => 'Cobrança já enviada ou bloqueada'
                        ];
                        continue;
                    }
                    $cobranca = $cobrancaExistente;
                } else {
                    // Criar nova cobrança
                    $cobranca = $this->criarCobranca($contrato, $competencia);
                }

                // Bloqueio de inadimplente: rotina automática não manda boleto novo
                // pra quem já tem lançamento anterior vencido e não pago (senão o
                // inquilino paga o mês atual e esquece o atrasado). Envio manual não
                // é afetado por essa regra — só a rotina automática.
                $locatario = $contrato->getPessoaLocatario();
                if ($locatario && $this->lancamentosFinanceirosRepo->possuiLancamentoEmAbertoAntesDe($locatario->getIdpessoa(), $dataVencimento)) {
                    $resultados['ignorados']++;
                    $resultados['detalhes'][] = [
                        'contrato_id' => $contrato->getId(),
                        'status' => 'ignorado',
                        'motivo' => 'Inquilino inadimplente (lançamento anterior em aberto)'
                    ];
                    continue;
                }

                // Gerar e enviar
                $resultado = $this->gerarEEnviarBoleto(
                    $cobranca,
                    ContratosCobrancas::TIPO_ENVIO_AUTOMATICO
                );

                if ($resultado['sucesso']) {
                    $resultados['sucesso']++;
                    $resultados['detalhes'][] = [
                        'contrato_id' => $contrato->getId(),
                        'cobranca_id' => $cobranca->getId(),
                        'status' => 'sucesso',
                        'mensagem' => $resultado['mensagem']
                    ];
                } else {
                    $resultados['falha']++;
                    $resultados['detalhes'][] = [
                        'contrato_id' => $contrato->getId(),
                        'cobranca_id' => $cobranca->getId(),
                        'status' => 'falha',
                        'mensagem' => $resultado['mensagem']
                    ];
                }

            } catch (\Exception $e) {
                $this->logger->error('Erro no envio automático', [
                    'contrato_id' => $contrato->getId(),
                    'erro' => $e->getMessage()
                ]);

                $resultados['falha']++;
                $resultados['detalhes'][] = [
                    'contrato_id' => $contrato->getId(),
                    'status' => 'erro',
                    'mensagem' => $e->getMessage()
                ];
            }
        }

        $this->logger->info('Envio automático concluído', $resultados);

        return $resultados;
    }

    /**
     * Busca cobranças pendentes para uma data de vencimento.
     *
     * @return ContratosCobrancas[]
     */
    public function buscarCobrancasPendentes(
        \DateTime $dataVencimento,
        bool $incluirAutomaticos = false
    ): array {
        return $this->cobrancasRepo->findPendentesPorVencimento(
            $dataVencimento,
            $incluirAutomaticos
        );
    }

    /**
     * Busca cobranças com filtros para listagem.
     *
     * @return array{cobrancas: ContratosCobrancas[], total: int}
     */
    public function listarCobrancas(
        array $filtros = [],
        int $limit = 20,
        int $offset = 0
    ): array {
        return $this->cobrancasRepo->findByFiltros($filtros, $limit, $offset);
    }

    /**
     * Busca cobrança por ID com dados relacionados.
     */
    public function buscarPorId(int $id): ?ContratosCobrancas
    {
        return $this->cobrancasRepo->find($id);
    }

    /**
     * Retorna estatísticas de cobranças.
     */
    public function getEstatisticas(?\DateTime $dataVencimento = null): array
    {
        return $this->cobrancasRepo->getEstatisticas($dataVencimento);
    }

    /**
     * Cancela uma cobrança.
     */
    public function cancelarCobranca(ContratosCobrancas $cobranca): array
    {
        if (!$cobranca->podeCancelar()) {
            return [
                'sucesso' => false,
                'mensagem' => 'Cobrança não pode ser cancelada no status atual'
            ];
        }

        $cobranca->setStatus(ContratosCobrancas::STATUS_CANCELADO);
        $this->em->persist($cobranca);
        $this->em->flush();

        return [
            'sucesso' => true,
            'mensagem' => 'Cobrança cancelada com sucesso'
        ];
    }

    /**
     * Marca uma cobrança com canal Administração/Correio como entregue manualmente
     * (impresso e entregue em mãos ou postado).
     */
    public function marcarEntregue(ContratosCobrancas $cobranca): array
    {
        if (!$cobranca->podeMarcarEntregue()) {
            return [
                'sucesso' => false,
                'mensagem' => 'Cobrança não está aguardando entrega'
            ];
        }

        $cobranca->setStatus(ContratosCobrancas::STATUS_ENVIADO);
        $cobranca->setEnviadoEm(new \DateTime());
        $this->em->persist($cobranca);
        $this->em->flush();

        return [
            'sucesso' => true,
            'mensagem' => 'Cobrança marcada como entregue'
        ];
    }

    /**
     * Verifica no banco o pagamento do boleto de UMA cobrança (consulta sob demanda)
     * e reflete o pagamento na cobrança (status PAGO) quando confirmado.
     *
     * @return array{sucesso:bool, mensagem:string}
     */
    public function verificarPagamento(ContratosCobrancas $cobranca): array
    {
        $boleto = $cobranca->getBoleto();
        if (!$boleto) {
            return ['sucesso' => false, 'mensagem' => 'Esta cobrança ainda não tem boleto gerado.'];
        }

        $r = $this->boletoService->consultarBoleto($boleto);

        if ($boleto->isPago() && $cobranca->getStatus() !== ContratosCobrancas::STATUS_PAGO) {
            $cobranca->setStatus(ContratosCobrancas::STATUS_PAGO);
            $this->em->persist($cobranca);
            $this->em->flush();
        }

        $sucesso = (bool) ($r['sucesso'] ?? false);
        return [
            'sucesso' => $sucesso,
            'mensagem' => $sucesso
                ? ('Status do boleto: ' . $boleto->getStatusLabel())
                : ($r['mensagem'] ?? 'Falha na consulta do boleto'),
        ];
    }

    /**
     * Emite um boleto AVULSO (fora de contrato) para qualquer pagador e envia por
     * e-mail. Um boleto avulso pode ter vários itens (ex.: "pedreiro", material...);
     * o valor é a soma e a composição vai itemizada na mensagem.
     *
     * $dados: pagador_id, config_id (opcional=padrão), vencimento (Y-m-d),
     *         imovel_id (opcional), itens[]{descricao,valor}
     *
     * @return array{sucesso:bool, mensagem:string, boleto_id?:int}
     */
    public function emitirBoletoAvulso(array $dados): array
    {
        $pagadorId = (int) ($dados['pagador_id'] ?? 0);
        if ($pagadorId <= 0) {
            return ['sucesso' => false, 'mensagem' => 'Selecione o pagador do boleto.'];
        }

        $total = 0.0;
        $linhas = [];
        foreach (($dados['itens'] ?? []) as $item) {
            $valor = (float) ($item['valor'] ?? 0);
            if ($valor <= 0) {
                continue;
            }
            $descricao = trim((string) ($item['descricao'] ?? '')) ?: 'Item';
            $total += $valor;
            $linhas[] = sprintf('%s: R$ %s', $descricao, number_format($valor, 2, ',', '.'));
        }
        if ($total <= 0) {
            return ['sucesso' => false, 'mensagem' => 'Informe ao menos um item com valor.'];
        }

        $vencStr = $dados['vencimento'] ?? null;
        if (!$vencStr) {
            return ['sucesso' => false, 'mensagem' => 'Informe o vencimento.'];
        }
        try {
            $vencimento = new \DateTime($vencStr);
        } catch (\Exception $e) {
            return ['sucesso' => false, 'mensagem' => 'Vencimento inválido.'];
        }

        $configId = (int) ($dados['config_id'] ?? 0);
        $config = $configId > 0 ? $this->configApiBancoRepo->find($configId) : $this->getConfiguracaoApiPadrao();
        if (!$config) {
            return ['sucesso' => false, 'mensagem' => 'Nenhuma configuração de API bancária ativa encontrada.'];
        }

        try {
            $boleto = $this->boletoService->criarBoletoFromArray([
                'configuracao_api_id' => $config->getId(),
                'pessoa_pagador_id' => $pagadorId,
                'imovel_id' => $dados['imovel_id'] ?? null,
                'valor_nominal' => $total,
                'data_vencimento' => $vencimento,
                'mensagem_pagador' => implode("\n", array_slice($linhas, 0, 10)),
            ]);

            $registro = $this->boletoService->registrarBoleto($boleto);
            if (!$registro['sucesso']) {
                return ['sucesso' => false, 'mensagem' => 'Falha ao registrar boleto: ' . ($registro['mensagem'] ?? '')];
            }

            $pdfPath = $this->gerarPdfBoleto($boleto);
            $email = $this->emailService->enviarBoletoAvulso($boleto, $pdfPath);
            if (file_exists($pdfPath)) {
                unlink($pdfPath);
            }

            return [
                'sucesso' => true,
                'mensagem' => ($email['sucesso'] ?? false)
                    ? 'Boleto avulso emitido e enviado por e-mail.'
                    : ('Boleto avulso emitido; falha no envio de e-mail: ' . ($email['erro'] ?? 'erro desconhecido')),
                'boleto_id' => $boleto->getId(),
            ];
        } catch (\Exception $e) {
            $this->logger->error('Erro ao emitir boleto avulso', ['erro' => $e->getMessage()]);
            return ['sucesso' => false, 'mensagem' => 'Erro: ' . $e->getMessage()];
        }
    }

    /**
     * Busca configuração de API padrão (primeira ativa).
     */
    private function getConfiguracaoApiPadrao(): ?\App\Entity\ConfiguracoesApiBanco
    {
        $configs = $this->configApiBancoRepo->findBy(['ativo' => true], ['id' => 'ASC'], 1);
        return $configs[0] ?? null;
    }

    /**
     * Monta a mensagem do boleto ITEMIZADA (aluguel, agua, luz, IPTU, condominio...).
     * Um boleto tem um valor unico, mas a composicao vai listada na mensagem para o
     * pagador enxergar o que esta sendo cobrado.
     */
    private function montarMensagemBoleto(ContratosCobrancas $cobranca): string
    {
        $contrato = $cobranca->getContrato();
        $imovel = $contrato->getImovel();

        $linhas = [
            sprintf(
                'Ref. %s - Imovel: %s',
                $cobranca->getCompetenciaFormatada(),
                $imovel ? $imovel->getCodigoInterno() : '-'
            ),
        ];

        $itens = $cobranca->getItensDetalhados() ?: [];
        foreach ($itens as $item) {
            $descricao = $item['descricao'] ?? ($item['tipo'] ?? 'Item');
            $valor = (float) ($item['valor'] ?? 0);
            $linhas[] = sprintf('%s: R$ %s', $descricao, number_format($valor, 2, ',', '.'));
        }

        // Sem itens detalhados: mantem o periodo como referencia.
        if (count($linhas) === 1) {
            $linhas[] = sprintf(
                'Periodo: %s a %s',
                $cobranca->getPeriodoInicio()->format('d/m/Y'),
                $cobranca->getPeriodoFim()->format('d/m/Y')
            );
        }

        return implode("\n", $linhas);
    }

    /**
     * Gera cobrancas (RASCUNHO, status PENDENTE) em lote para um periodo — SEM enviar.
     * O usuario revisa a relacao (relatorio de conferencia) e so depois emite.
     *
     * Dois modos:
     *  - Por competencia: $opts['competencia'] = 'YYYY-MM' (todos os contratos elegiveis).
     *  - Por intervalo de vencimento: $opts['vencimento_inicio'] e $opts['vencimento_fim']
     *    (DateTime) — inclui o contrato cujo vencimento da competencia cair no intervalo.
     *
     * @return array{criadas:int, existentes:int, erros:array, competencias:array}
     */
    public function gerarCobrancasDoPeriodo(array $opts): array
    {
        $res = ['criadas' => 0, 'existentes' => 0, 'erros' => [], 'competencias' => []];

        $vencIni = $opts['vencimento_inicio'] ?? null;
        $vencFim = $opts['vencimento_fim'] ?? null;

        if (!empty($opts['competencia'])) {
            $competencias = [$opts['competencia']];
        } elseif ($vencIni instanceof \DateTimeInterface && $vencFim instanceof \DateTimeInterface) {
            $competencias = $this->competenciasEntre($vencIni, $vencFim);
        } else {
            return ['criadas' => 0, 'existentes' => 0,
                    'erros' => ['Informe uma competencia ou um intervalo de vencimento.'],
                    'competencias' => []];
        }
        $res['competencias'] = $competencias;

        $contratos = $this->contratosRepo->findContratosParaEnvioManual();

        foreach ($competencias as $competencia) {
            foreach ($contratos as $contrato) {
                try {
                    $vencimento = $this->calcularPeriodo($contrato, $competencia)['data_vencimento'];
                    // Modo intervalo: so entra quem vence dentro do intervalo pedido.
                    if ($vencIni && $vencFim) {
                        if ($vencimento < $vencIni || $vencimento > $vencFim) {
                            continue;
                        }
                    }
                    if ($this->existeCobranca($contrato->getId(), $competencia)) {
                        $res['existentes']++;
                        continue;
                    }
                    $this->criarCobranca($contrato, $competencia);
                    $res['criadas']++;
                } catch (\Exception $e) {
                    $res['erros'][] = sprintf('Contrato %d: %s', $contrato->getId(), $e->getMessage());
                }
            }
        }

        return $res;
    }

    /**
     * Lista de competencias 'YYYY-MM' tocadas por um intervalo de datas.
     *
     * @return string[]
     */
    private function competenciasEntre(\DateTimeInterface $ini, \DateTimeInterface $fim): array
    {
        $comps = [];
        $cursor = new \DateTime($ini->format('Y-m-01'));
        $limite = new \DateTime($fim->format('Y-m-01'));
        while ($cursor <= $limite) {
            $comps[] = $cursor->format('Y-m');
            $cursor->modify('+1 month');
        }
        return $comps;
    }

    /**
     * Edita os ITENS de uma cobranca ainda PENDENTE (antes de gerar o boleto) e
     * recalcula os baldes de valor + total. Cada item = {tipo, descricao, valor}.
     * Permite incluir/remover/alterar linhas (aluguel, agua, luz, IPTU, condominio,
     * ou uma linha avulsa como "pedreiro").
     *
     * @param array<int,array{tipo?:string,descricao?:string,valor?:float|string}> $itens
     * @return array{sucesso:bool, mensagem:string}
     */
    public function atualizarItensCobranca(ContratosCobrancas $cobranca, array $itens): array
    {
        if ($cobranca->getStatus() !== ContratosCobrancas::STATUS_PENDENTE) {
            return ['sucesso' => false,
                    'mensagem' => 'So e possivel editar cobrancas pendentes (antes de gerar o boleto).'];
        }

        $baldes = ['aluguel' => 0.0, 'iptu' => 0.0, 'condominio' => 0.0, 'taxa_admin' => 0.0, 'outros' => 0.0];
        $detalhados = [];

        foreach ($itens as $item) {
            $tipo = (string) ($item['tipo'] ?? ContratosItensCobranca::TIPO_OUTROS);
            $valor = (float) str_replace(['.', ','], ['', '.'], (string) ($item['valor'] ?? 0));
            // aceita valor ja numerico (float vindo do JSON)
            if (is_numeric($item['valor'] ?? null)) {
                $valor = (float) $item['valor'];
            }
            if ($valor <= 0) {
                continue;
            }
            $descricao = trim((string) ($item['descricao'] ?? '')) ?:
                (ContratosItensCobranca::getTiposDisponiveis()[$tipo] ?? 'Item');

            switch ($tipo) {
                case ContratosItensCobranca::TIPO_ALUGUEL: $baldes['aluguel'] += $valor; break;
                case ContratosItensCobranca::TIPO_IPTU: $baldes['iptu'] += $valor; break;
                case ContratosItensCobranca::TIPO_CONDOMINIO: $baldes['condominio'] += $valor; break;
                case ContratosItensCobranca::TIPO_TAXA_ADMIN: $baldes['taxa_admin'] += $valor; break;
                default: $baldes['outros'] += $valor;
            }
            $detalhados[] = ['tipo' => $tipo, 'descricao' => $descricao, 'valor' => $valor];
        }

        if (empty($detalhados)) {
            return ['sucesso' => false, 'mensagem' => 'Informe ao menos um item com valor.'];
        }

        $total = array_sum($baldes);
        $cobranca->setValorAluguel($baldes['aluguel']);
        $cobranca->setValorIptu($baldes['iptu']);
        $cobranca->setValorCondominio($baldes['condominio']);
        $cobranca->setValorTaxaAdmin($baldes['taxa_admin']);
        $cobranca->setValorOutros($baldes['outros']);
        $cobranca->setValorTotal($total);
        $cobranca->setItensDetalhados($detalhados);

        $this->em->persist($cobranca);
        $this->em->flush();

        return ['sucesso' => true, 'mensagem' => 'Itens atualizados.'];
    }

    /**
     * Gera PDF do boleto (temporário).
     *
     * Nota: Em produção, usar biblioteca de PDF (TCPDF, DomPDF, etc.)
     * Por ora, apenas cria arquivo vazio como placeholder.
     */
    private function gerarPdfBoleto(\App\Entity\Boletos $boleto): string
    {
        $tempDir = $this->projectDir . '/var/temp';
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0777, true);
        }

        $pdfPath = $tempDir . '/boleto_' . $boleto->getId() . '_' . time() . '.pdf';

        // TODO: Implementar geração real de PDF
        // Por enquanto, criar arquivo placeholder
        file_put_contents($pdfPath, '%PDF-1.4 placeholder');

        return $pdfPath;
    }
}
