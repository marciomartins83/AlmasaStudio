<?php

namespace App\Service;

use App\Entity\Bairros;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service para gerenciar Bairros
 */
class BairroService
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

    public function criar(Bairros $bairro): void
    {
        try {
            $this->entityManager->persist($bairro);
            $this->entityManager->flush();

            $this->logger->info('Bairro criado com sucesso', [
                'id' => $bairro->getId(),
                'nome' => $bairro->getNome()
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erro ao criar bairro', [
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
            $this->logger->error('Erro ao atualizar bairro', [
                'erro' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function deletar(Bairros $bairro): void
    {
        try {
            $this->entityManager->remove($bairro);
            $this->entityManager->flush();

            $this->logger->info('Bairro deletado com sucesso', [
                'id' => $bairro->getId()
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erro ao deletar bairro', [
                'erro' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
