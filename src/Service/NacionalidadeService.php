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
     * Salva uma nova nacionalidade
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
}
