<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(
    name: 'pessoas_socios',
    indexes: [
        new ORM\Index(name: 'idx_pessoas_socios_pessoa', columns: ['id_pessoa']),
        new ORM\Index(name: 'idx_pessoas_socios_ativo', columns: ['ativo']),
    ],
    uniqueConstraints: [
        new ORM\UniqueConstraint(name: 'uk_socios_pessoa', columns: ['id_pessoa']),
    ]
)]
#[ORM\HasLifecycleCallbacks]
class PessoasSocios
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'id_pessoa', type: 'integer')]
    private int $idPessoa;

    #[ORM\Column(name: 'percentual_participacao', type: Types::DECIMAL, precision: 5, scale: 2, nullable: true)]
    private ?string $percentualParticipacao = null;

    #[ORM\Column(name: 'data_entrada', type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dataEntrada = null;

    #[ORM\Column(name: 'tipo_socio', type: 'string', length: 50, nullable: true)]
    private ?string $tipoSocio = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $observacoes = null;

    #[ORM\Column(type: 'boolean')]
    private bool $ativo = true;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime')]
    private \DateTimeInterface $updatedAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

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

    public function getPercentualParticipacao(): ?string
    {
        return $this->percentualParticipacao;
    }

    public function setPercentualParticipacao(?string $percentualParticipacao): self
    {
        $this->percentualParticipacao = $percentualParticipacao;
        return $this;
    }

    public function getDataEntrada(): ?\DateTimeInterface
    {
        return $this->dataEntrada;
    }

    public function setDataEntrada(?\DateTimeInterface $dataEntrada): self
    {
        $this->dataEntrada = $dataEntrada;
        return $this;
    }

    public function getTipoSocio(): ?string
    {
        return $this->tipoSocio;
    }

    public function setTipoSocio(?string $tipoSocio): self
    {
        $this->tipoSocio = $tipoSocio;
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

    public function isAtivo(): bool
    {
        return $this->ativo;
    }

    public function setAtivo(bool $ativo): self
    {
        $this->ativo = $ativo;
        return $this;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    #[ORM\PreUpdate]
    public function preUpdate(): void
    {
        $this->updatedAt = new \DateTime();
    }
}
