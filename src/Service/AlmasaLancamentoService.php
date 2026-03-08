<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\AlmasaLancamento;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class AlmasaLancamentoService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger
    ) {}

    public function criar(AlmasaLancamento $lancamento): void
    {
        try {
            $this->entityManager->persist($lancamento);
            $this->entityManager->flush();
            $this->logger->info('AlmasaLancamento criado', ['id' => $lancamento->getId()]);
        } catch (\Exception $e) {
            $this->logger->error('Erro ao criar AlmasaLancamento', ['erro' => $e->getMessage()]);
            throw $e;
        }
    }

    public function atualizar(AlmasaLancamento $lancamento): void
    {
        try {
            $this->entityManager->flush();
        } catch (\Exception $e) {
            $this->logger->error('Erro ao atualizar AlmasaLancamento', ['erro' => $e->getMessage()]);
            throw $e;
        }
    }

    public function deletar(AlmasaLancamento $lancamento): void
    {
        try {
            $this->entityManager->remove($lancamento);
            $this->entityManager->flush();
            $this->logger->info('AlmasaLancamento deletado', ['id' => $lancamento->getId()]);
        } catch (\Exception $e) {
            $this->logger->error('Erro ao deletar AlmasaLancamento', ['erro' => $e->getMessage()]);
            throw $e;
        }
    }
}
