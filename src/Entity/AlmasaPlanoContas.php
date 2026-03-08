<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\AlmasaPlanoContasRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AlmasaPlanoContasRepository::class)]
#[ORM\Table(
    name: 'almasa_plano_contas',
    indexes: [
        new ORM\Index(name: 'idx_almasa_pc_tipo', columns: ['tipo']),
        new ORM\Index(name: 'idx_almasa_pc_nivel', columns: ['nivel']),
        new ORM\Index(name: 'idx_almasa_pc_pai', columns: ['id_pai']),
    ]
)]
#[ORM\HasLifecycleCallbacks]
class AlmasaPlanoContas
{
    public const TIPO_RECEITA = 'receita';
    public const TIPO_DESPESA = 'despesa';

    public const NIVEL_GRUPO = 1;
    public const NIVEL_SUBGRUPO = 2;
    public const NIVEL_CONTA = 3;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 20, unique: true)]
    private string $codigo;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $descricao;

    #[ORM\Column(type: Types::STRING, length: 10)]
    private string $tipo = self::TIPO_RECEITA;

    #[ORM\Column(type: Types::SMALLINT)]
    private int $nivel = self::NIVEL_CONTA;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'filhos')]
    #[ORM\JoinColumn(name: 'id_pai', referencedColumnName: 'id', nullable: true, onDelete: 'RESTRICT')]
    private ?self $pai = null;

    /**
     * @var Collection<int, self>
     */
    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'pai')]
    #[ORM\OrderBy(['codigo' => 'ASC'])]
    private Collection $filhos;

    #[ORM\Column(name: 'aceita_lancamentos', type: Types::BOOLEAN)]
    private bool $aceitaLancamentos = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $ativo = true;

    /**
     * @var Collection<int, AlmasaLancamento>
     */
    #[ORM\OneToMany(targetEntity: AlmasaLancamento::class, mappedBy: 'almasaPlanoConta')]
    private Collection $almasaLancamentos;

    /**
     * @var Collection<int, PlanoContas>
     */
    #[ORM\OneToMany(targetEntity: PlanoContas::class, mappedBy: 'almasaPlanoConta')]
    private Collection $planosContasCliente;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $updatedAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
        $this->filhos = new ArrayCollection();
        $this->almasaLancamentos = new ArrayCollection();
        $this->planosContasCliente = new ArrayCollection();
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTime();
    }

    // === GETTERS E SETTERS ===

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCodigo(): string
    {
        return $this->codigo;
    }

    public function setCodigo(string $codigo): self
    {
        $this->codigo = $codigo;
        return $this;
    }

    public function getDescricao(): string
    {
        return $this->descricao;
    }

    public function setDescricao(string $descricao): self
    {
        $this->descricao = $descricao;
        return $this;
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

    public function getNivel(): int
    {
        return $this->nivel;
    }

    public function setNivel(int $nivel): self
    {
        $this->nivel = $nivel;
        return $this;
    }

    public function getPai(): ?self
    {
        return $this->pai;
    }

    public function setPai(?self $pai): self
    {
        $this->pai = $pai;
        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getFilhos(): Collection
    {
        return $this->filhos;
    }

    public function isAceitaLancamentos(): bool
    {
        return $this->aceitaLancamentos;
    }

    public function setAceitaLancamentos(bool $aceitaLancamentos): self
    {
        $this->aceitaLancamentos = $aceitaLancamentos;
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

    /**
     * @return Collection<int, AlmasaLancamento>
     */
    public function getAlmasaLancamentos(): Collection
    {
        return $this->almasaLancamentos;
    }

    public function addAlmasaLancamento(AlmasaLancamento $lancamento): self
    {
        if (!$this->almasaLancamentos->contains($lancamento)) {
            $this->almasaLancamentos->add($lancamento);
            $lancamento->setAlmasaPlanoConta($this);
        }
        return $this;
    }

    public function removeAlmasaLancamento(AlmasaLancamento $lancamento): self
    {
        if ($this->almasaLancamentos->removeElement($lancamento)) {
            if ($lancamento->getAlmasaPlanoConta() === $this) {
                $lancamento->setAlmasaPlanoConta(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, PlanoContas>
     */
    public function getPlanosContasCliente(): Collection
    {
        return $this->planosContasCliente;
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

    // === METODOS AUXILIARES ===

    public function isGrupo(): bool
    {
        return $this->nivel === self::NIVEL_GRUPO;
    }

    public function isSubgrupo(): bool
    {
        return $this->nivel === self::NIVEL_SUBGRUPO;
    }

    public function isConta(): bool
    {
        return $this->nivel === self::NIVEL_CONTA;
    }

    public function isReceita(): bool
    {
        return $this->tipo === self::TIPO_RECEITA;
    }

    public function isDespesa(): bool
    {
        return $this->tipo === self::TIPO_DESPESA;
    }

    public function getNivelLabel(): string
    {
        return match ($this->nivel) {
            self::NIVEL_GRUPO => 'Grupo',
            self::NIVEL_SUBGRUPO => 'Subgrupo',
            self::NIVEL_CONTA => 'Conta',
            default => 'Desconhecido',
        };
    }

    public function __toString(): string
    {
        return $this->codigo . ' - ' . $this->descricao;
    }
}
