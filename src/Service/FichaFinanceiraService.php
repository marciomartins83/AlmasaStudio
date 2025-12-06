<?php

namespace App\Service;

use App\Entity\LancamentosFinanceiros;
use App\Entity\BaixasFinanceiras;
use App\Entity\AcordosFinanceiros;
use App\Entity\ImoveisContratos;
use App\Repository\LancamentosFinanceirosRepository;
use App\Repository\BaixasFinanceirasRepository;
use App\Repository\AcordosFinanceirosRepository;
use App\Repository\ImoveisContratosRepository;
use App\Repository\PessoaRepository;
use App\Repository\ContasBancariasRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * FichaFinanceiraService - Fat Service
 *
 * Contém TODA a lógica de negócio do módulo de Ficha Financeira / Contas a Receber
 *
 * Responsabilidades:
 * - Gerenciamento de transações
 * - Validações de negócio
 * - Operações de persistência (persist, flush, remove)
 * - Lançamentos financeiros (criação, atualização, geração automática)
 * - Baixas de pagamentos
 * - Acordos de parcelamento
 * - Estatísticas e relatórios
 */
class FichaFinanceiraService
{
    public function __construct(
        private EntityManagerInterface $em,
        private LancamentosFinanceirosRepository $lancamentoRepo,
        private BaixasFinanceirasRepository $baixaRepo,
        private AcordosFinanceirosRepository $acordoRepo,
        private ImoveisContratosRepository $contratoRepo,
        private PessoaRepository $pessoaRepo,
        private ContasBancariasRepository $contaBancariaRepo,
        private LoggerInterface $logger
    ) {}

    /**
     * Lista lançamentos com filtros
     *
     * @param array $filtros
     * @return array
     */
    public function listarLancamentos(array $filtros = []): array
    {
        $lancamentos = $this->lancamentoRepo->findByFiltros($filtros);

        return array_map(fn($l) => $this->enriquecerLancamento($l), $lancamentos);
    }

    /**
     * Busca ficha financeira de um inquilino
     *
     * @param int $inquilinoId
     * @param int|null $ano
     * @return array
     */
    public function buscarFichaFinanceira(int $inquilinoId, ?int $ano = null): array
    {
        $lancamentos = $this->lancamentoRepo->findFichaFinanceira($inquilinoId, $ano);
        $totais = $this->lancamentoRepo->calcularTotaisInquilino($inquilinoId);
        $inquilino = $this->pessoaRepo->find($inquilinoId);

        return [
            'inquilino' => $inquilino ? [
                'id' => $inquilino->getIdpessoa(),
                'nome' => $inquilino->getNome(),
            ] : null,
            'lancamentos' => array_map(fn($l) => $this->enriquecerLancamento($l), $lancamentos),
            'totais' => $totais,
        ];
    }

    /**
     * Busca lançamentos em aberto de um inquilino
     *
     * @param int $inquilinoId
     * @return array
     */
    public function buscarAbertosInquilino(int $inquilinoId): array
    {
        $lancamentos = $this->lancamentoRepo->findAbertosInquilino($inquilinoId);

        return array_map(fn($l) => $this->enriquecerLancamento($l), $lancamentos);
    }

    /**
     * Busca lançamento por ID
     *
     * @param int $id
     * @return array|null
     */
    public function buscarLancamentoPorId(int $id): ?array
    {
        $lancamento = $this->lancamentoRepo->find($id);

        if (!$lancamento) {
            return null;
        }

        return $this->enriquecerLancamento($lancamento);
    }

    /**
     * Busca entidade lançamento por ID
     *
     * @param int $id
     * @return LancamentosFinanceiros|null
     */
    public function buscarLancamentoEntidade(int $id): ?LancamentosFinanceiros
    {
        return $this->lancamentoRepo->find($id);
    }

