<?php

namespace App\Service;

use App\Entity\Estados;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service para gerenciar Estados
 */
class EstadoService
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

    public function criar(Estados $estado): void
    {
        try {
            $this->entityManager->persist($estado);
            $this->entityManager->flush();

            $this->logger->info('Estado criado com sucesso', [
                'id' => $estado->getId(),
                'nome' => $estado->getNome(),
                'uf' => $estado->getUf()
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erro ao criar estado', [
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
            $this->logger->error('Erro ao atualizar estado', [
                'erro' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function deletar(Estados $estado): void
    {
        try {
            $this->entityManager->remove($estado);
            $this->entityManager->flush();

            $this->logger->info('Estado deletado com sucesso', [
                'id' => $estado->getId()
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erro ao deletar estado', [
                'erro' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
