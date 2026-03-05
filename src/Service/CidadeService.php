<?php

namespace App\Service;

use App\Entity\Cidades;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service para gerenciar Cidades
 */
class CidadeService
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

    public function criar(Cidades $cidade): void
    {
        try {
            $this->entityManager->persist($cidade);
            $this->entityManager->flush();

            $this->logger->info('Cidade criada com sucesso', [
                'id' => $cidade->getId(),
                'nome' => $cidade->getNome()
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erro ao criar cidade', [
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
            $this->logger->error('Erro ao atualizar cidade', [
                'erro' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function deletar(Cidades $cidade): void
    {
        try {
            $this->entityManager->remove($cidade);
            $this->entityManager->flush();

            $this->logger->info('Cidade deletada com sucesso', [
                'id' => $cidade->getId()
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erro ao deletar cidade', [
                'erro' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
