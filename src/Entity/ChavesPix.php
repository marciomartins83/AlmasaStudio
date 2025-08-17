<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'chaves_pix')]
class ChavesPix
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;
    #[ORM\Column(type: 'bigint')]
    private int $idPessoa;
    #[ORM\Column(type: 'bigint')]
    private int $idTipoChave;
    #[ORM\Column]
    private string $chavePix;
    #[ORM\Column(type: 'bigint', nullable: true)]
    private ?int $idContaBancaria = null;
    #[ORM\Column(type: 'boolean')]
    private bool $principal;
    #[ORM\Column(type: 'boolean')]
    private bool $ativo;

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

    public function getIdTipoChave(): int
    {
        return $this->idTipoChave;
    }

    public function setIdTipoChave(int $idTipoChave): self
    {
        $this->idTipoChave = $idTipoChave;
        return $this;
    }

    public function getChavePix(): string
    {
        return $this->chavePix;
    }

    public function setChavePix(string $chavePix): self
    {
        $this->chavePix = $chavePix;
        return $this;
    }

    public function getIdContaBancaria(): ?int
    {
        return $this->idContaBancaria;
    }

    public function setIdContaBancaria(?int $idContaBancaria): self
    {
        $this->idContaBancaria = $idContaBancaria;
        return $this;
    }

    public function getPrincipal(): bool
    {
        return $this->principal;
    }

    public function setPrincipal(bool $principal): self
    {
        $this->principal = $principal;
        return $this;
    }

    public function getAtivo(): bool
    {
        return $this->ativo;
    }

    public function setAtivo(bool $ativo): self
    {
        $this->ativo = $ativo;
        return $this;
    }

}
