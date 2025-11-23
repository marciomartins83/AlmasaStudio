<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'relacionamentos_familiares')]
class RelacionamentosFamiliares
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column]
    private ?int $id = null;
    #[ORM\Column(type: 'bigint')]
    private int $idPessoaOrigem;
    #[ORM\Column(type: 'bigint')]
    private int $idPessoaDestino;
    #[ORM\Column]
    private string $tipoRelacionamento;
    #[ORM\Column(type: 'bigint', nullable: true)]
    private ?int $idRegimeCasamento = null;
    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $dataInicio = null;
    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $dataFim = null;
    #[ORM\Column(type: 'boolean')]
    private bool $ativo;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdPessoaOrigem(): int
    {
        return $this->idPessoaOrigem;
    }

    public function setIdPessoaOrigem(int $idPessoaOrigem): self
    {
        $this->idPessoaOrigem = $idPessoaOrigem;
        return $this;
    }

    public function getIdPessoaDestino(): int
    {
        return $this->idPessoaDestino;
    }

    public function setIdPessoaDestino(int $idPessoaDestino): self
    {
        $this->idPessoaDestino = $idPessoaDestino;
        return $this;
    }

    public function getTipoRelacionamento(): string
    {
        return $this->tipoRelacionamento;
    }

    public function setTipoRelacionamento(string $tipoRelacionamento): self
    {
        $this->tipoRelacionamento = $tipoRelacionamento;
        return $this;
    }

    public function getIdRegimeCasamento(): ?int
    {
        return $this->idRegimeCasamento;
    }

    public function setIdRegimeCasamento(?int $idRegimeCasamento): self
    {
        $this->idRegimeCasamento = $idRegimeCasamento;
        return $this;
    }

    public function getDataInicio(): ?\DateTimeInterface
    {
        return $this->dataInicio;
    }

    public function setDataInicio(?\DateTimeInterface $dataInicio): self
    {
        $this->dataInicio = $dataInicio;
        return $this;
    }

    public function getDataFim(): ?\DateTimeInterface
    {
        return $this->dataFim;
    }

    public function setDataFim(?\DateTimeInterface $dataFim): self
    {
        $this->dataFim = $dataFim;
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
