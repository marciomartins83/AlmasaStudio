<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\Estados;

#[ORM\Entity]
#[ORM\Table(name: 'cidades')]
class Cidades
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Estados::class, inversedBy: "cidades")]
    #[ORM\JoinColumn(name: "id_estado", referencedColumnName: "id")]
    private ?Estados $estado = null;

    #[ORM\Column]
    private string $nome;

    #[ORM\Column(nullable: true)]
    private ?string $codigo = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEstado(): ?Estados
    {
        return $this->estado;
    }

    public function setEstado(?Estados $estado): self
    {
        $this->estado = $estado;
        return $this;
    }

    public function getNome(): string
    {
        return $this->nome;
    }

    public function setNome(string $nome): self
    {
        $this->nome = $nome;
        return $this;
    }

    public function getCodigo(): ?string
    {
        return $this->codigo;
    }

    public function setCodigo(?string $codigo): self
    {
        $this->codigo = $codigo;
        return $this;
    }

    public function __toString(): string
    {
        return $this->nome;
    }
}
