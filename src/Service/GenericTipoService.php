<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;

class GenericTipoService
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function criar(object $entity): void
    {
        $this->entityManager->persist($entity);
        $this->entityManager->flush();
    }

    public function atualizar(): void
    {
        $this->entityManager->flush();
    }

    public function deletar(object $entity): void
    {
        $this->entityManager->remove($entity);
        $this->entityManager->flush();
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
}
