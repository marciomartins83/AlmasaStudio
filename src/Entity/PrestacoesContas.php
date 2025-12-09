<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\PrestacoesContasRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * PrestacoesContas - Módulo de Prestação de Contas aos Proprietários
 *
 * Gera extratos financeiros por proprietário/imóvel, calcula valores de repasse,
 * e registra os pagamentos realizados.
 */
#[ORM\Entity(repositoryClass: PrestacoesContasRepository::class)]
#[ORM\Table(
    name: 'prestacoes_contas',
    indexes: [
        new ORM\Index(name: 'idx_prestacoes_contas_proprietario', columns: ['id_proprietario']),
        new ORM\Index(name: 'idx_prestacoes_contas_imovel', columns: ['id_imovel']),
        new ORM\Index(name: 'idx_prestacoes_contas_periodo', columns: ['data_inicio', 'data_fim']),
        new ORM\Index(name: 'idx_prestacoes_contas_status', columns: ['status']),
        new ORM\Index(name: 'idx_prestacoes_contas_ano', columns: ['ano']),
    ],
    uniqueConstraints: [
        new ORM\UniqueConstraint(name: 'uk_prestacao_numero_ano', columns: ['numero', 'ano'])
    ]
)]
#[ORM\HasLifecycleCallbacks]
class PrestacoesContas
{
    // Constantes de Status
    public const STATUS_GERADO = 'gerado';
    public const STATUS_APROVADO = 'aprovado';
    public const STATUS_PAGO = 'pago';
    public const STATUS_CANCELADO = 'cancelado';

    // Constantes de Tipo de Período
    public const PERIODO_PERSONALIZADO = 'personalizado';
    public const PERIODO_DIARIO = 'diario';
    public const PERIODO_SEMANAL = 'semanal';
    public const PERIODO_QUINZENAL = 'quinzenal';
    public const PERIODO_MENSAL = 'mensal';
    public const PERIODO_TRIMESTRAL = 'trimestral';
    public const PERIODO_SEMESTRAL = 'semestral';
    public const PERIODO_ANUAL = 'anual';
    public const PERIODO_BIENAL = 'bienal';

    // Constantes de Forma de Repasse
    public const FORMA_PIX = 'pix';
    public const FORMA_TED = 'ted';
    public const FORMA_DEPOSITO = 'deposito';
    public const FORMA_CHEQUE = 'cheque';
    public const FORMA_DINHEIRO = 'dinheiro';

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    // === IDENTIFICAÇÃO ===

    #[ORM\Column(type: Types::INTEGER)]
    private int $numero;

    #[ORM\Column(type: Types::INTEGER)]
    private int $ano;

    // === PERÍODO ===

    #[ORM\Column(name: 'data_inicio', type: Types::DATE_MUTABLE)]
    private \DateTimeInterface $dataInicio;

    #[ORM\Column(name: 'data_fim', type: Types::DATE_MUTABLE)]
    private \DateTimeInterface $dataFim;

    #[ORM\Column(name: 'tipo_periodo', type: Types::STRING, length: 20)]
    private string $tipoPeriodo = self::PERIODO_MENSAL;

    #[ORM\Column(type: Types::STRING, length: 7, nullable: true)]
    private ?string $competencia = null;

    // === VÍNCULOS ===

    #[ORM\ManyToOne(targetEntity: Pessoas::class)]
    #[ORM\JoinColumn(name: 'id_proprietario', referencedColumnName: 'idpessoa', nullable: false)]
    private Pessoas $proprietario;

    #[ORM\ManyToOne(targetEntity: Imoveis::class)]
    #[ORM\JoinColumn(name: 'id_imovel', referencedColumnName: 'id', nullable: true)]
    private ?Imoveis $imovel = null;

    // === ORIGEM DOS DADOS ===

    #[ORM\Column(name: 'incluir_ficha_financeira', type: Types::BOOLEAN, nullable: true)]
    private ?bool $incluirFichaFinanceira = true;

    #[ORM\Column(name: 'incluir_lancamentos', type: Types::BOOLEAN, nullable: true)]
    private ?bool $incluirLancamentos = true;

    // === TOTAIS CALCULADOS ===

    #[ORM\Column(name: 'total_receitas', type: Types::DECIMAL, precision: 15, scale: 2, nullable: true)]
    private ?string $totalReceitas = '0.00';

    #[ORM\Column(name: 'total_despesas', type: Types::DECIMAL, precision: 15, scale: 2, nullable: true)]
    private ?string $totalDespesas = '0.00';