    /**
     * Cria novo lançamento manual
     *
     * @param array $dados
     * @return LancamentosFinanceiros
     * @throws \Exception
     */
    public function criarLancamento(array $dados): LancamentosFinanceiros
    {
        $this->em->beginTransaction();

        try {
            $lancamento = new LancamentosFinanceiros();
            $this->preencherLancamento($lancamento, $dados);
            $lancamento->calcularTotal();

            $this->em->persist($lancamento);
            $this->em->flush();
            $this->em->commit();

            $this->logger->info('Lançamento criado', ['id' => $lancamento->getId()]);

            return $lancamento;

        } catch (\Exception $e) {
            $this->em->rollback();
            $this->logger->error('Erro ao criar lançamento', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Atualiza lançamento
     *
     * @param int $id
     * @param array $dados
     * @return LancamentosFinanceiros
     * @throws \Exception
     */
    public function atualizarLancamento(int $id, array $dados): LancamentosFinanceiros
    {
        $this->em->beginTransaction();

        try {
            $lancamento = $this->lancamentoRepo->find($id);

            if (!$lancamento) {
                throw new \RuntimeException('Lançamento não encontrado');
            }

            // Não permite editar lançamentos pagos
            if ($lancamento->isPago()) {
                throw new \RuntimeException('Não é possível editar um lançamento já pago');
            }

            $this->preencherLancamento($lancamento, $dados);
            $lancamento->calcularTotal();

            $this->em->flush();
            $this->em->commit();

            $this->logger->info('Lançamento atualizado', ['id' => $id]);

            return $lancamento;

        } catch (\Exception $e) {
            $this->em->rollback();
            $this->logger->error('Erro ao atualizar lançamento', ['id' => $id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Realiza baixa (pagamento)
     *
     * @param int $lancamentoId
     * @param array $dados
     * @return BaixasFinanceiras
     * @throws \Exception
     */
    public function realizarBaixa(int $lancamentoId, array $dados): BaixasFinanceiras
    {
        $this->em->beginTransaction();

        try {
            $lancamento = $this->lancamentoRepo->find($lancamentoId);

            if (!$lancamento) {
                throw new \RuntimeException('Lançamento não encontrado');
            }

            if ($lancamento->isPago()) {
                throw new \RuntimeException('Este lançamento já está totalmente pago');
            }

            // Cria a baixa
            $baixa = new BaixasFinanceiras();
            $baixa->setLancamento($lancamento);
            $baixa->setDataPagamento(new \DateTime($dados['dataPagamento']));
            $baixa->setValorPago($dados['valorPago']);
            $baixa->setValorMultaPaga($dados['valorMulta'] ?? '0.00');
            $baixa->setValorJurosPago($dados['valorJuros'] ?? '0.00');
            $baixa->setValorDesconto($dados['valorDesconto'] ?? '0.00');
            $baixa->setFormaPagamento($dados['formaPagamento'] ?? 'boleto');
            $baixa->setNumeroDocumento($dados['numeroDocumento'] ?? null);
            $baixa->setNumeroAutenticacao($dados['numeroAutenticacao'] ?? null);
            $baixa->setTipoBaixa($dados['tipoBaixa'] ?? 'normal');
            $baixa->setObservacoes($dados['observacoes'] ?? null);

            if (!empty($dados['contaBancaria'])) {
                $contaBancaria = $this->contaBancariaRepo->find($dados['contaBancaria']);
                $baixa->setContaBancaria($contaBancaria);
            }

            $baixa->calcularTotal();

            // Atualiza o lançamento
            $valorPagoAtual = (float) $lancamento->getValorPago();
            $novoValorPago = $valorPagoAtual + (float) $baixa->getValorTotalPago();
            $lancamento->setValorPago(number_format($novoValorPago, 2, '.', ''));
            $lancamento->calcularSaldo();

            // Atualiza situação
            if ((float) $lancamento->getValorSaldo() <= 0) {
                $lancamento->setSituacao('pago');
            } elseif ($novoValorPago > 0) {
                $lancamento->setSituacao('parcial');
            }

            $this->em->persist($baixa);
            $this->em->flush();
            $this->em->commit();

            $this->logger->info('Baixa realizada', [
                'lancamento' => $lancamentoId,
                'baixa' => $baixa->getId(),
                'valor' => $baixa->getValorTotalPago()
            ]);

            return $baixa;

        } catch (\Exception $e) {
            $this->em->rollback();
            $this->logger->error('Erro ao realizar baixa', ['lancamento' => $lancamentoId, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Estorna baixa
     *
     * @param int $baixaId
     * @param string $motivo
     * @return BaixasFinanceiras
     * @throws \Exception
     */
    public function estornarBaixa(int $baixaId, string $motivo): BaixasFinanceiras
    {
        $this->em->beginTransaction();

        try {
            $baixa = $this->baixaRepo->find($baixaId);

            if (!$baixa) {
                throw new \RuntimeException('Baixa não encontrada');
            }

            if ($baixa->isEstornada()) {
                throw new \RuntimeException('Esta baixa já foi estornada');
            }

            $lancamento = $baixa->getLancamento();

            // Reverte o valor no lançamento
            $valorPagoAtual = (float) $lancamento->getValorPago();
            $novoValorPago = $valorPagoAtual - (float) $baixa->getValorTotalPago();
            $lancamento->setValorPago(number_format(max(0, $novoValorPago), 2, '.', ''));
            $lancamento->calcularSaldo();

            // Atualiza situação do lançamento
            if ($novoValorPago <= 0) {
                $lancamento->setSituacao($lancamento->isEmAtraso() ? 'atrasado' : 'aberto');
            } else {
                $lancamento->setSituacao('parcial');
            }

            // Marca a baixa como estornada
            $baixa->setEstornada(true);
            $baixa->setDataEstorno(new \DateTime());
            $baixa->setMotivoEstorno($motivo);

            $this->em->flush();
            $this->em->commit();

            $this->logger->info('Baixa estornada', ['baixa' => $baixaId, 'motivo' => $motivo]);

            return $baixa;

        } catch (\Exception $e) {
            $this->em->rollback();
            $this->logger->error('Erro ao estornar baixa', ['baixa' => $baixaId, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Gera lançamentos automáticos para uma competência
     *
     * @param \DateTime $competencia
     * @return array
     * @throws \Exception
     */
    public function gerarLancamentosAutomaticos(\DateTime $competencia): array
    {
        $this->em->beginTransaction();

        try {
            $contratosParaGerar = $this->lancamentoRepo->getLancamentosParaGerar($competencia);
            $lancamentosCriados = [];

            foreach ($contratosParaGerar as $dados) {
                $contrato = $this->contratoRepo->find($dados['contrato_id']);

                if (!$contrato) {
                    continue;
                }

                // Calcula data de vencimento
                $diaVenc = $dados['dia_vencimento'] ?? 10;
                $dataVencimento = new \DateTime($competencia->format('Y-m') . '-' . str_pad((string)$diaVenc, 2, '0', STR_PAD_LEFT));

                // Cria o lançamento
                $lancamento = new LancamentosFinanceiros();
                $lancamento->setContrato($contrato);
                $lancamento->setImovel($contrato->getImovel());
                $lancamento->setInquilino($contrato->getPessoaLocatario());

                if ($contrato->getImovel()->getPessoaProprietario()) {
                    $lancamento->setProprietario($contrato->getImovel()->getPessoaProprietario());
                }

                $lancamento->setCompetencia($competencia);
                $lancamento->setDataLancamento(new \DateTime());
                $lancamento->setDataVencimento($dataVencimento);
                $lancamento->setValorPrincipal($dados['valor_contrato']);
                $lancamento->setTipoLancamento('aluguel');
                $lancamento->setOrigem('contrato');
                $lancamento->setGeradoAutomaticamente(true);
                $lancamento->setDescricao('Aluguel ' . $competencia->format('m/Y'));
                $lancamento->calcularTotal();

                $this->em->persist($lancamento);
                $lancamentosCriados[] = $lancamento;
            }

            $this->em->flush();
            $this->em->commit();

            $this->logger->info('Lançamentos automáticos gerados', [
                'competencia' => $competencia->format('Y-m'),
                'quantidade' => count($lancamentosCriados)
            ]);

            return $lancamentosCriados;

        } catch (\Exception $e) {
            $this->em->rollback();
            $this->logger->error('Erro ao gerar lançamentos automáticos', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Busca lançamentos em atraso
     *
     * @return array
     */
    public function buscarEmAtraso(): array
    {
        $lancamentos = $this->lancamentoRepo->findEmAtraso();

        return array_map(fn($l) => $this->enriquecerLancamento($l), $lancamentos);
    }

    /**
     * Obtém estatísticas
     *
     * @param array|null $filtros
     * @return array
     */
    public function obterEstatisticas(?array $filtros = null): array
    {
        return $this->lancamentoRepo->getEstatisticas($filtros);
    }

    /**
     * Lista inquilinos com débitos
     *
     * @return array
     */
    public function listarInquilinosComDebitos(): array
    {
        $conn = $this->em->getConnection();

        $sql = "
            SELECT
                p.idpessoa as id,
                p.nome,
                COUNT(l.id) as qtd_lancamentos,
                SUM(l.valor_saldo) as valor_total
            FROM pessoas p
            JOIN lancamentos_financeiros l ON l.id_inquilino = p.idpessoa
            WHERE l.situacao IN ('aberto', 'parcial', 'atrasado')
            AND l.ativo = true
            GROUP BY p.idpessoa, p.nome
            HAVING SUM(l.valor_saldo) > 0
            ORDER BY SUM(l.valor_saldo) DESC
        ";

        return $conn->fetchAllAssociative($sql);
    }

    /**
     * Busca baixas recentes
     *
     * @param int $limite
     * @return array
     */
    public function buscarBaixasRecentes(int $limite = 10): array
    {
        $baixas = $this->baixaRepo->findRecentes($limite);

        return array_map(fn($b) => $this->enriquecerBaixa($b), $baixas);
    }

    /**
     * Busca total recebido por período
     *
     * @param \DateTime $inicio
     * @param \DateTime $fim
     * @return array
     */
    public function buscarTotalRecebido(\DateTime $inicio, \DateTime $fim): array
    {
        return $this->baixaRepo->getTotalRecebidoPeriodo($inicio, $fim);
    }

    /**
     * Busca lançamentos por contrato
     *
     * @param int $contratoId
     * @return array
     */
    public function buscarLancamentosContrato(int $contratoId): array
    {
        $lancamentos = $this->lancamentoRepo->findByContrato($contratoId);

        return array_map(fn($l) => $this->enriquecerLancamento($l), $lancamentos);
    }

    /**
     * Cancela um lançamento
     *
     * @param int $id
     * @param string $motivo
     * @return LancamentosFinanceiros
     * @throws \Exception
     */
    public function cancelarLancamento(int $id, string $motivo): LancamentosFinanceiros
    {
        $this->em->beginTransaction();

        try {
            $lancamento = $this->lancamentoRepo->find($id);

            if (!$lancamento) {
                throw new \RuntimeException('Lançamento não encontrado');
            }

            if ($lancamento->isPago()) {
                throw new \RuntimeException('Não é possível cancelar um lançamento já pago');
            }

            $lancamento->setSituacao('cancelado');
            $lancamento->setAtivo(false);

            $observacoes = $lancamento->getObservacoes() ?? '';
            $observacoes .= "\n[" . date('Y-m-d H:i:s') . "] Cancelado: " . $motivo;
            $lancamento->setObservacoes($observacoes);

            $this->em->flush();
            $this->em->commit();

            $this->logger->info('Lançamento cancelado', ['id' => $id, 'motivo' => $motivo]);

            return $lancamento;

        } catch (\Exception $e) {
            $this->em->rollback();
            $this->logger->error('Erro ao cancelar lançamento', ['id' => $id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    // === MÉTODOS PRIVADOS ===

    /**
     * Preenche dados do lançamento
     *
     * @param LancamentosFinanceiros $lancamento
     * @param array $dados
     */
    private function preencherLancamento(LancamentosFinanceiros $lancamento, array $dados): void
    {
        if (!empty($dados['contrato'])) {
            $contrato = $this->contratoRepo->find($dados['contrato']);
            $lancamento->setContrato($contrato);
            if ($contrato) {
                $lancamento->setImovel($contrato->getImovel());
                $lancamento->setInquilino($contrato->getPessoaLocatario());
            }
        }

        if (!empty($dados['inquilino'])) {
            $inquilino = $this->pessoaRepo->find($dados['inquilino']);
            $lancamento->setInquilino($inquilino);
        }

        if (!empty($dados['proprietario'])) {
            $proprietario = $this->pessoaRepo->find($dados['proprietario']);
            $lancamento->setProprietario($proprietario);
        }

        if (!empty($dados['competencia'])) {
            $lancamento->setCompetencia(new \DateTime($dados['competencia'] . '-01'));
        }

        if (!empty($dados['dataVencimento'])) {
            $lancamento->setDataVencimento(new \DateTime($dados['dataVencimento']));
        }

        if (!empty($dados['dataLimite'])) {
            $lancamento->setDataLimite(new \DateTime($dados['dataLimite']));
        }

        // Valores
        if (isset($dados['valorPrincipal'])) {
            $lancamento->setValorPrincipal($dados['valorPrincipal']);
        }

        if (isset($dados['valorCondominio'])) {
            $lancamento->setValorCondominio($dados['valorCondominio']);
        }

        if (isset($dados['valorIptu'])) {
            $lancamento->setValorIptu($dados['valorIptu']);
        }

        if (isset($dados['valorAgua'])) {
            $lancamento->setValorAgua($dados['valorAgua']);
        }

        if (isset($dados['valorLuz'])) {
            $lancamento->setValorLuz($dados['valorLuz']);
        }

        if (isset($dados['valorGas'])) {
            $lancamento->setValorGas($dados['valorGas']);
        }

        if (isset($dados['valorOutros'])) {
            $lancamento->setValorOutros($dados['valorOutros']);
        }

        if (isset($dados['valorMulta'])) {
            $lancamento->setValorMulta($dados['valorMulta']);
        }

        if (isset($dados['valorJuros'])) {
            $lancamento->setValorJuros($dados['valorJuros']);
        }

        if (isset($dados['valorDesconto'])) {
            $lancamento->setValorDesconto($dados['valorDesconto']);
        }

        if (!empty($dados['tipoLancamento'])) {
            $lancamento->setTipoLancamento($dados['tipoLancamento']);
        }

        if (!empty($dados['descricao'])) {
            $lancamento->setDescricao($dados['descricao']);
        }

        if (!empty($dados['observacoes'])) {
            $lancamento->setObservacoes($dados['observacoes']);
        }
    }

    /**
     * Enriquece dados do lançamento
     *
     * @param LancamentosFinanceiros $lancamento
     * @return array
     */
    private function enriquecerLancamento(LancamentosFinanceiros $lancamento): array
    {
        $inquilino = $lancamento->getInquilino();
        $proprietario = $lancamento->getProprietario();
        $imovel = $lancamento->getImovel();
        $contrato = $lancamento->getContrato();

        return [
            'id' => $lancamento->getId(),
            'numeroAcordo' => $lancamento->getNumeroAcordo(),
            'numeroParcela' => $lancamento->getNumeroParcela(),
            'numeroRecibo' => $lancamento->getNumeroRecibo(),
            'numeroBoleto' => $lancamento->getNumeroBoleto(),
            'inquilino' => $inquilino ? [
                'id' => $inquilino->getIdpessoa(),
                'nome' => $inquilino->getNome(),
            ] : null,
            'proprietario' => $proprietario ? [
                'id' => $proprietario->getIdpessoa(),
                'nome' => $proprietario->getNome(),
            ] : null,
            'imovel' => $imovel ? [
                'id' => $imovel->getId(),
                'codigoInterno' => $imovel->getCodigoInterno(),
            ] : null,
            'contrato' => $contrato ? [
                'id' => $contrato->getId(),
            ] : null,
            'competencia' => $lancamento->getCompetencia()->format('Y-m'),
            'competenciaFormatada' => $lancamento->getCompetenciaFormatada(),
            'dataVencimento' => $lancamento->getDataVencimento()->format('Y-m-d'),
            'dataVencimentoFormatada' => $lancamento->getDataVencimento()->format('d/m/Y'),
            'dataLimite' => $lancamento->getDataLimite()?->format('Y-m-d'),
            'valorPrincipal' => $lancamento->getValorPrincipal(),
            'valorCondominio' => $lancamento->getValorCondominio(),
            'valorIptu' => $lancamento->getValorIptu(),
            'valorAgua' => $lancamento->getValorAgua(),
            'valorLuz' => $lancamento->getValorLuz(),
            'valorGas' => $lancamento->getValorGas(),
            'valorOutros' => $lancamento->getValorOutros(),
            'valorMulta' => $lancamento->getValorMulta(),
            'valorJuros' => $lancamento->getValorJuros(),
            'valorDesconto' => $lancamento->getValorDesconto(),
            'valorTotal' => $lancamento->getValorTotal(),
            'valorTotalFormatado' => 'R$ ' . number_format((float) $lancamento->getValorTotal(), 2, ',', '.'),
            'valorPago' => $lancamento->getValorPago(),
            'valorPagoFormatado' => 'R$ ' . number_format((float) $lancamento->getValorPago(), 2, ',', '.'),
            'valorSaldo' => $lancamento->getValorSaldo(),
            'valorSaldoFormatado' => 'R$ ' . number_format((float) $lancamento->getValorSaldo(), 2, ',', '.'),
            'situacao' => $lancamento->getSituacao(),
            'situacaoLabel' => $this->getSituacaoLabel($lancamento->getSituacao()),
            'tipoLancamento' => $lancamento->getTipoLancamento(),
            'descricao' => $lancamento->getDescricao(),
            'emAtraso' => $lancamento->isEmAtraso(),
            'diasAtraso' => $lancamento->getDiasAtraso(),
            'isPago' => $lancamento->isPago(),
            'isParcial' => $lancamento->isParcial(),
            'baixas' => array_map(fn($b) => $this->enriquecerBaixa($b), $lancamento->getBaixas()->toArray()),
        ];
    }

    /**
     * Enriquece dados da baixa
     *
     * @param BaixasFinanceiras $baixa
     * @return array
     */
    private function enriquecerBaixa(BaixasFinanceiras $baixa): array
    {
        $lancamento = $baixa->getLancamento();
        $inquilino = $lancamento?->getInquilino();

        return [
            'id' => $baixa->getId(),
            'lancamentoId' => $lancamento?->getId(),
            'inquilino' => $inquilino ? [
                'id' => $inquilino->getIdpessoa(),
                'nome' => $inquilino->getNome(),
            ] : null,
            'dataPagamento' => $baixa->getDataPagamento()->format('Y-m-d'),
            'dataPagamentoFormatada' => $baixa->getDataPagamento()->format('d/m/Y'),
            'valorPago' => $baixa->getValorPago(),
            'valorMultaPaga' => $baixa->getValorMultaPaga(),
            'valorJurosPago' => $baixa->getValorJurosPago(),
            'valorDesconto' => $baixa->getValorDesconto(),
            'valorTotalPago' => $baixa->getValorTotalPago(),
            'valorTotalPagoFormatado' => 'R$ ' . number_format((float) $baixa->getValorTotalPago(), 2, ',', '.'),
            'formaPagamento' => $baixa->getFormaPagamento(),
            'formaPagamentoLabel' => $this->getFormaPagamentoLabel($baixa->getFormaPagamento()),
            'numeroDocumento' => $baixa->getNumeroDocumento(),
            'estornada' => $baixa->isEstornada(),
        ];
    }

    /**
     * Retorna label amigável para situação
     *
     * @param string $situacao
     * @return string
     */
    private function getSituacaoLabel(string $situacao): string
    {
        return match($situacao) {
            'aberto' => 'Em Aberto',
            'pago' => 'Pago',
            'parcial' => 'Parcialmente Pago',
            'atrasado' => 'Em Atraso',
            'cancelado' => 'Cancelado',
            default => ucfirst($situacao),
        };
    }

    /**
     * Retorna label amigável para forma de pagamento
     *
     * @param string $forma
     * @return string
     */
    private function getFormaPagamentoLabel(string $forma): string
    {
        return match($forma) {
            'boleto' => 'Boleto',
            'pix' => 'PIX',
            'transferencia' => 'Transferência',
            'dinheiro' => 'Dinheiro',
            'cheque' => 'Cheque',
            'cartao' => 'Cartão',
            default => ucfirst($forma),
        };
    }
}
