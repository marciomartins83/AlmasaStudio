<?php

namespace App\Service;

use App\Entity\Logradouros;
use App\Entity\Bairros;
use App\Entity\Cidades;
use App\Entity\Estados;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * LogradouroService - Fat Service
 * Contém TODA a lógica de negócio do módulo de logradouros/endereços
 *
 * Responsabilidades:
 * - Gerenciamento de transações
 * - Validações de negócio
 * - Operações de persistência (persist, flush, remove)
 * - Busca de CEP (local e API)
 * - Criação em cascata de estado, cidade, bairro e logradouro
 */
class LogradouroService
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
     * Cria um novo logradouro com todas as validações
     *
     * @param Logradouros $logradouro
     * @return void
     * @throws \Exception
     */
    public function criarLogradouro(Logradouros $logradouro): void
    {
        try {
            $this->entityManager->persist($logradouro);
            $this->entityManager->flush();
            $this->logger->info('Logradouro criado com sucesso: ' . $logradouro->getId());
        } catch (\Exception $e) {
            $this->logger->error('Erro ao criar logradouro: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Atualiza um logradouro existente
     *
     * @param Logradouros $logradouro
     * @return void
     * @throws \Exception
     */
    public function atualizarLogradouro(Logradouros $logradouro): void
    {
        try {
            $this->entityManager->flush();
            $this->logger->info('Logradouro atualizado com sucesso: ' . $logradouro->getId());
        } catch (\Exception $e) {
            $this->logger->error('Erro ao atualizar logradouro: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Remove um logradouro
     *
     * @param Logradouros $logradouro
     * @return void
     * @throws \Exception
     */
    public function removerLogradouro(Logradouros $logradouro): void
    {
        try {
            $this->entityManager->remove($logradouro);
            $this->entityManager->flush();
            $this->logger->info('Logradouro removido com sucesso: ' . $logradouro->getId());
        } catch (\Exception $e) {
            $this->logger->error('Erro ao remover logradouro: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Busca logradouro por CEP no banco local
     *
     * @param string $cep
     * @return array|null
     */
    public function buscarPorCepLocal(string $cep): ?array
    {
        $cepLimpo = preg_replace('/[^0-9]/', '', $cep);

        if (strlen($cepLimpo) !== 8) {
            return null;
        }

        $logradouro = $this->entityManager->getRepository(Logradouros::class)
            ->findOneBy(['cep' => $cepLimpo]);

        if (!$logradouro) {
            return null;
        }

        $bairro = $logradouro->getBairro();
        if (!$bairro) {
            return null;
        }

        $cidade = $bairro->getCidade();
        if (!$cidade) {
            return null;
        }

        $estado = $cidade->getEstado();
        if (!$estado) {
            return null;
        }

        return [
            'encontrado' => true,
            'logradouro' => $logradouro->getNome(),
            'bairro' => $bairro->getNome(),
            'cidade' => $cidade->getNome(),
            'estado' => $estado->getUf()
        ];
    }

    /**
     * Salva um novo endereço completo em cascata (estado -> cidade -> bairro -> logradouro)
     *
     * @param array $data
     * @return bool
     * @throws \Exception
     */
    public function salvarEnderecoCompleto(array $data): bool
    {
        try {
            $this->entityManager->getConnection()->beginTransaction();

            // Verifica se o estado já existe
            $estado = $this->entityManager->getRepository(Estados::class)
                ->findOneBy(['uf' => $data['estado']]);

            if (!$estado) {
                $estado = new Estados();
                $estado->setNome($data['estado']);
                $estado->setUf($data['estado']);
                $this->entityManager->persist($estado);
            }

            // Verifica se a cidade já existe
            $cidade = $this->entityManager->getRepository(Cidades::class)
                ->findOneBy([
                    'nome' => $data['cidade'],
                    'estado' => $estado
                ]);

            if (!$cidade) {
                $cidade = new Cidades();
                $cidade->setNome($data['cidade']);
                $cidade->setEstado($estado);
                $this->entityManager->persist($cidade);
            }

            // Verifica se o bairro já existe
            $bairro = $this->entityManager->getRepository(Bairros::class)
                ->findOneBy([
                    'nome' => $data['bairro'],
                    'cidade' => $cidade
                ]);

            if (!$bairro) {
                $bairro = new Bairros();
                $bairro->setNome($data['bairro']);
                $bairro->setCidade($cidade);
                $this->entityManager->persist($bairro);
            }

            // Cria o novo logradouro
            $logradouro = new Logradouros();
            $logradouro->setCep($data['cep']);
            $logradouro->setLogradouro($data['logradouro']);
            $logradouro->setBairro($bairro);
            $this->entityManager->persist($logradouro);

            $this->entityManager->flush();
            $this->entityManager->getConnection()->commit();

            return true;
        } catch (\Exception $e) {
            $this->entityManager->getConnection()->rollBack();
            $this->logger->error('Erro ao salvar endereço completo: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Busca endereço por CEP em API externa (simulado)
     *
     * @param string $cep
     * @return array
     */
    public function buscarCepApi(string $cep): array
    {
        $cepLimpo = preg_replace('/[^0-9]/', '', $cep);

        if (strlen($cepLimpo) !== 8) {
            return ['encontrado' => false, 'erro' => 'CEP inválido'];
        }

        // Simulação de chamada à API dos Correios
        // Em produção, substituir por chamada real à API
        return [
            'encontrado' => true,
            'cep' => $cepLimpo,
            'logradouro' => 'Rua Exemplo',
            'bairro' => 'Centro',
            'cidade' => 'São Paulo',
            'estado' => 'SP'
        ];
    }
}
