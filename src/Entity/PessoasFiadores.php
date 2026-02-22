<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'pessoas_fiadores')]

class PessoasFiadores
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column]
    private ?int $id = null;
    #[ORM\Column(type: 'bigint')]
    private int $idPessoa;
    #[ORM\Column(type: 'bigint', nullable: true)]
    private ?int $idConjuge = null;
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $motivoFianca = null;
    #[ORM\Column(type: 'boolean')]
    private bool $jaFoiFiador = false;
    #[ORM\Column(type: 'boolean')]
    private bool $conjugeTrabalha = false;
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $outros = null;

    #[ORM\Column(type: 'bigint', nullable: true)]
    private ?int $idFormaRetirada = null;

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

    public function getIdFormaRetirada(): ?int
    {
        return $this->idFormaRetirada;
    }

    public function setIdFormaRetirada(?int $idFormaRetirada): self
    {
        $this->idFormaRetirada = $idFormaRetirada;
        return $this;
    }

    public function getIdConjuge(): ?int
    {
        return $this->idConjuge;
    }

    public function setIdConjuge(?int $idConjuge): self
    {
        $this->idConjuge = $idConjuge;
        return $this;
    }

    public function getMotivoFianca(): ?string
    {
        return $this->motivoFianca;
    }

    public function setMotivoFianca(?string $motivoFianca): self
    {
        $this->motivoFianca = $motivoFianca;
        return $this;
    }

    public function getJaFoiFiador(): bool
    {
        return $this->jaFoiFiador;
    }

    public function setJaFoiFiador(bool $jaFoiFiador): self
    {
        $this->jaFoiFiador = $jaFoiFiador;
        return $this;
    }

    public function getConjugeTrabalha(): bool
    {
        return $this->conjugeTrabalha;
    }

    public function setConjugeTrabalha(bool $conjugeTrabalha): self
    {
        $this->conjugeTrabalha = $conjugeTrabalha;
        return $this;
    }

    public function getOutros(): ?string
    {
        return $this->outros;
    }

    public function setOutros(?string $outros): self
    {
        $this->outros = $outros;
        return $this;
    }
}
