<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'pessoas_telefones')]
class PessoasTelefones
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;
    #[ORM\Column(type: 'bigint')]
    private int $idPessoa;
    #[ORM\Column(type: 'bigint')]
    private int $idTelefone;

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

    public function getIdTelefone(): int
    {
        return $this->idTelefone;
    }

    public function setIdTelefone(int $idTelefone): self
    {
        $this->idTelefone = $idTelefone;
        return $this;
    }

}
