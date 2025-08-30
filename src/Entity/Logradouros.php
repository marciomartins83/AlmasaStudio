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

    #[ORM\ManyToOne(targetEntity: Bairros::class)]
    #[ORM\JoinColumn(name: "id_bairro", referencedColumnName: "id")]
    private ?Bairros $bairro = null;

    #[ORM\Column]
    private string $logradouro;

    #[ORM\Column]
    private string $cep;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBairro(): ?Bairros
    {
        return $this->bairro;
    }

    public function setBairro(?Bairros $bairro): self
    {
        $this->bairro = $bairro;
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

    public function getIdBairro(): int
    {
        return $this->bairro ? $this->bairro->getId() : 0;
    }

    public function setIdBairro(int $idBairro): self
    {
        // Método mantido para compatibilidade
        return $this;
    }
}
