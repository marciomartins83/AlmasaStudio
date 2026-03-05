<?php

namespace App\Service;

use App\Entity\Telefones;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service para gerenciar Telefones
 */
class TelefoneService
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

    public function criar(Telefones $telefone): void
    {
        try {
            $this->entityManager->persist($telefone);
            $this->entityManager->flush();

            $this->logger->info('Telefone criado com sucesso', [
                'id' => $telefone->getId()
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erro ao criar telefone', [
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
            $this->logger->error('Erro ao atualizar telefone', [
                'erro' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function deletar(Telefones $telefone): void
    {
        try {
            $this->entityManager->remove($telefone);
            $this->entityManager->flush();

            $this->logger->info('Telefone deletado com sucesso', [
                'id' => $telefone->getId()
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erro ao deletar telefone', [
                'erro' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
