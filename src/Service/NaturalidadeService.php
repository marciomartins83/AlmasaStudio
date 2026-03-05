<?php

namespace App\Service;

use App\Entity\Naturalidade;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service para gerenciar Naturalidades
 *
 * Fat Service: Contém toda a lógica de negócio
 */
class NaturalidadeService
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

    /**
     * Salva uma nova naturalidade (via AJAX)
     *
     * @param string $nome Nome da naturalidade
     * @return Naturalidade
     * @throws \RuntimeException Se nome estiver vazio
     * @throws \Exception Se houver erro ao salvar
     */
    public function salvarNaturalidade(string $nome): Naturalidade
    {
        if (empty($nome)) {
            throw new \RuntimeException('Nome da naturalidade é obrigatório');
        }

        $naturalidade = new Naturalidade();
        $naturalidade->setNome($nome);

        try {
            $this->entityManager->persist($naturalidade);
            $this->entityManager->flush();

            $this->logger->info('Naturalidade salva com sucesso', [
                'id' => $naturalidade->getId(),
                'nome' => $naturalidade->getNome()
            ]);

            return $naturalidade;

        } catch (\Exception $e) {
            $this->logger->error('Erro ao salvar naturalidade', [
                'nome' => $nome,
                'erro' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Cria uma nova naturalidade (via formulário)
     */
    public function criar(Naturalidade $naturalidade): void
    {
        try {
            $this->entityManager->persist($naturalidade);
            $this->entityManager->flush();

            $this->logger->info('Naturalidade criada com sucesso', [
                'id' => $naturalidade->getId(),
                'nome' => $naturalidade->getNome()
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erro ao criar naturalidade', [
                'erro' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Atualiza uma naturalidade existente
     */
    public function atualizar(): void
    {
        try {
            $this->entityManager->flush();
        } catch (\Exception $e) {
            $this->logger->error('Erro ao atualizar naturalidade', [
                'erro' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Deleta uma naturalidade
     */
    public function deletar(Naturalidade $naturalidade): void
    {
        try {
            $this->entityManager->remove($naturalidade);
            $this->entityManager->flush();

            $this->logger->info('Naturalidade deletada com sucesso', [
                'id' => $naturalidade->getId()
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erro ao deletar naturalidade', [
                'erro' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
