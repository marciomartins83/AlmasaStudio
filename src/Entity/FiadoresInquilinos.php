<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'fiadores_inquilinos')]
class FiadoresInquilinos
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;
    #[ORM\Column(type: 'bigint')]
    private int $idFiador;
    #[ORM\Column(type: 'bigint')]
    private int $idInquilino;

    /**
     * @ORM\Column(type="date")
     */
    private \DateTimeInterface $dataInicio;
    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $dataFim = null;
    #[ORM\Column(type: 'boolean')]
    private bool $ativo;
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $observacoes = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdFiador(): int
    {
        return $this->idFiador;
    }

    public function setIdFiador(int $idFiador): self
    {
        $this->idFiador = $idFiador;
        return $this;
    }

    public function getIdInquilino(): int
    {
        return $this->idInquilino;
    }

    public function setIdInquilino(int $idInquilino): self
    {
        $this->idInquilino = $idInquilino;
        return $this;
    }

    public function getDataInicio(): \DateTimeInterface
    {
        return $this->dataInicio;
    }

    public function setDataInicio(\DateTimeInterface $dataInicio): self
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
