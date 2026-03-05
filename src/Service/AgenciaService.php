<?php

namespace App\Service;

use App\Entity\Agencias;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service para gerenciar Agencias
 */
class AgenciaService
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

    public function criar(Agencias $agencia): void
    {
        try {
            $this->entityManager->persist($agencia);
            $this->entityManager->flush();

            $this->logger->info('Agencia criada com sucesso', [
                'id' => $agencia->getId(),
                'nome' => $agencia->getNome()
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erro ao criar agencia', [
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
            $this->logger->error('Erro ao atualizar agencia', [
                'erro' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function deletar(Agencias $agencia): void
    {
        try {
            $this->entityManager->remove($agencia);
            $this->entityManager->flush();

            $this->logger->info('Agencia deletada com sucesso', [
                'id' => $agencia->getId()
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erro ao deletar agencia', [
                'erro' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
