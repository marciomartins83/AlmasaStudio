<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\AlmasaLancamento;
use App\Entity\Lancamentos;
use App\Repository\AlmasaLancamentoRepository;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Events;

#[AsDoctrineListener(event: Events::postPersist)]
#[AsDoctrineListener(event: Events::postUpdate)]
class AlmasaLancamentoSubscriber
{
    public function __construct(
        private readonly AlmasaLancamentoRepository $almasaLancamentoRepo,
    ) {
    }

    public function postPersist(PostPersistEventArgs $args): void
    {
        $entity = $args->getObject();
        if (!$entity instanceof Lancamentos) {
            return;
        }

        $this->processarLancamentoDuplo($entity, $args->getObjectManager());
    }

    public function postUpdate(PostUpdateEventArgs $args): void
    {
        $entity = $args->getObject();
        if (!$entity instanceof Lancamentos) {
            return;
        }

        $this->processarLancamentoDuplo($entity, $args->getObjectManager());
    }

    private function processarLancamentoDuplo(Lancamentos $lancamento, EntityManagerInterface $em): void
    {
        $planoConta = $lancamento->getPlanoConta();
        if (!$planoConta) {
            return;
        }

        $almasaPlanoConta = $planoConta->getAlmasaPlanoConta();
        if (!$almasaPlanoConta) {
            return;
        }

        // Idempotencia: verificar se ja existe lancamento Almasa para esta origem
        $existente = $this->almasaLancamentoRepo->findByLancamentoOrigem($lancamento->getId());

        if ($existente) {
            // Atualizar status se o lancamento do cliente mudou
            if ($lancamento->isPago() && !$existente->isPago()) {
                $existente->setStatus(AlmasaLancamento::STATUS_PAGO);
                $existente->setDataPagamento($lancamento->getDataPagamento() ?? new \DateTime());
            } elseif ($lancamento->isCancelado() && !$existente->isCancelado()) {
                $existente->setStatus(AlmasaLancamento::STATUS_CANCELADO);
            }
            $existente->setValor($lancamento->getValor());
            $em->persist($existente);
            $em->flush();
            return;
        }

        // Criar novo lancamento do Almasa
        $almasaLancamento = new AlmasaLancamento();
        $almasaLancamento->setAlmasaPlanoConta($almasaPlanoConta);
        $almasaLancamento->setTipo(AlmasaLancamento::TIPO_RECEITA);
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
        $em->flush();
    }
}
