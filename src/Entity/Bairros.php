<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'bairros')]
class Bairros
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Cidades::class)]
    #[ORM\JoinColumn(name: "id_cidade", referencedColumnName: "id")]
    private Cidades $cidade;

    #[ORM\Column]
    private string $nome;

    #[ORM\Column(nullable: true)]
    private ?string $codigo = null;

    #[ORM\OneToMany(mappedBy: 'bairro', targetEntity: Logradouros::class, fetch: "LAZY")]
    private iterable $logradouros;

    public function __construct()
    {
        $this->logradouros = new \Doctrine\Common\Collections\ArrayCollection();
    }

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

    public function getIdCidade(): ?int
    {
        return $this->cidade->getId();
    }

    public function getNome(): string
    {
        return $this->nome;
    }

    public function getLogradouros(): iterable
    {
        return $this->logradouros;
    }

    public function setLogradouros(iterable $logradouros): self
    {
        $this->logradouros = $logradouros;
        return $this;
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
