<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'agencias')]
class Agencias
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;
    #[ORM\Column]
    private string $codigo;
    #[ORM\ManyToOne(targetEntity: Bancos::class)]
    #[ORM\JoinColumn(name: 'id_banco', referencedColumnName: 'id', nullable: false)]
    private ?Bancos $banco = null;
    #[ORM\Column]
    private string $nome;
    #[ORM\ManyToOne(targetEntity: Enderecos::class)]
    #[ORM\JoinColumn(name: 'id_endereco', referencedColumnName: 'id')]
    private ?Enderecos $endereco = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCodigo(): string
    {
        return $this->codigo;
    }

    public function setCodigo(string $codigo): self
    {
        $this->codigo = $codigo;
        return $this;
    }

    public function getBanco(): ?Bancos
    {
        return $this->banco;
    }

    public function setBanco(?Bancos $banco): self
    {
        $this->banco = $banco;
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

    public function getEndereco(): ?Enderecos
    {
        return $this->endereco;
    }

    public function setEndereco(?Enderecos $endereco): self
    {
        $this->endereco = $endereco;
        return $this;
    }

}
