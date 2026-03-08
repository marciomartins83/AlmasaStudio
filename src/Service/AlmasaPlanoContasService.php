<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\AlmasaPlanoContas;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class AlmasaPlanoContasService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger
    ) {}

    public function criar(AlmasaPlanoContas $conta): void
    {
        try {
            $this->entityManager->persist($conta);
            $this->entityManager->flush();
            $this->logger->info('AlmasaPlanoContas criado', ['id' => $conta->getId(), 'codigo' => $conta->getCodigo()]);
        } catch (\Exception $e) {
            $this->logger->error('Erro ao criar AlmasaPlanoContas', ['erro' => $e->getMessage()]);
            throw $e;
        }
    }

    public function atualizar(AlmasaPlanoContas $conta): void
    {
        try {
            $this->entityManager->flush();
        } catch (\Exception $e) {
            $this->logger->error('Erro ao atualizar AlmasaPlanoContas', ['erro' => $e->getMessage()]);
            throw $e;
        }
    }

    public function deletar(AlmasaPlanoContas $conta): void
    {
        if ($conta->getFilhos()->count() > 0) {
            throw new \RuntimeException('Não é possível excluir: esta conta possui subcontas vinculadas.');
        }
        if ($conta->getAlmasaLancamentos()->count() > 0) {
            throw new \RuntimeException('Não é possível excluir: esta conta possui lançamentos vinculados.');
        }

        try {
            $this->entityManager->remove($conta);
            $this->entityManager->flush();
            $this->logger->info('AlmasaPlanoContas deletado', ['id' => $conta->getId()]);
        } catch (\Exception $e) {
            $this->logger->error('Erro ao deletar AlmasaPlanoContas', ['erro' => $e->getMessage()]);
            throw $e;
        }
    }
}
