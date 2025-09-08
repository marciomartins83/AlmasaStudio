<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\PessoaCorretorRepository;

#[ORM\Entity(repositoryClass: PessoaCorretorRepository::class)]
#[ORM\Table(name: 'pessoas_corretores')]
class PessoasCorretores
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Pessoas::class)]
    #[ORM\JoinColumn(name: 'id_pessoa', referencedColumnName: 'idpessoa', nullable: false)]
    private ?Pessoas $pessoa = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $creci = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $usuario = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $status = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true, name: 'data_cadastro')]
    private ?\DateTimeInterface $dataCadastro = null;

    #[ORM\Column]
    private ?bool $ativo = true;

    public function __construct()
    {
        $this->dataCadastro = new \DateTime();
        $this->ativo = true;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPessoa(): ?Pessoas
    {
        return $this->pessoa;
    }

    public function setPessoa(?Pessoas $pessoa): static
    {
        $this->pessoa = $pessoa;
        return $this;
    }

    public function getCreci(): ?string
    {
        return $this->creci;
    }

    public function setCreci(?string $creci): static
    {
        $this->creci = $creci;
        return $this;
    }

    public function getUsuario(): ?string
    {
        return $this->usuario;
    }

    public function setUsuario(?string $usuario): static
    {
        $this->usuario = $usuario;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getDataCadastro(): ?\DateTimeInterface
    {
        return $this->dataCadastro;
    }

    public function setDataCadastro(?\DateTimeInterface $dataCadastro): static
    {
        $this->dataCadastro = $dataCadastro;
        return $this;
    }

    public function isAtivo(): ?bool
    {
        return $this->ativo;
    }

    public function setAtivo(bool $ativo): static
    {
        $this->ativo = $ativo;
        return $this;
    }
}
