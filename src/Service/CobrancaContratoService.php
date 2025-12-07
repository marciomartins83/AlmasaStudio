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
            $boleto = $this->boletoService->criarBoleto([
                'configuracao_api_id' => $configApi->getId(),
                'pessoa_pagador_id' => $locatario->getId(),
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
     * Busca configuração de API padrão (primeira ativa).
     */
    private function getConfiguracaoApiPadrao(): ?\App\Entity\ConfiguracoesApiBanco
    {
        $configs = $this->configApiBancoRepo->findBy(['ativo' => true], ['id' => 'ASC'], 1);
        return $configs[0] ?? null;
    }

    /**
     * Monta mensagem para o boleto.
     */
    private function montarMensagemBoleto(ContratosCobrancas $cobranca): string
    {
        $contrato = $cobranca->getContrato();
        $imovel = $contrato->getImovel();

        return sprintf(
            "Aluguel ref. %s\nImovel: %s\nPeriodo: %s a %s",
            $cobranca->getCompetenciaFormatada(),
            $imovel ? $imovel->getCodigoInterno() : '-',
            $cobranca->getPeriodoInicio()->format('d/m/Y'),
            $cobranca->getPeriodoFim()->format('d/m/Y')
        );
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
