<?php

namespace App\Service;

use App\Entity\Agencias;
use App\Entity\Bancos;
use App\Entity\ContasBancarias;
use App\Entity\Lancamentos;
use App\Entity\Pessoas;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service para gerenciar Contas Bancarias
 */
class ContaBancariaService
{
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;

    public function __construct(
        EntityManagerInterface $entityManager,
        LoggerInterface $logger
    ) {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    /**
     * Resolve os IDs dos campos autocomplete e seta as entidades na ContaBancaria
     */
    public function resolverAutocompletes(ContasBancarias $contaBancaria, ?string $pessoaId, ?string $bancoId, ?string $agenciaId): void
    {
        // Pessoa (titular)
        if ($pessoaId) {
            $pessoa = $this->entityManager->getReference(Pessoas::class, (int) $pessoaId);
            $contaBancaria->setIdPessoa($pessoa);
        } else {
            $contaBancaria->setIdPessoa(null);
        }

        // Banco
        if ($bancoId) {
            $banco = $this->entityManager->getReference(Bancos::class, (int) $bancoId);
            $contaBancaria->setIdBanco($banco);
        } else {
            $contaBancaria->setIdBanco(null);
        }

        // Agencia
        if ($agenciaId) {
            $agencia = $this->entityManager->getReference(Agencias::class, (int) $agenciaId);
            $contaBancaria->setIdAgencia($agencia);
        } else {
            $contaBancaria->setIdAgencia(null);
        }
    }

    public function buscarPessoa(int $id): ?Pessoas
    {
        return $this->entityManager->getRepository(Pessoas::class)->find($id);
    }

    public function buscarBanco(int $id): ?Bancos
    {
        return $this->entityManager->getRepository(Bancos::class)->find($id);
    }

    public function buscarAgencia(int $id): ?Agencias
    {
        return $this->entityManager->getRepository(Agencias::class)->find($id);
    }

    public function criar(ContasBancarias $contaBancaria): void
    {
        try {
            $this->entityManager->persist($contaBancaria);
            $this->entityManager->flush();

            // Lançamento de saldo anterior (se > 0)
            $this->criarLancamentoSaldoAnterior($contaBancaria);

            $this->logger->info('Conta Bancaria criada com sucesso', [
                'id' => $contaBancaria->getId(),
                'conta' => $contaBancaria->getCodigo()
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erro ao criar conta bancaria', [
                'erro' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function atualizar(): void
    {
        try {
            $this->entityManager->flush();
        } catch (\Exception $e) {
            $this->logger->error('Erro ao atualizar conta bancaria', [
                'erro' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function deletar(ContasBancarias $contaBancaria): void
    {
        try {
            $this->entityManager->remove($contaBancaria);
            $this->entityManager->flush();

            $this->logger->info('Conta Bancaria deletada com sucesso', [
                'id' => $contaBancaria->getId()
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erro ao deletar conta bancaria', [
                'erro' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Cria lancamento de saldo anterior (saldo inicial da conta).
     * Gera um lancamento do tipo "receber" ja pago na data atual.
     */
    private function criarLancamentoSaldoAnterior(ContasBancarias $contaBancaria): void
    {
        $saldo = $contaBancaria->getSaldoAnteriorFloat();
        if ($saldo <= 0) {
            return;
        }

        $hoje = new \DateTime();
        $descricao = 'Saldo anterior — ' . ($contaBancaria->getDescricao() ?? $contaBancaria->getCodigo());

        $lancamento = new Lancamentos();
        $lancamento->setTipo(Lancamentos::TIPO_RECEBER);
        $lancamento->setDataMovimento($hoje);
        $lancamento->setDataVencimento($hoje);
        $lancamento->setDataPagamento($hoje);
        $lancamento->setCompetencia($hoje->format('Y-m'));
        $lancamento->setValor(number_format($saldo, 2, '.', ''));
        $lancamento->setValorPago(number_format($saldo, 2, '.', ''));
        $lancamento->setStatus(Lancamentos::STATUS_PAGO);
        $lancamento->setHistorico($descricao);
        $lancamento->setContaBancaria($contaBancaria);
        $lancamento->setFormaPagamento('debito');

        $this->entityManager->persist($lancamento);
        $this->entityManager->flush();

        $this->logger->info('Lancamento saldo anterior criado', [
            'conta_id' => $contaBancaria->getId(),
            'valor' => $saldo,
            'lancamento_id' => $lancamento->getId(),
        ]);
    }
}
