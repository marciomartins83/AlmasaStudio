<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Doctrine\ORM\EntityManagerInterface;

abstract class DatabaseTestCase extends WebTestCase
{
    protected EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);

        // Setup database limpa para cada teste
        $this->cleanDatabase();
    }

    protected function cleanDatabase(): void
    {
        // Este método deve ser implementado pelas classes de teste filhas,
        // ou podemos ter uma lógica comum aqui se as tabelas a serem limpas forem sempre as mesmas.
        // Por enquanto, vamos deixar um placeholder.
        // Exemplo de limpeza para todas as tabelas (requer conhecimento das relações):
        // $conn = $this->entityManager->getConnection();
        // $conn->executeStatement('DELETE FROM agencias');
        // $conn->executeStatement('DELETE FROM bancos');
        // ... e assim por diante para todas as tabelas relacionadas aos testes.
        // Para testes de performance e integrações mais complexas, pode ser necessário usar fixtures.
    }

    protected function createAuthenticatedClient(string $username = 'testuser'): self
    {
        // Este método é um placeholder para a criação de um cliente autenticado.
        // A implementação real dependerá do seu sistema de autenticação (ex: login form, JWT, etc.).
        // Exemplo genérico:
        // $client = static::createClient();
        // $client->request('POST', '/login', ['username' => $username, 'password' => 'password']);
        // return $client;
        return $this;
    }
} 