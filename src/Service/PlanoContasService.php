<?php

namespace App\Service;

use App\Entity\PlanoContas;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class PlanoContasService
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

    public function criar(PlanoContas $planoContas): void
    {
        try {
            $planoContas->setUpdatedAt(new \DateTime());
            $this->entityManager->persist($planoContas);
            $this->entityManager->flush();

            $this->logger->info('Plano de Contas criado', [
                'id' => $planoContas->getId(),
                'codigo' => $planoContas->getCodigo(),
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erro ao criar Plano de Contas', ['erro' => $e->getMessage()]);
            throw $e;
        }
    }

    public function atualizar(PlanoContas $planoContas): void
    {
        try {
            $planoContas->setUpdatedAt(new \DateTime());
            $this->entityManager->flush();
        } catch (\Exception $e) {
            $this->logger->error('Erro ao atualizar Plano de Contas', ['erro' => $e->getMessage()]);
            throw $e;
        }
    }

    public function deletar(PlanoContas $planoContas): void
    {
        try {
            $this->entityManager->remove($planoContas);
            $this->entityManager->flush();

            $this->logger->info('Plano de Contas deletado', ['id' => $planoContas->getId()]);
        } catch (\Exception $e) {
            $this->logger->error('Erro ao deletar Plano de Contas', ['erro' => $e->getMessage()]);
            throw $e;
        }
    }
}
