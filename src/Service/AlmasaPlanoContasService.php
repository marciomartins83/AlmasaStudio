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
            $this->entityManager->beginTransaction();

            // Flush alteracoes na conta do plano
            $this->entityManager->flush();

            // Os flushes internos do sincronismo sao intencionais e continuam
            // dentro desta mesma transacao Doctrine.
            $this->atualizarLancamentoSaldoAnterior($conta);

            $this->entityManager->commit();

            $this->logger->info('AlmasaPlanoContas atualizado', ['id' => $conta->getId(), 'codigo' => $conta->getCodigo()]);
        } catch (\Exception $e) {
            $this->entityManager->rollBack();
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
            foreach ($this->buscarLancamentosVinculados($conta) as $lancamento) {
                if ($this->isLancamentoSaldoAnteriorGerado($lancamento, $conta)) {
                    $this->entityManager->remove($lancamento);
                    continue;
                }

                throw new \RuntimeException('Não é possível excluir: esta conta possui lançamentos vinculados.');
            }

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

        // Caso 1: Saldo zerou e existe lancamento -> remover
        if ($saldo <= 0 && $lancamento) {
            $this->entityManager->remove($lancamento);
            $this->entityManager->flush();
            $this->logger->info('Lancamento saldo anterior removido', ['plano_id' => $conta->getId(), 'lancamento_id' => $lancamento->getId()]);
            return;
        }

        // Caso 2: Saldo zerou e nao existe lancamento -> nada a fazer
        if ($saldo <= 0) {
            $this->logger->debug('Nao ha saldo anterior para processar', ['plano_id' => $conta->getId()]);
            return;
        }

        // Caso 3: Nao existe lancamento -> criar
        if (!$lancamento) {
            $this->logger->info('Criando lancamento de saldo anterior (nao existia)', ['plano_id' => $conta->getId(), 'saldo' => $saldo]);
            $this->criarLancamentoSaldoAnterior($conta);
            return;
        }

        // Caso 4: Lancamento existe -> atualizar
        $valorAtual = $lancamento->getValorFloat();
        $valorNovo = $saldo;
        $historico = 'Saldo anterior — ' . $conta->getCodigo() . ' ' . $conta->getDescricao();
        $valorFormatado = sprintf('%.2f', $valorNovo);
        $contaBancaria = $this->buscarContaBancariaVinculada($conta);

        $valorMudou = abs($valorAtual - $valorNovo) >= 0.01;
        $historicoMudou = $lancamento->getHistorico() !== $historico;
        $contaBancariaMudou = $lancamento->getContaBancaria()?->getId() !== $contaBancaria?->getId();

        if (!$valorMudou && !$historicoMudou && !$contaBancariaMudou) {
            $this->logger->debug('Lancamento de saldo anterior ja esta sincronizado', [
                'plano_id' => $conta->getId(),
                'valor' => $valorAtual,
            ]);
            return;
        }

        $lancamento->setValor($valorFormatado);
        $lancamento->setValorPago($valorFormatado);
        $lancamento->setHistorico($historico);

        // Atualiza conta bancaria vinculada; null remove vinculo antigo.
        $lancamento->setContaBancaria($contaBancaria);

        // Persiste imediatamente, ainda dentro da transacao aberta em atualizar().
        $this->entityManager->flush();

        $this->logger->info('Lancamento saldo anterior atualizado', [
            'plano_id' => $conta->getId(),
            'valor_antigo' => $valorAtual,
            'valor_novo' => $valorNovo,
            'lancamento_id' => $lancamento->getId(),
            'historico_atualizado' => $historicoMudou,
            'conta_bancaria_atualizada' => $contaBancariaMudou,
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
            $this->logger->warning('Tentativa de criar lancamento com saldo <= 0', ['plano_id' => $conta->getId()]);
            return;
        }

        // Data sentinel "1900-01-01" para garantir que o lancamento de saldo
        // anterior caia sempre antes de qualquer filtro de data nos relatorios.
        $dataSentinel = new \DateTime('1900-01-01');
        $historico = 'Saldo anterior — ' . $conta->getCodigo() . ' ' . $conta->getDescricao();
        $valorFormatado = sprintf('%.2f', $saldo);

        $lancamento = new Lancamentos();
        $lancamento->setTipo(Lancamentos::TIPO_RECEBER);
        $lancamento->setDataMovimento($dataSentinel);
        $lancamento->setDataVencimento($dataSentinel);
        $lancamento->setDataPagamento($dataSentinel);
        $lancamento->setCompetencia($dataSentinel->format('Y-m'));
        $lancamento->setValor($valorFormatado);
        $lancamento->setValorPago($valorFormatado);
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

    /**
     * @return Lancamentos[]
     */
    private function buscarLancamentosVinculados(AlmasaPlanoContas $conta): array
    {
        return $this->entityManager->getRepository(Lancamentos::class)
            ->createQueryBuilder('l')
            ->where('l.planoContaDebito = :conta OR l.planoContaCredito = :conta')
            ->setParameter('conta', $conta)
            ->getQuery()
            ->getResult();
    }

    private function isLancamentoSaldoAnteriorGerado(Lancamentos $lancamento, AlmasaPlanoContas $conta): bool
    {
        return $lancamento->getPlanoContaCredito() === $conta
            && str_starts_with((string) $lancamento->getHistorico(), 'Saldo anterior')
            && $lancamento->getDataVencimento()?->format('Y-m-d') === '1900-01-01';
    }
}
