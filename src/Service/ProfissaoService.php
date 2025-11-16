<?php

namespace App\Service;

use App\Entity\Profissoes;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service responsável pela lógica de negócio de Profissões
 * Segue o padrão "Fat Service" - toda lógica de negócio aqui
 */
class ProfissaoService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Salva uma nova profissão no banco de dados
     *
     * @param string $nome Nome da profissão
     * @return Profissoes
     * @throws \RuntimeException Se o nome for inválido
     */
    public function salvarProfissao(string $nome): Profissoes
    {
        // Validação
        if (empty($nome)) {
            throw new \RuntimeException('Nome da profissão é obrigatório');
        }

        // Criação da entidade
        $profissao = new Profissoes();
        $profissao->setNome($nome);
        $profissao->setAtivo(true);

        // Persistência
        $this->entityManager->persist($profissao);
        $this->entityManager->flush();

        // Log
        $this->logger->info('Profissão salva com sucesso', [
            'id' => $profissao->getId(),
            'nome' => $profissao->getNome()
        ]);

        return $profissao;
    }
}
