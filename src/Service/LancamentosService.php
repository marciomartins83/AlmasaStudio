<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Lancamentos;
use App\Entity\PlanoContas;
use App\Entity\Pessoas;
use App\Entity\ImoveisContratos;
use App\Entity\Imoveis;
use App\Entity\ContasBancarias;
use App\Repository\LancamentosRepository;
use App\Repository\PlanoContasRepository;
use App\Repository\PessoaRepository;
use App\Repository\ImoveisContratosRepository;
use App\Repository\ImoveisRepository;
use App\Repository\ContasBancariasRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * LancamentosService - Fat Service
 *
 * Responsabilidades:
 * - Toda lógica de negócio de lançamentos
 * - Operações de persistência (persist, flush, remove)
 * - Gerenciamento de transações
 * - Cálculos e validações
 */
class LancamentosService
{
    public function __construct(
        private EntityManagerInterface $em,
        private LancamentosRepository $lancamentoRepo,
        private PlanoContasRepository $planoContaRepo,
        private PessoaRepository $pessoaRepo,
        private ImoveisContratosRepository $contratoRepo,
        private ImoveisRepository $imovelRepo,
        private ContasBancariasRepository $contaBancariaRepo,
        private Security $security
    ) {}

    /**
     * Lista lançamentos com filtros
     *
     * @return Lancamentos[]
     */
    public function listarLancamentos(array $filtros = []): array
    {
        return $this->lancamentoRepo->findByFiltros($filtros);
    }

    /**
     * Busca lançamento por ID
     */
    public function buscarPorId(int $id): ?Lancamentos
    {
        return $this->lancamentoRepo->find($id);
    }

    /**
     * Salva novo lançamento
     *
     * @throws \Exception
     */
    public function salvarLancamento(array $dados): Lancamentos
    {
        $this->em->beginTransaction();

        try {
            $lancamento = new Lancamentos();
            $this->preencherLancamento($lancamento, $dados);

            // Gerar número sequencial
            $lancamento->setNumero($this->gerarNumeroSequencial($lancamento->getTipo()));

            // Definir competência padrão se não informada
            if (empty($lancamento->getCompetencia())) {
                $lancamento->setCompetencia($lancamento->getDataVencimento()->format('Y-m'));
            }

            // Calcular retenções
            $this->calcularRetencoes($lancamento);

            // Definir status inicial
            $lancamento->atualizarStatus();

            // Definir usuário criador
            $user = $this->security->getUser();
            if ($user) {
                $lancamento->setCreatedBy($user);
            }

            $this->em->persist($lancamento);
            $this->em->flush();
            $this->em->commit();

            return $lancamento;

        } catch (\Exception $e) {
            $this->em->rollback();
            throw new \Exception('Erro ao salvar lançamento: ' . $e->getMessage());
        }
    }

    /**
     * Atualiza lançamento existente
     *
     * @throws \Exception
     */
    public function atualizarLancamento(Lancamentos $lancamento, array $dados): Lancamentos
    {
        // Não permitir edição de lançamento cancelado
        if ($lancamento->isCancelado()) {
            throw new \Exception('Não é possível editar um lançamento cancelado.');
        }

        // Não permitir edição de lançamento totalmente pago
        if ($lancamento->isPago()) {
            throw new \Exception('Não é possível editar um lançamento já pago. Estorne primeiro.');
        }

        $this->em->beginTransaction();

        try {
            $this->preencherLancamento($lancamento, $dados);

            // Recalcular retenções
            $this->calcularRetencoes($lancamento);

            // Atualizar status
            $lancamento->atualizarStatus();

            $this->em->flush();
            $this->em->commit();

            return $lancamento;

        } catch (\Exception $e) {
            $this->em->rollback();
            throw new \Exception('Erro ao atualizar lançamento: ' . $e->getMessage());
        }
    }

    /**
     * Exclui lançamento
     *
     * @throws \Exception
     */
    public function excluirLancamento(Lancamentos $lancamento): bool
    {
        // Não permitir exclusão de lançamento pago
        if ($lancamento->isPago() || $lancamento->isPagoParcial()) {
            throw new \Exception('Não é possível excluir um lançamento com pagamento. Estorne primeiro.');
        }

        $this->em->beginTransaction();

        try {
            $this->em->remove($lancamento);
            $this->em->flush();
            $this->em->commit();

            return true;

        } catch (\Exception $e) {
            $this->em->rollback();
            throw new \Exception('Erro ao excluir lançamento: ' . $e->getMessage());
        }
    }

