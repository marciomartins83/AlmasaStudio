<?php

namespace App\Service;

use App\Entity\IndiceEconomico;
use App\Repository\IndiceEconomicoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service para gerenciar Índices Econômicos (IGPM, Tribunal de Justiça)
 */
class IndiceEconomicoService
{
    private EntityManagerInterface $entityManager;
    private IndiceEconomicoRepository $repository;
    private LoggerInterface $logger;

    public function __construct(
        EntityManagerInterface $entityManager,
        IndiceEconomicoRepository $repository,
        LoggerInterface $logger
    ) {
        $this->entityManager = $entityManager;
        $this->repository = $repository;
        $this->logger = $logger;
    }

    public function criar(IndiceEconomico $indice): void
    {
        try {
            $this->entityManager->persist($indice);
            $this->entityManager->flush();

            $this->logger->info('Índice econômico criado com sucesso', [
                'id' => $indice->getId(),
                'tipo' => $indice->getTipo(),
                'competencia' => $indice->getCompetencia(),
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erro ao criar índice econômico', [
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
            $this->logger->error('Erro ao atualizar índice econômico', [
                'erro' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function deletar(IndiceEconomico $indice): void
    {
        try {
            $this->entityManager->remove($indice);
            $this->entityManager->flush();

            $this->logger->info('Índice econômico deletado com sucesso', [
                'id' => $indice->getId()
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erro ao deletar índice econômico', [
                'erro' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Busca o valor do índice vigente para um tipo (IGPM/TJ) numa competência ('YYYY-MM').
     */
    public function buscarVigente(string $tipo, string $competencia): ?IndiceEconomico
    {
        return $this->repository->findVigente($tipo, $competencia);
    }
}
