<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'enderecos')]
class Enderecos
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;
    #[ORM\Column(type: 'bigint')]
    private int $idPessoa;
    #[ORM\ManyToOne(targetEntity: Logradouro::class)]
    #[ORM\JoinColumn(name: 'id_logradouro', referencedColumnName: 'id', nullable: false)]
    private ?Logradouro $logradouro = null;
    #[ORM\ManyToOne(targetEntity: TipoEndereco::class)]
    #[ORM\JoinColumn(name: 'id_tipo', referencedColumnName: 'id', nullable: false)]
    private ?TipoEndereco $tipo = null;
    #[ORM\Column]
    private int $endNumero;
    #[ORM\Column(nullable: true)]
    private ?string $complemento = null;

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

    public function getLogradouro(): ?Logradouro
    {
        return $this->logradouro;
    }

    public function setLogradouro(?Logradouro $logradouro): self
    {
        $this->logradouro = $logradouro;
        return $this;
    }

    public function getTipo(): ?TipoEndereco
    {
        return $this->tipo;
    }

    public function setTipo(?TipoEndereco $tipo): self
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
