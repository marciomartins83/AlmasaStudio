<?php

namespace App\Service;

use App\Entity\TiposRemessa;
use App\Entity\TiposTelefones;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class GenericTipoService
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

    public function criar(object $entity): void
    {
        try {
            $this->entityManager->persist($entity);
            $this->entityManager->flush();

            $this->logger->info('Entidade criada com sucesso', [
                'tipo' => get_class($entity),
                'id' => method_exists($entity, 'getId') ? $entity->getId() : null
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erro ao criar entidade', [
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
            $this->logger->error('Erro ao atualizar entidade', [
                'erro' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function deletar(object $entity): void
    {
        try {
            $this->entityManager->remove($entity);
            $this->entityManager->flush();

            $this->logger->info('Entidade deletada com sucesso', [
                'tipo' => get_class($entity),
                'id' => method_exists($entity, 'getId') ? $entity->getId() : null
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erro ao deletar entidade', [
                'erro' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function salvarTipoTelefone(string $tipo): \App\Entity\TiposTelefones
    {
        $entity = new \App\Entity\TiposTelefones();
        $entity->setTipo($tipo);
        $this->entityManager->persist($entity);
        $this->entityManager->flush();
        return $entity;
    }

    public function salvarTipoEndereco(string $tipo): \App\Entity\TiposEnderecos
    {
        $entity = new \App\Entity\TiposEnderecos();
        $entity->setTipo($tipo);
        $this->entityManager->persist($entity);
        $this->entityManager->flush();
        return $entity;
    }

    public function salvarTipoEmail(string $tipo): \App\Entity\TiposEmails
    {
        $entity = new \App\Entity\TiposEmails();
        $entity->setTipo($tipo);
        $this->entityManager->persist($entity);
        $this->entityManager->flush();
        return $entity;
    }

    public function salvarTipoChavePix(string $tipo): \App\Entity\TiposChavesPix
    {
        $entity = new \App\Entity\TiposChavesPix();
        $entity->setTipo($tipo);
        $this->entityManager->persist($entity);
        $this->entityManager->flush();
        return $entity;
    }

    public function salvarTipoDocumento(string $tipo): \App\Entity\TiposDocumentos
    {
        $entity = new \App\Entity\TiposDocumentos();
        $entity->setTipo($tipo);
        $this->entityManager->persist($entity);
        $this->entityManager->flush();
        return $entity;
    }

    public function salvarTipoProfissao(string $tipo): \App\Entity\Profissoes
    {
        $entity = new \App\Entity\Profissoes();
        $entity->setDescricao($tipo);
        $this->entityManager->persist($entity);
        $this->entityManager->flush();
        return $entity;
    }

    public function salvarTipoContaBancaria(string $tipo): \App\Entity\TiposContasBancarias
    {
        $entity = new \App\Entity\TiposContasBancarias();
        $entity->setTipo($tipo);
        $this->entityManager->persist($entity);
        $this->entityManager->flush();
        return $entity;
    }

    public function salvarTipoRemessa(string $tipo): TiposRemessa
    {
        $entity = new TiposRemessa();
        $entity->setTipo($tipo);
        $this->entityManager->persist($entity);
        $this->entityManager->flush();
        return $entity;
    }
}
