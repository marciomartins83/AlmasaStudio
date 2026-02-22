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
}
