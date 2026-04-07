<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\AlmasaPlanoContas;
use App\Entity\Lancamentos;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class AlmasaPlanoContasService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger
    ) {}

    public function criar(AlmasaPlanoContas $conta): void
    {
        try {
            $this->entityManager->persist($conta);
            $this->entityManager->flush();

            $this->criarLancamentoSaldoAnterior($conta);

            $this->logger->info('AlmasaPlanoContas criado', ['id' => $conta->getId(), 'codigo' => $conta->getCodigo()]);
        } catch (\Exception $e) {
            $this->logger->error('Erro ao criar AlmasaPlanoContas', ['erro' => $e->getMessage()]);
            throw $e;
        }
    }

    public function atualizar(AlmasaPlanoContas $conta): void
    {
        try {
            $this->entityManager->flush();
        } catch (\Exception $e) {
            $this->logger->error('Erro ao atualizar AlmasaPlanoContas', ['erro' => $e->getMessage()]);
            throw $e;
        }
    }

    public function deletar(AlmasaPlanoContas $conta): void
    {
        if ($conta->getFilhos()->count() > 0) {
            throw new \RuntimeException('Não é possível excluir: esta conta possui subcontas vinculadas.');
        }
        if ($conta->getAlmasaLancamentos()->count() > 0) {
            throw new \RuntimeException('Não é possível excluir: esta conta possui lançamentos vinculados.');
        }

        try {
            $this->entityManager->remove($conta);
            $this->entityManager->flush();
            $this->logger->info('AlmasaPlanoContas deletado', ['id' => $conta->getId()]);
        } catch (\Exception $e) {
            $this->logger->error('Erro ao deletar AlmasaPlanoContas', ['erro' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Cria lancamento de saldo anterior ao criar conta no plano.
     * Gera lancamento tipo receber ja pago na data da criacao.
     */
    private function criarLancamentoSaldoAnterior(AlmasaPlanoContas $conta): void
    {
        $saldo = $conta->getSaldoAnteriorFloat();
        if ($saldo <= 0) {
            return;
        }

        $hoje = new \DateTime();
        $historico = 'Saldo anterior — ' . $conta->getCodigo() . ' ' . $conta->getDescricao();

        $lancamento = new Lancamentos();
        $lancamento->setTipo(Lancamentos::TIPO_RECEBER);
        $lancamento->setDataMovimento($hoje);
        $lancamento->setDataVencimento($hoje);
        $lancamento->setDataPagamento($hoje);
        $lancamento->setCompetencia($hoje->format('Y-m'));
        $lancamento->setValor(number_format($saldo, 2, '.', ''));
        $lancamento->setValorPago(number_format($saldo, 2, '.', ''));
        $lancamento->setStatus(Lancamentos::STATUS_PAGO);
        $lancamento->setHistorico($historico);
        $lancamento->setFormaPagamento('debito');
        $lancamento->setPlanoContaCredito($conta);

        $this->entityManager->persist($lancamento);
        $this->entityManager->flush();

        $this->logger->info('Lancamento saldo anterior criado para plano de contas', [
            'plano_id' => $conta->getId(),
            'valor' => $saldo,
            'lancamento_id' => $lancamento->getId(),
        ]);
    }
}
