<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\AlmasaVinculoBancario;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class AlmasaVinculoBancarioService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger
    ) {}

    public function criar(AlmasaVinculoBancario $vinculo): void
    {
        try {
            $this->entityManager->persist($vinculo);
            $this->entityManager->flush();
            $this->logger->info('AlmasaVinculoBancario criado', ['id' => $vinculo->getId()]);
        } catch (\Exception $e) {
            $this->logger->error('Erro ao criar AlmasaVinculoBancario', ['erro' => $e->getMessage()]);
            throw $e;
        }
    }

    public function atualizar(AlmasaVinculoBancario $vinculo): void
    {
        try {
            $this->entityManager->flush();
        } catch (\Exception $e) {
            $this->logger->error('Erro ao atualizar AlmasaVinculoBancario', ['erro' => $e->getMessage()]);
            throw $e;
        }
    }

    public function deletar(AlmasaVinculoBancario $vinculo): void
    {
        try {
            $this->entityManager->remove($vinculo);
            $this->entityManager->flush();
            $this->logger->info('AlmasaVinculoBancario deletado', ['id' => $vinculo->getId()]);
        } catch (\Exception $e) {
            $this->logger->error('Erro ao deletar AlmasaVinculoBancario', ['erro' => $e->getMessage()]);
            throw $e;
        }
    }
}
