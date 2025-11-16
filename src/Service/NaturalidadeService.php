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
     * Salva uma nova naturalidade
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
}