    #[ORM\Column(name: 'total_taxa_admin', type: Types::DECIMAL, precision: 15, scale: 2, nullable: true)]
    private ?string $totalTaxaAdmin = '0.00';

    #[ORM\Column(name: 'total_retencao_ir', type: Types::DECIMAL, precision: 15, scale: 2, nullable: true)]
    private ?string $totalRetencaoIr = '0.00';

    #[ORM\Column(name: 'valor_repasse', type: Types::DECIMAL, precision: 15, scale: 2, nullable: true)]
    private ?string $valorRepasse = '0.00';

    // === REPASSE ===

    #[ORM\Column(type: Types::STRING, length: 20, nullable: true)]
    private ?string $status = self::STATUS_GERADO;

    #[ORM\Column(name: 'data_repasse', type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dataRepasse = null;

    #[ORM\Column(name: 'forma_repasse', type: Types::STRING, length: 20, nullable: true)]
    private ?string $formaRepasse = null;

    #[ORM\ManyToOne(targetEntity: ContasBancarias::class)]
    #[ORM\JoinColumn(name: 'id_conta_bancaria', referencedColumnName: 'id', nullable: true)]
    private ?ContasBancarias $contaBancaria = null;

    #[ORM\Column(name: 'comprovante_repasse', type: Types::STRING, length: 255, nullable: true)]
    private ?string $comprovanteRepasse = null;

    // === OBSERVAÇÕES ===

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $observacoes = null;

