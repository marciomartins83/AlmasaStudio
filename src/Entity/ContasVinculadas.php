<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'contas_vinculadas')]
class ContasVinculadas
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column]
    private ?int $id = null;
    #[ORM\Column(type: 'bigint')]
    private int $idContaPrincipal;
    #[ORM\Column(type: 'bigint')]
    private int $idContaVinculada;
    #[ORM\Column]
    private string $tipoVinculo;
    #[ORM\Column(type: 'boolean')]
    private bool $ativo;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdContaPrincipal(): int
    {
        return $this->idContaPrincipal;
    }

    public function setIdContaPrincipal(int $idContaPrincipal): self
    {
        $this->idContaPrincipal = $idContaPrincipal;
        return $this;
    }

    public function getIdContaVinculada(): int
    {
        return $this->idContaVinculada;
    }

    public function setIdContaVinculada(int $idContaVinculada): self
    {
        $this->idContaVinculada = $idContaVinculada;
        return $this;
    }

    public function getTipoVinculo(): string
    {
        return $this->tipoVinculo;
    }

    public function setTipoVinculo(string $tipoVinculo): self
    {
        $this->tipoVinculo = $tipoVinculo;
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
