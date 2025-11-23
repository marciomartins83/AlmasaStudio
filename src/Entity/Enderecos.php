<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'enderecos')]
class Enderecos
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Pessoas::class)]
    #[ORM\JoinColumn(name: 'id_pessoa', referencedColumnName: 'idpessoa', nullable: false)]
    private ?Pessoas $pessoa = null;

    #[ORM\ManyToOne(targetEntity: Logradouros::class)]
    #[ORM\JoinColumn(name: 'id_logradouro', referencedColumnName: 'id', nullable: false)]
    private ?Logradouros $logradouro = null;

    #[ORM\ManyToOne(targetEntity: TiposEnderecos::class)]
    #[ORM\JoinColumn(name: 'id_tipo', referencedColumnName: 'id', nullable: false)]
    private ?TiposEnderecos $tipo = null;

    #[ORM\Column]
    private int $endNumero;

    #[ORM\Column(nullable: true)]
    private ?string $complemento = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPessoa(): ?Pessoas
    {
        return $this->pessoa;
    }

    public function setPessoa(?Pessoas $pessoa): self
    {
        $this->pessoa = $pessoa;
        return $this;
    }

    public function getLogradouro(): ?Logradouros
    {
        return $this->logradouro;
    }

    public function setLogradouro(?Logradouros $logradouro): self
    {
        $this->logradouro = $logradouro;
        return $this;
    }

    public function getTipo(): ?TiposEnderecos
    {
        return $this->tipo;
    }

    public function setTipo(?TiposEnderecos $tipo): self
    {
        $this->tipo = $tipo;
        return $this;
    }

    public function getEndNumero(): int
    {
        return $this->endNumero;
    }

    public function setEndNumero(int $endNumero): self
    {
        $this->endNumero = $endNumero;
        return $this;
    }

    public function getComplemento(): ?string
    {
        return $this->complemento;
    }

    public function setComplemento(?string $complemento): self
    {
        $this->complemento = $complemento;
        return $this;
    }
}