    /**
     * Realiza baixa (pagamento) do lançamento
     *
     * @throws \Exception
     */
    public function baixarLancamento(int $id, array $dadosBaixa): Lancamentos
    {
        $lancamento = $this->buscarPorId($id);

        if (!$lancamento) {
            throw new \Exception('Lançamento não encontrado.');
        }

        if ($lancamento->isCancelado()) {
            throw new \Exception('Não é possível baixar um lançamento cancelado.');
        }

        if ($lancamento->isSuspenso()) {
            throw new \Exception('Não é possível baixar um lançamento suspenso.');
        }

        if ($lancamento->isPago()) {
            throw new \Exception('Lançamento já está totalmente pago.');
        }

        $this->em->beginTransaction();

        try {
            // Data do pagamento
            $dataPagamento = !empty($dadosBaixa['data_pagamento'])
                ? new \DateTime($dadosBaixa['data_pagamento'])
                : new \DateTime();
            $lancamento->setDataPagamento($dataPagamento);

            // Valor pago
            $valorPago = $this->parseDecimal($dadosBaixa['valor_pago'] ?? '0');
            $valorPagoAtual = $lancamento->getValorPagoFloat();
            $lancamento->setValorPago(number_format($valorPagoAtual + $valorPago, 2, '.', ''));

            // Forma de pagamento
            if (!empty($dadosBaixa['forma_pagamento'])) {
                $lancamento->setFormaPagamento($dadosBaixa['forma_pagamento']);
            }

            // Conta bancária
            if (!empty($dadosBaixa['id_conta_bancaria'])) {
                $conta = $this->contaBancariaRepo->find($dadosBaixa['id_conta_bancaria']);
                $lancamento->setContaBancaria($conta);
            }

            // Valores adicionais na baixa
            if (isset($dadosBaixa['valor_desconto'])) {
                $desconto = $this->parseDecimal($dadosBaixa['valor_desconto']);
                $lancamento->setValorDesconto(number_format($desconto, 2, '.', ''));
            }

            if (isset($dadosBaixa['valor_juros'])) {
                $juros = $this->parseDecimal($dadosBaixa['valor_juros']);
                $lancamento->setValorJuros(number_format($juros, 2, '.', ''));
            }

            if (isset($dadosBaixa['valor_multa'])) {
                $multa = $this->parseDecimal($dadosBaixa['valor_multa']);
                $lancamento->setValorMulta(number_format($multa, 2, '.', ''));
            }

            // Atualizar status baseado no valor pago
            $lancamento->atualizarStatus();

            $this->em->flush();
            $this->em->commit();

            return $lancamento;

        } catch (\Exception $e) {
            $this->em->rollback();
            throw new \Exception('Erro ao baixar lançamento: ' . $e->getMessage());
        }
    }

    /**
     * Estorna baixa do lançamento
     *
     * @throws \Exception
     */
    public function estornarBaixa(int $id): Lancamentos
    {
        $lancamento = $this->buscarPorId($id);

        if (!$lancamento) {
            throw new \Exception('Lançamento não encontrado.');
        }

        if (!$lancamento->isPago() && !$lancamento->isPagoParcial()) {
            throw new \Exception('Lançamento não possui pagamento para estornar.');
        }

        $this->em->beginTransaction();

        try {
            $lancamento->setDataPagamento(null);
            $lancamento->setValorPago('0.00');
            $lancamento->setFormaPagamento(null);
            $lancamento->setStatus(Lancamentos::STATUS_ABERTO);

            $this->em->flush();
            $this->em->commit();

            return $lancamento;

        } catch (\Exception $e) {
            $this->em->rollback();
            throw new \Exception('Erro ao estornar baixa: ' . $e->getMessage());
        }
    }

    /**
     * Cancela lançamento
     *
     * @throws \Exception
     */
    public function cancelarLancamento(int $id, string $motivo): Lancamentos
    {
        $lancamento = $this->buscarPorId($id);

        if (!$lancamento) {
            throw new \Exception('Lançamento não encontrado.');
        }

        if ($lancamento->isPago()) {
            throw new \Exception('Não é possível cancelar um lançamento totalmente pago.');
        }

        $this->em->beginTransaction();

        try {
            $lancamento->setStatus(Lancamentos::STATUS_CANCELADO);
            $lancamento->setSuspensoMotivo($motivo);

            $this->em->flush();
            $this->em->commit();

            return $lancamento;

        } catch (\Exception $e) {
            $this->em->rollback();
            throw new \Exception('Erro ao cancelar lançamento: ' . $e->getMessage());
        }
    }

