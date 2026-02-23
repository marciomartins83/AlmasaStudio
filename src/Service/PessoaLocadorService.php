<?php

namespace App\Service;

use App\Entity\PessoasLocadores;
use Doctrine\ORM\EntityManagerInterface;

class PessoaLocadorService
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function criar(PessoasLocadores $pessoaLocador): void
    {
        $this->entityManager->persist($pessoaLocador);
        $this->entityManager->flush();
    }

    public function atualizar(): void
    {
        $this->entityManager->flush();
    }

    public function deletar(PessoasLocadores $pessoaLocador): void
    {
        $this->entityManager->remove($pessoaLocador);
        $this->entityManager->flush();
    }
}
