<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'logradouros')]
class Logradouros
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;
    #[ORM\Column(type: 'bigint')]
    private int $idBairro;
    #[ORM\Column]
    private string $logradouro;
    #[ORM\Column]
    private string $cep;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdBairro(): int
    {
        return $this->idBairro;
    }

    public function setIdBairro(int $idBairro): self
    {
        $this->idBairro = $idBairro;
        return $this;
    }

    public function getLogradouro(): string
    {
        return $this->logradouro;
    }

    public function setLogradouro(string $logradouro): self
    {
        $this->logradouro = $logradouro;
        return $this;
    }

    public function getCep(): string
    {
        return $this->cep;
    }

    public function setCep(string $cep): self
    {
        $this->cep = $cep;
        return $this;
    }

}
