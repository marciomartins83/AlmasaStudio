<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'pessoas_profissoes')]
class PessoasProfissoes
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;
    #[ORM\Column(type: 'bigint')]
    private int $idPessoa;
    #[ORM\Column(type: 'bigint')]
    private int $idProfissao;
    #[ORM\Column(nullable: true)]
    private ?string $empresa = null;
    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $dataAdmissao = null;
    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $dataDemissao = null;
    #[ORM\Column(type: 'decimal', nullable: true)]
    private ?string $renda = null;
    #[ORM\Column(type: 'boolean')]
    private bool $ativo;
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $observacoes = null;

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

    public function getIdProfissao(): int
    {
        return $this->idProfissao;
    }

    public function setIdProfissao(int $idProfissao): self
    {
        $this->idProfissao = $idProfissao;
        return $this;
    }

    public function getEmpresa(): ?string
    {
        return $this->empresa;
    }

    public function setEmpresa(?string $empresa): self
    {
        $this->empresa = $empresa;
        return $this;
    }

    public function getDataAdmissao(): ?\DateTimeInterface
    {
        return $this->dataAdmissao;
    }

    public function setDataAdmissao(?\DateTimeInterface $dataAdmissao): self
    {
        $this->dataAdmissao = $dataAdmissao;
        return $this;
    }

    public function getDataDemissao(): ?\DateTimeInterface
    {
        return $this->dataDemissao;
    }

    public function setDataDemissao(?\DateTimeInterface $dataDemissao): self
    {
        $this->dataDemissao = $dataDemissao;
        return $this;
    }

    public function getRenda(): ?string
    {
        return $this->renda;
    }

    public function setRenda(?string $renda): self
    {
        $this->renda = $renda;
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

    public function getObservacoes(): ?string
    {
        return $this->observacoes;
    }

    public function setObservacoes(?string $observacoes): self
    {
        $this->observacoes = $observacoes;
        return $this;
    }

}
