<?php

namespace App\Service;

use App\Entity\ContasBancarias;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service para gerenciar Contas Bancarias
 */
class ContaBancariaService
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

    public function criar(ContasBancarias $contaBancaria): void
    {
        try {
            $this->entityManager->persist($contaBancaria);
            $this->entityManager->flush();

            $this->logger->info('Conta Bancaria criada com sucesso', [
                'id' => $contaBancaria->getId(),
                'conta' => $contaBancaria->getCodigo()
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erro ao criar conta bancaria', [
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
            $this->logger->error('Erro ao atualizar conta bancaria', [
                'erro' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function deletar(ContasBancarias $contaBancaria): void
    {
        try {
            $this->entityManager->remove($contaBancaria);
            $this->entityManager->flush();

            $this->logger->info('Conta Bancaria deletada com sucesso', [
                'id' => $contaBancaria->getId()
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erro ao deletar conta bancaria', [
                'erro' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