    /**
     * Suspende lançamento
     *
     * @throws \Exception
     */
    public function suspenderLancamento(int $id, string $motivo): Lancamentos
    {
        $lancamento = $this->buscarPorId($id);

        if (!$lancamento) {
            throw new \Exception('Lançamento não encontrado.');
        }

        if ($lancamento->isCancelado()) {
            throw new \Exception('Não é possível suspender um lançamento cancelado.');
        }

        if ($lancamento->isPago()) {
            throw new \Exception('Não é possível suspender um lançamento pago.');
        }

        $this->em->beginTransaction();

        try {
            $lancamento->setStatus(Lancamentos::STATUS_SUSPENSO);
            $lancamento->setSuspensoMotivo($motivo);

            $this->em->flush();
            $this->em->commit();

            return $lancamento;

        } catch (\Exception $e) {
            $this->em->rollback();
            throw new \Exception('Erro ao suspender lançamento: ' . $e->getMessage());
        }
    }

    /**
     * Reativa lançamento suspenso
     *
     * @throws \Exception
     */
    public function reativarLancamento(int $id): Lancamentos
    {
        $lancamento = $this->buscarPorId($id);

        if (!$lancamento) {
            throw new \Exception('Lançamento não encontrado.');
        }

        if (!$lancamento->isSuspenso()) {
            throw new \Exception('Lançamento não está suspenso.');
        }

        $this->em->beginTransaction();

        try {
            $lancamento->setSuspensoMotivo(null);
            $lancamento->atualizarStatus();

            $this->em->flush();
            $this->em->commit();

            return $lancamento;

        } catch (\Exception $e) {
            $this->em->rollback();
            throw new \Exception('Erro ao reativar lançamento: ' . $e->getMessage());
        }
    }

    /**
     * Lista lançamentos vencidos
     *
     * @return Lancamentos[]
     */
    public function listarVencidos(?string $tipo = null): array
    {
        return $this->lancamentoRepo->findVencidos($tipo);
    }

    /**
     * Lista lançamentos por competência
     *
     * @return Lancamentos[]
     */
    public function listarPorCompetencia(string $competencia, ?string $tipo = null): array
    {
        return $this->lancamentoRepo->findByCompetencia($competencia, $tipo);
    }

    /**
     * Retorna estatísticas de lançamentos
     */
    public function getEstatisticas(?string $competencia = null): array
    {
        return $this->lancamentoRepo->getEstatisticas($competencia);
    }

    /**
     * Gera número sequencial para o tipo
     */
    public function gerarNumeroSequencial(string $tipo): int
    {
        return $this->lancamentoRepo->getProximoNumero($tipo);
    }

    /**
     * Calcula valor líquido do lançamento
     */
    public function calcularValorLiquido(Lancamentos $lancamento): float
    {
        return $lancamento->getValorLiquido();
    }

    /**
     * Lista planos de conta ativos
     *
     * @return PlanoContas[]
     */
    public function listarPlanosContaAtivos(): array
    {
        return $this->planoContaRepo->findBy(['ativo' => true], ['codigo' => 'ASC']);
    }

    /**
     * Lista contas bancárias ativas
     *
     * @return ContasBancarias[]
     */
    public function listarContasBancariasAtivas(): array
    {
        return $this->contaBancariaRepo->findBy(['ativo' => true], ['descricao' => 'ASC']);
    }

    /**
     * Lista competências com lançamentos
     *
     * @return string[]
     */
    public function listarCompetencias(): array
    {
        return $this->lancamentoRepo->findCompetencias();
    }

    // ========== MÉTODOS PRIVADOS ==========

