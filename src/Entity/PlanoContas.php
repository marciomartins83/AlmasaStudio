<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\PlanoContasRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PlanoContasRepository::class)]
#[ORM\Table(name: 'plano_contas')]
class PlanoContas
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 20, unique: true)]
    private string $codigo;

    #[ORM\Column(type: Types::SMALLINT)]
    private int $tipo = 0;

    #[ORM\Column(type: Types::STRING, length: 100)]
    private string $descricao;

    #[ORM\Column(name: 'incide_taxa_admin', type: Types::BOOLEAN)]
    private bool $incideTaxaAdmin = false;

    #[ORM\Column(name: 'incide_ir', type: Types::BOOLEAN)]
    private bool $incideIr = false;

    #[ORM\Column(name: 'entra_informe', type: Types::BOOLEAN)]
    private bool $entraInforme = false;

    #[ORM\Column(name: 'entra_desconto', type: Types::BOOLEAN)]
    private bool $entraDesconto = false;

    #[ORM\Column(name: 'entra_multa', type: Types::BOOLEAN)]
    private bool $entraMulta = false;

    #[ORM\Column(name: 'codigo_contabil', type: Types::STRING, length: 20, nullable: true)]
    private ?string $codigoContabil = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $ativo = true;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $updatedAt;

    /**
     * @var Collection<int, Lancamentos>
     */
    #[ORM\OneToMany(targetEntity: Lancamentos::class, mappedBy: 'planoConta')]
    private Collection $lancamentos;

    /**
     * @var Collection<int, InformesRendimentos>
     */
    #[ORM\OneToMany(targetEntity: InformesRendimentos::class, mappedBy: 'planoConta')]
    private Collection $informesRendimentos;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
        $this->lancamentos = new ArrayCollection();
        $this->informesRendimentos = new ArrayCollection();
    }

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

    public function getTipo(): int
    {
        return $this->tipo;
    }

    public function setTipo(int $tipo): self
    {
        $this->tipo = $tipo;
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

    public function isIncideTaxaAdmin(): bool
    {
        return $this->incideTaxaAdmin;
    }

    public function setIncideTaxaAdmin(bool $incideTaxaAdmin): self
    {
        $this->incideTaxaAdmin = $incideTaxaAdmin;
        return $this;
    }

    public function isIncideIr(): bool
    {
        return $this->incideIr;
    }

    public function setIncideIr(bool $incideIr): self
    {
        $this->incideIr = $incideIr;
        return $this;
    }

    public function isEntraInforme(): bool
    {
        return $this->entraInforme;
    }

    public function setEntraInforme(bool $entraInforme): self
    {
        $this->entraInforme = $entraInforme;
        return $this;
    }

    public function isEntraDesconto(): bool
    {
        return $this->entraDesconto;
    }

    public function setEntraDesconto(bool $entraDesconto): self
    {
        $this->entraDesconto = $entraDesconto;
        return $this;
    }

    public function isEntraMulta(): bool
    {
        return $this->entraMulta;
    }

    public function setEntraMulta(bool $entraMulta): self
    {
        $this->entraMulta = $entraMulta;
        return $this;
    }

    public function getCodigoContabil(): ?string
    {
        return $this->codigoContabil;
    }

    public function setCodigoContabil(?string $codigoContabil): self
    {
        $this->codigoContabil = $codigoContabil;
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

    /**
     * @return Collection<int, Lancamentos>
     */
    public function getLancamentos(): Collection
    {
        return $this->lancamentos;
    }

    public function addLancamento(Lancamentos $lancamento): self
    {
        if (!$this->lancamentos->contains($lancamento)) {
            $this->lancamentos->add($lancamento);
            $lancamento->setPlanoConta($this);
        }
        return $this;
    }

    public function removeLancamento(Lancamentos $lancamento): self
    {
        if ($this->lancamentos->removeElement($lancamento)) {
            if ($lancamento->getPlanoConta() === $this) {
                $lancamento->setPlanoConta(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, InformesRendimentos>
     */
    public function getInformesRendimentos(): Collection
    {
        return $this->informesRendimentos;
    }

    public function addInformeRendimento(InformesRendimentos $informe): self
    {
        if (!$this->informesRendimentos->contains($informe)) {
            $this->informesRendimentos->add($informe);
            $informe->setPlanoConta($this);
        }
        return $this;
    }

    public function removeInformeRendimento(InformesRendimentos $informe): self
    {
        if ($this->informesRendimentos->removeElement($informe)) {
            if ($informe->getPlanoConta() === $this) {
                $informe->setPlanoConta(null);
            }
        }
        return $this;
    }

    /**
     * Retorna descrição legível do tipo
     */
    public function getTipoDescricao(): string
    {
        return match($this->tipo) {
            0 => 'Receita',
            1 => 'Despesa',
            2 => 'Transitória',
            3 => 'Caixa',
            default => 'Desconhecido'
        };
    }

    public function __toString(): string
    {
        return $this->codigo . ' - ' . $this->descricao;
    }
}
