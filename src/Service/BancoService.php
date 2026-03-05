<?php

namespace App\Service;

use App\Entity\Bancos;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service para gerenciar Bancos
 */
class BancoService
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

    public function criar(Bancos $banco): void
    {
        try {
            $this->entityManager->persist($banco);
            $this->entityManager->flush();

            $this->logger->info('Banco criado com sucesso', [
                'id' => $banco->getId(),
                'nome' => $banco->getNome()
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erro ao criar banco', [
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
            $this->logger->error('Erro ao atualizar banco', [
                'erro' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function deletar(Bancos $banco): void
    {
        try {
            $this->entityManager->remove($banco);
            $this->entityManager->flush();

            $this->logger->info('Banco deletado com sucesso', [
                'id' => $banco->getId()
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erro ao deletar banco', [
                'erro' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
