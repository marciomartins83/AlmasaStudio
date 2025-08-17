<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'bairros')]
class Bairros
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;
    #[ORM\ManyToOne(targetEntity: Cidades::class)]
    #[ORM\JoinColumn(name: "id_cidade", referencedColumnName: "id")]
    private Cidades $cidade;
    #[ORM\Column]
    private string $nome;
    #[ORM\Column(nullable: true)]
    private ?string $codigo = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCidade(): Cidades
    {
        return $this->cidade;
    }

    public function setCidade(Cidades $cidade): self
    {
        $this->cidade = $cidade;
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

}
