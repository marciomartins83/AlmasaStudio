<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'pessoas_corretores')]
class PessoasCorretores
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;
    #[ORM\Column(type: 'bigint')]
    private int $idPessoa;
    #[ORM\Column(nullable: true)]
    private ?string $creci = null;
    #[ORM\Column(nullable: true)]
    private ?string $usuario = null;
    #[ORM\Column(nullable: true)]
    private ?string $status = null;
    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $dataCadastro = null;
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

    public function getCreci(): ?string
    {
        return $this->creci;
    }

    public function setCreci(?string $creci): self
    {
        $this->creci = $creci;
        return $this;
    }

    public function getUsuario(): ?string
    {
        return $this->usuario;
    }

    public function setUsuario(?string $usuario): self
    {
        $this->usuario = $usuario;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getDataCadastro(): ?\DateTimeInterface
    {
        return $this->dataCadastro;
    }

    public function setDataCadastro(?\DateTimeInterface $dataCadastro): self
    {
        $this->dataCadastro = $dataCadastro;
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
