<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\IndiceEconomicoRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: IndiceEconomicoRepository::class)]
#[ORM\Table(name: 'indices_economicos')]
#[ORM\UniqueConstraint(name: 'uq_indice_tipo_competencia', columns: ['tipo', 'competencia'])]
#[ORM\Index(name: 'idx_indices_tipo', columns: ['tipo'])]
#[ORM\Index(name: 'idx_indices_competencia', columns: ['competencia'])]
#[ORM\HasLifecycleCallbacks]
class IndiceEconomico
{
    public const TIPO_IGPM = 'IGPM';
    public const TIPO_TJ = 'TJ';

    public const TIPO_VALOR_INDICE = 'indice';
    public const TIPO_VALOR_PERCENTUAL = 'percentual';

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 20)]
    private string $tipo;

    #[ORM\Column(type: Types::STRING, length: 7)]
    private string $competencia;

    #[ORM\Column(name: 'tipo_valor', type: Types::STRING, length: 10, options: ['default' => self::TIPO_VALOR_INDICE])]
    private string $tipoValor = self::TIPO_VALOR_INDICE;

    #[ORM\Column(name: 'valor_indice', type: Types::DECIMAL, precision: 15, scale: 6, nullable: true)]
    private ?string $valorIndice = null;

    #[ORM\Column(name: 'valor_percentual', type: Types::DECIMAL, precision: 8, scale: 4, nullable: true)]
    private ?string $valorPercentual = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $observacoes = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_MUTABLE)]
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

    public function getTipo(): string
    {
        return $this->tipo;
    }

    public function setTipo(string $tipo): self
    {
        $this->tipo = $tipo;
        return $this;
    }

    public function getCompetencia(): string
    {
        return $this->competencia;
    }

    public function setCompetencia(string $competencia): self
    {
        $this->competencia = $competencia;
        return $this;
    }

    public function getTipoValor(): string
    {
        return $this->tipoValor;
    }

    public function setTipoValor(string $tipoValor): self
    {
        $this->tipoValor = $tipoValor;
        return $this;
    }

    public function getValorIndice(): ?string
    {
        return $this->valorIndice;
    }

    public function setValorIndice(?string $valorIndice): self
    {
        $this->valorIndice = $valorIndice;
        return $this;
    }

    public function getValorPercentual(): ?string
    {
        return $this->valorPercentual;
    }

    public function setValorPercentual(?string $valorPercentual): self
    {
        $this->valorPercentual = $valorPercentual;
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

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    #[ORM\PreUpdate]
    public function preUpdate(): void
    {
        $this->updatedAt = new \DateTime();
    }

    public function isIndice(): bool
    {
        return $this->tipoValor === self::TIPO_VALOR_INDICE;
    }
}
