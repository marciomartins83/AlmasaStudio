<?php

namespace App\Service;

use App\Entity\PessoasCorretores;
use Doctrine\ORM\EntityManagerInterface;

class PessoaCorretorService
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function criar(PessoasCorretores $pessoaCorretor): void
    {
        $this->entityManager->persist($pessoaCorretor);
        $this->entityManager->flush();
    }

    public function atualizar(): void
    {
        $this->entityManager->flush();
    }

    public function deletar(PessoasCorretores $pessoaCorretor): void
    {
        $this->entityManager->remove($pessoaCorretor);
        $this->entityManager->flush();
    }
}
