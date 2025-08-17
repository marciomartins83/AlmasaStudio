<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'pessoas_corretoras')]
class PessoasCorretoras
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;
    #[ORM\Column(type: 'bigint')]
    private int $idPessoa;
    #[ORM\Column(nullable: true)]
    private ?string $contatoNome = null;
    #[ORM\Column(nullable: true)]
    private ?string $contatoTelefone = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdPessoa(): int
    {
        return $this->idPessoa;
    }

    public function setIdPessoa(int $idPessoa): self
    {
        $this->idPessoa = $idPessoa;
        return $this;
    }

    public function getContatoNome(): ?string
    {
        return $this->contatoNome;
    }

    public function setContatoNome(?string $contatoNome): self
    {
        $this->contatoNome = $contatoNome;
        return $this;
    }

    public function getContatoTelefone(): ?string
    {
        return $this->contatoTelefone;
    }

    public function setContatoTelefone(?string $contatoTelefone): self
    {
        $this->contatoTelefone = $contatoTelefone;
        return $this;
    }

}
