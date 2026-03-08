<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\AlmasaLancamento;
use App\Entity\Lancamentos;
use App\Repository\AlmasaLancamentoRepository;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Events;

#[AsDoctrineListener(event: Events::postPersist)]
#[AsDoctrineListener(event: Events::postUpdate)]
#[AsDoctrineListener(event: Events::postFlush)]
class AlmasaLancamentoSubscriber
{
    /** @var Lancamentos[] */
    private array $pendentes = [];

    public function __construct(
        private readonly AlmasaLancamentoRepository $almasaLancamentoRepo,
    ) {
    }

    public function postPersist(PostPersistEventArgs $args): void
    {
        $entity = $args->getObject();
        if ($entity instanceof Lancamentos) {
            $this->pendentes[] = $entity;
        }
    }

    public function postUpdate(PostUpdateEventArgs $args): void
    {
        $entity = $args->getObject();
        if ($entity instanceof Lancamentos) {
            $this->pendentes[] = $entity;
        }
    }

    public function postFlush(PostFlushEventArgs $args): void
    {
        if (empty($this->pendentes)) {
            return;
        }

        // Esvazia a fila ANTES de processar para evitar loop infinito
        $lancamentos = $this->pendentes;
        $this->pendentes = [];

        $em = $args->getObjectManager();
        $precisaFlush = false;

        foreach ($lancamentos as $lancamento) {
            if ($this->processarLancamentoDuplo($lancamento, $em)) {
                $precisaFlush = true;
            }
        }

        if ($precisaFlush) {
            $em->flush();
        }
    }

    private function processarLancamentoDuplo(Lancamentos $lancamento, EntityManagerInterface $em): bool
    {
        $planoConta = $lancamento->getPlanoConta();
        if (!$planoConta) {
            return false;
        }

        $almasaPlanoConta = $planoConta->getAlmasaPlanoConta();
        if (!$almasaPlanoConta) {
            return false;
        }

        // Determinar tipo do lancamento Almasa baseado no tipo do lancamento do cliente
        // Lancamento "receber" do cliente = "receita" para o Almasa (taxa cobrada)
        // Lancamento "pagar" do cliente = "despesa" para o Almasa (comissão paga)
        $tipoAlmasa = $lancamento->isReceber()
            ? AlmasaLancamento::TIPO_RECEITA
            : AlmasaLancamento::TIPO_DESPESA;

        // Idempotencia: verificar se ja existe lancamento Almasa para esta origem
        $existente = $this->almasaLancamentoRepo->findByLancamentoOrigem($lancamento->getId());

        if ($existente) {
            // Atualizar status se o lancamento do cliente mudou
            $mudou = false;
            if ($lancamento->isPago() && !$existente->isPago()) {
                $existente->setStatus(AlmasaLancamento::STATUS_PAGO);
                $existente->setDataPagamento($lancamento->getDataPagamento() ?? new \DateTime());
                $mudou = true;
            } elseif ($lancamento->isCancelado() && !$existente->isCancelado()) {
                $existente->setStatus(AlmasaLancamento::STATUS_CANCELADO);
                $mudou = true;
            }
            if ($existente->getValor() !== $lancamento->getValor()) {
                $existente->setValor($lancamento->getValor());
                $mudou = true;
            }
            if ($existente->getTipo() !== $tipoAlmasa) {
                $existente->setTipo($tipoAlmasa);
                $mudou = true;
            }
            return $mudou;
        }

        // Criar novo lancamento do Almasa
        $almasaLancamento = new AlmasaLancamento();
        $almasaLancamento->setAlmasaPlanoConta($almasaPlanoConta);
        $almasaLancamento->setTipo($tipoAlmasa);
        $almasaLancamento->setDescricao(
            $planoConta->getDescricao() . ' — ' . ($lancamento->getHistorico() ?? 'Lançamento #' . $lancamento->getId())
        );
        $almasaLancamento->setValor($lancamento->getValor());
        $almasaLancamento->setDataCompetencia($lancamento->getDataVencimento());
        $almasaLancamento->setDataVencimento($lancamento->getDataVencimento());
        $almasaLancamento->setStatus(
            $lancamento->isPago() ? AlmasaLancamento::STATUS_PAGO : AlmasaLancamento::STATUS_ABERTO
        );
        if ($lancamento->isPago()) {
            $almasaLancamento->setDataPagamento($lancamento->getDataPagamento() ?? new \DateTime());
        }
        $almasaLancamento->setLancamentoOrigem($lancamento);
        $almasaLancamento->setContaBancaria($lancamento->getContaBancaria());

        $em->persist($almasaLancamento);
        return true;
    }
}