    /**
     * Preenche dados do lançamento a partir do array
     */
    private function preencherLancamento(Lancamentos $lancamento, array $dados): void
    {
        // Tipo
        if (isset($dados['tipo'])) {
            $lancamento->setTipo($dados['tipo']);
        }

        // Datas
        if (!empty($dados['data_movimento'])) {
            $lancamento->setDataMovimento(new \DateTime($dados['data_movimento']));
        }

        if (!empty($dados['data_vencimento'])) {
            $lancamento->setDataVencimento(new \DateTime($dados['data_vencimento']));
        }

        // Competência
        if (isset($dados['competencia'])) {
            $lancamento->setCompetencia($dados['competencia']);
        }

        // Plano de Conta
        if (!empty($dados['id_plano_conta'])) {
            $planoConta = $this->planoContaRepo->find($dados['id_plano_conta']);
            if ($planoConta) {
                $lancamento->setPlanoConta($planoConta);
            }
        }

        // Histórico
        if (isset($dados['historico'])) {
            $lancamento->setHistorico($dados['historico']);
        }

        // Centro de Custo
        if (isset($dados['centro_custo'])) {
            $lancamento->setCentroCusto($dados['centro_custo']);
        }

        // Pessoas
        if (!empty($dados['id_pessoa_credor'])) {
            $credor = $this->pessoaRepo->find($dados['id_pessoa_credor']);
            $lancamento->setPessoaCredor($credor);
        } else {
            $lancamento->setPessoaCredor(null);
        }

        if (!empty($dados['id_pessoa_pagador'])) {
            $pagador = $this->pessoaRepo->find($dados['id_pessoa_pagador']);
            $lancamento->setPessoaPagador($pagador);
        } else {
            $lancamento->setPessoaPagador(null);
        }

        // Vínculos
        if (!empty($dados['id_contrato'])) {
            $contrato = $this->contratoRepo->find($dados['id_contrato']);
            $lancamento->setContrato($contrato);
        } else {
            $lancamento->setContrato(null);
        }

        if (!empty($dados['id_imovel'])) {
            $imovel = $this->imovelRepo->find($dados['id_imovel']);
            $lancamento->setImovel($imovel);
        } else {
            $lancamento->setImovel(null);
        }

        if (!empty($dados['id_conta_bancaria'])) {
            $conta = $this->contaBancariaRepo->find($dados['id_conta_bancaria']);
            $lancamento->setContaBancaria($conta);
        }

        // Valores
        if (isset($dados['valor'])) {
            $valor = $this->parseDecimal($dados['valor']);
            $lancamento->setValor(number_format($valor, 2, '.', ''));
        }

        if (isset($dados['valor_desconto'])) {
            $desconto = $this->parseDecimal($dados['valor_desconto']);
            $lancamento->setValorDesconto(number_format($desconto, 2, '.', ''));
        }

        if (isset($dados['valor_juros'])) {
            $juros = $this->parseDecimal($dados['valor_juros']);
            $lancamento->setValorJuros(number_format($juros, 2, '.', ''));
        }

        if (isset($dados['valor_multa'])) {
            $multa = $this->parseDecimal($dados['valor_multa']);
            $lancamento->setValorMulta(number_format($multa, 2, '.', ''));
        }

        // Retenções
        $lancamento->setReterInss(!empty($dados['reter_inss']));
        if (isset($dados['perc_inss'])) {
            $percInss = $this->parseDecimal($dados['perc_inss']);
            $lancamento->setPercInss(number_format($percInss, 2, '.', ''));
        }

        $lancamento->setReterIss(!empty($dados['reter_iss']));
        if (isset($dados['perc_iss'])) {
            $percIss = $this->parseDecimal($dados['perc_iss']);
            $lancamento->setPercIss(number_format($percIss, 2, '.', ''));
        }

        // Documento
        if (isset($dados['tipo_documento'])) {
            $lancamento->setTipoDocumento($dados['tipo_documento']);
        }

        if (isset($dados['numero_documento'])) {
            $lancamento->setNumeroDocumento($dados['numero_documento']);
        }

        // Forma de pagamento
        if (isset($dados['forma_pagamento'])) {
            $lancamento->setFormaPagamento($dados['forma_pagamento']);
        }

        // Origem
        if (isset($dados['origem'])) {
            $lancamento->setOrigem($dados['origem']);
        }

        // Observações
        if (isset($dados['observacoes'])) {
            $lancamento->setObservacoes($dados['observacoes']);
        }
    }

    /**
     * Calcula valores de retenção automaticamente
     */
    private function calcularRetencoes(Lancamentos $lancamento): void
    {
        $valor = $lancamento->getValorFloat();

        // INSS
        if ($lancamento->isReterInss() && $lancamento->getPercInss()) {
            $percInss = (float) $lancamento->getPercInss();
            $valorInss = $valor * ($percInss / 100);
            $lancamento->setValorInss(number_format($valorInss, 2, '.', ''));
        } else {
            $lancamento->setValorInss(null);
        }

        // ISS
        if ($lancamento->isReterIss() && $lancamento->getPercIss()) {
            $percIss = (float) $lancamento->getPercIss();
            $valorIss = $valor * ($percIss / 100);
            $lancamento->setValorIss(number_format($valorIss, 2, '.', ''));
        } else {
            $lancamento->setValorIss(null);
        }
    }

    /**
     * Converte valor monetário para decimal
     */
    private function parseDecimal(mixed $value): float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        if (is_string($value)) {
            // Remove R$ e espaços
            $value = preg_replace('/[R$\s]/', '', $value);
            // Trata formato brasileiro (1.234,56)
            if (preg_match('/^\d{1,3}(\.\d{3})*,\d{2}$/', $value)) {
                $value = str_replace('.', '', $value);
                $value = str_replace(',', '.', $value);
            }
            // Trata formato com vírgula como decimal simples (1234,56)
            elseif (strpos($value, ',') !== false && strpos($value, '.') === false) {
                $value = str_replace(',', '.', $value);
            }
        }

        return (float) $value;
    }
}
