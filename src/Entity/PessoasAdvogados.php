<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(
    name: 'pessoas_advogados',
    indexes: [
        new ORM\Index(name: 'idx_pessoas_advogados_pessoa', columns: ['id_pessoa']),
        new ORM\Index(name: 'idx_pessoas_advogados_oab', columns: ['numero_oab']),
        new ORM\Index(name: 'idx_pessoas_advogados_ativo', columns: ['ativo']),
    ],
    uniqueConstraints: [
        new ORM\UniqueConstraint(name: 'uk_advogados_pessoa', columns: ['id_pessoa']),
    ]
)]
#[ORM\HasLifecycleCallbacks]
class PessoasAdvogados
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'id_pessoa', type: 'integer')]
    private int $idPessoa;

    #[ORM\Column(name: 'numero_oab', type: 'string', length: 20)]
    private string $numeroOab;

    #[ORM\Column(name: 'seccional_oab', type: 'string', length: 2)]
    private string $seccionalOab;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $especialidade = null;

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

    public function getNumeroOab(): string
    {
        return $this->numeroOab;
    }

    public function setNumeroOab(string $numeroOab): self
    {
        $this->numeroOab = $numeroOab;
        return $this;
    }

    public function getSeccionalOab(): string
    {
        return $this->seccionalOab;
    }

    public function setSeccionalOab(string $seccionalOab): self
    {
        $this->seccionalOab = $seccionalOab;
        return $this;
    }

    public function getEspecialidade(): ?string
    {
        return $this->especialidade;
    }

    public function setEspecialidade(?string $especialidade): self
    {
        $this->especialidade = $especialidade;
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
