<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\AlmasaPlanoContas;
use App\Entity\AlmasaVinculoBancario;
use App\Entity\ContasBancarias;
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

            $this->atualizarLancamentoSaldoAnterior($conta);

            $this->logger->info('AlmasaPlanoContas atualizado', ['id' => $conta->getId(), 'codigo' => $conta->getCodigo()]);
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
     * Atualiza lancamento de saldo anterior ao editar conta no plano.
     * Cria se nao existe e saldo > 0, atualiza se mudou, remove se zerou.
     */
    private function atualizarLancamentoSaldoAnterior(AlmasaPlanoContas $conta): void
    {
        $saldo = $conta->getSaldoAnteriorFloat();
        $lancamento = $this->buscarLancamentoSaldoAnterior($conta);

        if ($saldo <= 0 && $lancamento) {
            $this->entityManager->remove($lancamento);
            $this->entityManager->flush();
            $this->logger->info('Lancamento saldo anterior removido', ['plano_id' => $conta->getId()]);
            return;
        }

        if ($saldo <= 0) {
            return;
        }

        if (!$lancamento) {
            $this->criarLancamentoSaldoAnterior($conta);
            return;
        }

        $historico = 'Saldo anterior — ' . $conta->getCodigo() . ' ' . $conta->getDescricao();
        $lancamento->setValor(number_format($saldo, 2, '.', ''));
        $lancamento->setValorPago(number_format($saldo, 2, '.', ''));
        $lancamento->setHistorico($historico);
        $lancamento->setContaBancaria($this->buscarContaBancariaVinculada($conta));
        $this->entityManager->flush();

        $this->logger->info('Lancamento saldo anterior atualizado', [
            'plano_id' => $conta->getId(),
            'valor' => $saldo,
            'lancamento_id' => $lancamento->getId(),
        ]);
    }

    /**
     * Busca a conta bancaria vinculada (padrao=true) ao plano de contas, se existir.
     */
    private function buscarContaBancariaVinculada(AlmasaPlanoContas $conta): ?ContasBancarias
    {
        $vinculo = $this->entityManager->getRepository(AlmasaVinculoBancario::class)
            ->findOneBy(
                ['almasaPlanoConta' => $conta, 'ativo' => true],
                ['padrao' => 'DESC']
            );

        return $vinculo?->getContaBancaria();
    }

    /**
     * Busca lancamento de saldo anterior existente para uma conta do plano.
     */
    private function buscarLancamentoSaldoAnterior(AlmasaPlanoContas $conta): ?Lancamentos
    {
        return $this->entityManager->getRepository(Lancamentos::class)
            ->createQueryBuilder('l')
            ->where('l.planoContaCredito = :conta')
            ->andWhere('l.historico LIKE :prefixo')
            ->setParameter('conta', $conta)
            ->setParameter('prefixo', 'Saldo anterior%')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
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
        $lancamento->setContaBancaria($this->buscarContaBancariaVinculada($conta));

        $this->entityManager->persist($lancamento);
        $this->entityManager->flush();

        $this->logger->info('Lancamento saldo anterior criado para plano de contas', [
            'plano_id' => $conta->getId(),
            'valor' => $saldo,
            'lancamento_id' => $lancamento->getId(),
        ]);
    }
}
