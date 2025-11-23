<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'requisicoes_responsaveis')]
class RequisicoesResponsaveis
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column]
    private ?int $id = null;
    #[ORM\Column]
    private int $numeroRequisicao;
    #[ORM\Column(type: 'bigint')]
    private int $idPessoa;
    #[ORM\Column(type: 'bigint', nullable: true)]
    private ?int $idInquilino = null;
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $observacoes = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumeroRequisicao(): int
    {
        return $this->numeroRequisicao;
    }

    public function setNumeroRequisicao(int $numeroRequisicao): self
    {
        $this->numeroRequisicao = $numeroRequisicao;
        return $this;
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

    public function getIdInquilino(): ?int
    {
        return $this->idInquilino;
    }

    public function setIdInquilino(?int $idInquilino): self
    {
        $this->idInquilino = $idInquilino;
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