    // === AUDITORIA ===

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\ManyToOne(targetEntity: Users::class)]
    #[ORM\JoinColumn(name: 'created_by', referencedColumnName: 'id', nullable: true)]
    private ?Users $createdBy = null;

    // === RELACIONAMENTOS ===

    /**
     * @var Collection<int, PrestacoesContasItens>
     */
    #[ORM\OneToMany(
        targetEntity: PrestacoesContasItens::class,
        mappedBy: 'prestacaoConta',
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    private Collection $itens;

    public function __construct()
    {
        $this->itens = new ArrayCollection();
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
        $this->dataInicio = new \DateTime('first day of this month');
        $this->dataFim = new \DateTime('last day of this month');
        $this->ano = (int) date('Y');
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

    public function getNumero(): int
    {
        return $this->numero;
    }

    public function setNumero(int $numero): self
    {
        $this->numero = $numero;
        return $this;
    }

    public function getAno(): int
    {
        return $this->ano;
    }

    public function setAno(int $ano): self
    {
        $this->ano = $ano;
        return $this;
    }

    public function getNumeroFormatado(): string
    {
        return str_pad((string) $this->numero, 3, '0', STR_PAD_LEFT) . '/' . $this->ano;
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

    public function getDataFim(): \DateTimeInterface
    {
        return $this->dataFim;
    }

    public function setDataFim(\DateTimeInterface $dataFim): self
    {
        $this->dataFim = $dataFim;
        return $this;
    }

    public function getTipoPeriodo(): string
    {
        return $this->tipoPeriodo;
    }

    public function setTipoPeriodo(string $tipoPeriodo): self
    {
        $this->tipoPeriodo = $tipoPeriodo;
        return $this;
    }

    public function getCompetencia(): ?string
    {
        return $this->competencia;
    }

    public function setCompetencia(?string $competencia): self
    {
        $this->competencia = $competencia;
        return $this;
    }

    public function getProprietario(): Pessoas
    {
        return $this->proprietario;
    }

    public function setProprietario(Pessoas $proprietario): self
    {
        $this->proprietario = $proprietario;
        return $this;
    }

    public function getImovel(): ?Imoveis
    {
        return $this->imovel;
    }

    public function setImovel(?Imoveis $imovel): self
    {
        $this->imovel = $imovel;
        return $this;
    }

    public function isIncluirFichaFinanceira(): bool
    {
        return $this->incluirFichaFinanceira ?? true;
    }

    public function setIncluirFichaFinanceira(?bool $incluirFichaFinanceira): self
    {
        $this->incluirFichaFinanceira = $incluirFichaFinanceira;
        return $this;
    }

    public function isIncluirLancamentos(): bool
    {
        return $this->incluirLancamentos ?? true;
    }

    public function setIncluirLancamentos(?bool $incluirLancamentos): self
    {
        $this->incluirLancamentos = $incluirLancamentos;
        return $this;
    }

    public function getTotalReceitas(): string
    {
        return $this->totalReceitas ?? '0.00';
    }

    public function setTotalReceitas(?string $totalReceitas): self
    {
        $this->totalReceitas = $totalReceitas;
        return $this;
    }

    public function getTotalReceitasFloat(): float
    {
        return (float) ($this->totalReceitas ?? '0.00');
    }

    public function getTotalDespesas(): string
    {
        return $this->totalDespesas ?? '0.00';
    }

    public function setTotalDespesas(?string $totalDespesas): self
    {
        $this->totalDespesas = $totalDespesas;
        return $this;
    }

    public function getTotalDespesasFloat(): float
    {
        return (float) ($this->totalDespesas ?? '0.00');
    }

    public function getTotalTaxaAdmin(): string
    {
        return $this->totalTaxaAdmin ?? '0.00';
    }

    public function setTotalTaxaAdmin(?string $totalTaxaAdmin): self
    {
        $this->totalTaxaAdmin = $totalTaxaAdmin;
        return $this;
    }

    public function getTotalTaxaAdminFloat(): float
    {
        return (float) ($this->totalTaxaAdmin ?? '0.00');
    }

    public function getTotalRetencaoIr(): string
    {
        return $this->totalRetencaoIr ?? '0.00';
    }

    public function setTotalRetencaoIr(?string $totalRetencaoIr): self
    {
        $this->totalRetencaoIr = $totalRetencaoIr;
        return $this;
    }

    public function getTotalRetencaoIrFloat(): float
    {
        return (float) ($this->totalRetencaoIr ?? '0.00');
    }

    public function getValorRepasse(): string
    {
        return $this->valorRepasse ?? '0.00';
    }

    public function setValorRepasse(?string $valorRepasse): self
    {
        $this->valorRepasse = $valorRepasse;
        return $this;
    }

    public function getValorRepasseFloat(): float
    {
        return (float) ($this->valorRepasse ?? '0.00');
    }

    public function getStatus(): string
    {
        return $this->status ?? self::STATUS_GERADO;
    }

    public function setStatus(?string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function isGerado(): bool
    {
        return $this->status === self::STATUS_GERADO;
    }

    public function isAprovado(): bool
    {
        return $this->status === self::STATUS_APROVADO;
    }

    public function isPago(): bool
    {
        return $this->status === self::STATUS_PAGO;
    }

    public function isCancelado(): bool
    {
        return $this->status === self::STATUS_CANCELADO;
    }

    public function getDataRepasse(): ?\DateTimeInterface
    {
        return $this->dataRepasse;
    }

    public function setDataRepasse(?\DateTimeInterface $dataRepasse): self
    {
        $this->dataRepasse = $dataRepasse;
        return $this;
    }

    public function getFormaRepasse(): ?string
    {
        return $this->formaRepasse;
    }

    public function setFormaRepasse(?string $formaRepasse): self
    {
        $this->formaRepasse = $formaRepasse;
        return $this;
    }

    public function getContaBancaria(): ?ContasBancarias
    {
        return $this->contaBancaria;
    }

    public function setContaBancaria(?ContasBancarias $contaBancaria): self
    {
        $this->contaBancaria = $contaBancaria;
        return $this;
    }

    public function getComprovanteRepasse(): ?string
    {
        return $this->comprovanteRepasse;
    }

    public function setComprovanteRepasse(?string $comprovanteRepasse): self
    {
        $this->comprovanteRepasse = $comprovanteRepasse;
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

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getCreatedBy(): ?Users
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?Users $createdBy): self
    {
        $this->createdBy = $createdBy;
        return $this;
    }

    /**
     * @return Collection<int, PrestacoesContasItens>
     */
    public function getItens(): Collection
    {
        return $this->itens;
    }

    public function addItem(PrestacoesContasItens $item): self
    {
        if (!$this->itens->contains($item)) {
            $this->itens->add($item);
            $item->setPrestacaoConta($this);
        }
        return $this;
    }

    public function removeItem(PrestacoesContasItens $item): self
    {
        if ($this->itens->removeElement($item)) {
            if ($item->getPrestacaoConta() === $this) {
                $item->setPrestacaoConta(null);
            }
        }
        return $this;
    }

    // === MÉTODOS AUXILIARES ===

    /**
     * Retorna label do status para exibição
     */
    public function getStatusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_GERADO => 'Gerado',
            self::STATUS_APROVADO => 'Aprovado',
            self::STATUS_PAGO => 'Pago',
            self::STATUS_CANCELADO => 'Cancelado',
            default => $this->status,
        };
    }

    /**
     * Retorna classe CSS do badge de status
     */
    public function getStatusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_GERADO => 'info',
            self::STATUS_APROVADO => 'warning',
            self::STATUS_PAGO => 'success',
            self::STATUS_CANCELADO => 'danger',
            default => 'secondary',
        };
    }

    /**
     * Retorna label do tipo de período
     */
    public function getTipoPeriodoLabel(): string
    {
        return match ($this->tipoPeriodo) {
            self::PERIODO_PERSONALIZADO => 'Personalizado',
            self::PERIODO_DIARIO => 'Diário',
            self::PERIODO_SEMANAL => 'Semanal',
            self::PERIODO_QUINZENAL => 'Quinzenal',
            self::PERIODO_MENSAL => 'Mensal',
            self::PERIODO_TRIMESTRAL => 'Trimestral',
            self::PERIODO_SEMESTRAL => 'Semestral',
            self::PERIODO_ANUAL => 'Anual',
            self::PERIODO_BIENAL => 'Bienal',
            default => $this->tipoPeriodo,
        };
    }

    /**
     * Retorna label da forma de repasse
     */
    public function getFormaRepasseLabel(): ?string
    {
        if (!$this->formaRepasse) {
            return null;
        }

        return match ($this->formaRepasse) {
            self::FORMA_PIX => 'PIX',
            self::FORMA_TED => 'TED',
            self::FORMA_DEPOSITO => 'Depósito',
            self::FORMA_CHEQUE => 'Cheque',
            self::FORMA_DINHEIRO => 'Dinheiro',
            default => $this->formaRepasse,
        };
    }

    /**
     * Retorna período formatado para exibição
     */
    public function getPeriodoFormatado(): string
    {
        return $this->dataInicio->format('d/m/Y') . ' a ' . $this->dataFim->format('d/m/Y');
    }

    /**
     * Verifica se pode ser editado (só status gerado)
     */
    public function podeEditar(): bool
    {
        return $this->status === self::STATUS_GERADO;
    }

    /**
     * Verifica se pode ser excluído (só status gerado)
     */
    public function podeExcluir(): bool
    {
        return $this->status === self::STATUS_GERADO;
    }

    /**
     * Verifica se pode ser aprovado
     */
    public function podeAprovar(): bool
    {
        return $this->status === self::STATUS_GERADO;
    }

    /**
     * Verifica se pode registrar repasse
     */
    public function podeRegistrarRepasse(): bool
    {
        return $this->status === self::STATUS_APROVADO;
    }

    /**
     * Verifica se pode ser cancelado
     */
    public function podeCancelar(): bool
    {
        return in_array($this->status, [self::STATUS_GERADO, self::STATUS_APROVADO], true);
    }

    /**
     * Retorna os itens do tipo receita
     * @return Collection<int, PrestacoesContasItens>
     */
    public function getReceitas(): Collection
    {
        return $this->itens->filter(fn(PrestacoesContasItens $item) => $item->isReceita());
    }

    /**
     * Retorna os itens do tipo despesa
     * @return Collection<int, PrestacoesContasItens>
     */
    public function getDespesas(): Collection
    {
        return $this->itens->filter(fn(PrestacoesContasItens $item) => $item->isDespesa());
    }

    /**
     * Recalcula os totais a partir dos itens
     */
    public function recalcularTotais(): self
    {
        $totalReceitas = 0.0;
        $totalDespesas = 0.0;
        $totalTaxaAdmin = 0.0;
        $totalRetencaoIr = 0.0;

        foreach ($this->itens as $item) {
            if ($item->isReceita()) {
                $totalReceitas += $item->getValorBrutoFloat();
                $totalTaxaAdmin += $item->getValorTaxaAdminFloat();
                $totalRetencaoIr += $item->getValorRetencaoIrFloat();
            } else {
                $totalDespesas += $item->getValorBrutoFloat();
            }
        }

        $this->totalReceitas = number_format($totalReceitas, 2, '.', '');
        $this->totalDespesas = number_format($totalDespesas, 2, '.', '');
        $this->totalTaxaAdmin = number_format($totalTaxaAdmin, 2, '.', '');
        $this->totalRetencaoIr = number_format($totalRetencaoIr, 2, '.', '');

        // Valor de repasse = Receitas - Taxa Admin - IR - Despesas
        $valorRepasse = $totalReceitas - $totalTaxaAdmin - $totalRetencaoIr - $totalDespesas;
        $this->valorRepasse = number_format($valorRepasse, 2, '.', '');

        return $this;
    }
}
