<?php

namespace App\Service;

use App\Entity\EstadoCivil;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service para gerenciar Estado Civil
 */
class EstadoCivilService
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

    public function criar(EstadoCivil $estadoCivil): void
    {
        try {
            $this->entityManager->persist($estadoCivil);
            $this->entityManager->flush();

            $this->logger->info('Estado Civil criado com sucesso', [
                'id' => $estadoCivil->getId(),
                'nome' => $estadoCivil->getNome()
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erro ao criar estado civil', [
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
            $this->logger->error('Erro ao atualizar estado civil', [
                'erro' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function deletar(EstadoCivil $estadoCivil): void
    {
        try {
            $this->entityManager->remove($estadoCivil);
            $this->entityManager->flush();

            $this->logger->info('Estado Civil deletado com sucesso', [
                'id' => $estadoCivil->getId()
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erro ao deletar estado civil', [
                'erro' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
