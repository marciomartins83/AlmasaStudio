<?php

namespace App\Service;

use App\Entity\Nacionalidade;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service para gerenciar Nacionalidades
 *
 * Fat Service: Contém toda a lógica de negócio
 */
class NacionalidadeService
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
     * Salva uma nova nacionalidade (via AJAX)
     *
     * @param string $nome Nome da nacionalidade
     * @return Nacionalidade
     * @throws \RuntimeException Se nome estiver vazio
     * @throws \Exception Se houver erro ao salvar
     */
    public function salvarNacionalidade(string $nome): Nacionalidade
    {
        if (empty($nome)) {
            throw new \RuntimeException('Nome da nacionalidade é obrigatório');
        }

        $nacionalidade = new Nacionalidade();
        $nacionalidade->setNome($nome);

        try {
            $this->entityManager->persist($nacionalidade);
            $this->entityManager->flush();

            $this->logger->info('Nacionalidade salva com sucesso', [
                'id' => $nacionalidade->getId(),
                'nome' => $nacionalidade->getNome()
            ]);

            return $nacionalidade;

        } catch (\Exception $e) {
            $this->logger->error('Erro ao salvar nacionalidade', [
                'nome' => $nome,
                'erro' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Cria uma nova nacionalidade (via formulário)
     */
    public function criar(Nacionalidade $nacionalidade): void
    {
        try {
            $this->entityManager->persist($nacionalidade);
            $this->entityManager->flush();

            $this->logger->info('Nacionalidade criada com sucesso', [
                'id' => $nacionalidade->getId(),
                'nome' => $nacionalidade->getNome()
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erro ao criar nacionalidade', [
                'erro' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Atualiza uma nacionalidade existente
     */
    public function atualizar(): void
    {
        try {
            $this->entityManager->flush();
        } catch (\Exception $e) {
            $this->logger->error('Erro ao atualizar nacionalidade', [
                'erro' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Deleta uma nacionalidade
     */
    public function deletar(Nacionalidade $nacionalidade): void
    {
        try {
            $this->entityManager->remove($nacionalidade);
            $this->entityManager->flush();

            $this->logger->info('Nacionalidade deletada com sucesso', [
                'id' => $nacionalidade->getId()
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erro ao deletar nacionalidade', [
                'erro' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
