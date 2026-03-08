<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\AlmasaLancamentoRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AlmasaLancamentoRepository::class)]
#[ORM\Table(
    name: 'almasa_lancamentos',
    indexes: [
        new ORM\Index(name: 'idx_almasa_lanc_tipo', columns: ['tipo']),
        new ORM\Index(name: 'idx_almasa_lanc_status', columns: ['status']),
        new ORM\Index(name: 'idx_almasa_lanc_competencia', columns: ['data_competencia']),
        new ORM\Index(name: 'idx_almasa_lanc_vencimento', columns: ['data_vencimento']),
        new ORM\Index(name: 'idx_almasa_lanc_plano', columns: ['id_almasa_plano_conta']),
        new ORM\Index(name: 'idx_almasa_lanc_origem', columns: ['id_lancamento_origem']),
        new ORM\Index(name: 'idx_almasa_lanc_origem_fin', columns: ['id_lancamento_financeiro_origem']),
    ]
)]
#[ORM\HasLifecycleCallbacks]
class AlmasaLancamento
{
    public const TIPO_RECEITA = 'receita';
    public const TIPO_DESPESA = 'despesa';

    public const STATUS_ABERTO = 'aberto';
    public const STATUS_PAGO = 'pago';
    public const STATUS_CANCELADO = 'cancelado';

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: AlmasaPlanoContas::class, inversedBy: 'almasaLancamentos')]
    #[ORM\JoinColumn(name: 'id_almasa_plano_conta', referencedColumnName: 'id', nullable: false, onDelete: 'RESTRICT')]
    private ?AlmasaPlanoContas $almasaPlanoConta = null;

    #[ORM\Column(type: Types::STRING, length: 10)]
    private string $tipo = self::TIPO_RECEITA;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $descricao = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    private string $valor = '0.00';

    #[ORM\Column(name: 'data_competencia', type: Types::DATE_MUTABLE)]
    private \DateTimeInterface $dataCompetencia;

    #[ORM\Column(name: 'data_vencimento', type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dataVencimento = null;

    #[ORM\Column(name: 'data_pagamento', type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dataPagamento = null;

    #[ORM\Column(type: Types::STRING, length: 20)]
    private string $status = self::STATUS_ABERTO;

    #[ORM\ManyToOne(targetEntity: Pessoas::class)]
    #[ORM\JoinColumn(name: 'id_pessoa', referencedColumnName: 'idpessoa', nullable: true, onDelete: 'SET NULL')]
    private ?Pessoas $pessoa = null;

    #[ORM\ManyToOne(targetEntity: ContasBancarias::class)]
    #[ORM\JoinColumn(name: 'id_conta_bancaria', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?ContasBancarias $contaBancaria = null;

    #[ORM\ManyToOne(targetEntity: Lancamentos::class)]
    #[ORM\JoinColumn(name: 'id_lancamento_origem', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Lancamentos $lancamentoOrigem = null;

    #[ORM\ManyToOne(targetEntity: LancamentosFinanceiros::class)]
    #[ORM\JoinColumn(name: 'id_lancamento_financeiro_origem', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?LancamentosFinanceiros $lancamentoFinanceiroOrigem = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $observacao = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $updatedAt;

    public function __construct()
    {
        $this->dataCompetencia = new \DateTime();
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
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

    public function getAlmasaPlanoConta(): ?AlmasaPlanoContas
    {
        return $this->almasaPlanoConta;
    }

    public function setAlmasaPlanoConta(?AlmasaPlanoContas $almasaPlanoConta): self
    {
        $this->almasaPlanoConta = $almasaPlanoConta;
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

    public function getDescricao(): ?string
    {
        return $this->descricao;
    }

    public function setDescricao(?string $descricao): self
    {
        $this->descricao = $descricao;
        return $this;
    }

    public function getValor(): string
    {
        return $this->valor;
    }

    public function setValor(string $valor): self
    {
        $this->valor = $valor;
        return $this;
    }

    public function getValorFloat(): float
    {
        return (float) $this->valor;
    }

    public function getDataCompetencia(): \DateTimeInterface
    {
        return $this->dataCompetencia;
    }

    public function setDataCompetencia(\DateTimeInterface $dataCompetencia): self
    {
        $this->dataCompetencia = $dataCompetencia;
        return $this;
    }

    public function getDataVencimento(): ?\DateTimeInterface
    {
        return $this->dataVencimento;
    }

    public function setDataVencimento(?\DateTimeInterface $dataVencimento): self
    {
        $this->dataVencimento = $dataVencimento;
        return $this;
    }

    public function getDataPagamento(): ?\DateTimeInterface
    {
        return $this->dataPagamento;
    }

    public function setDataPagamento(?\DateTimeInterface $dataPagamento): self
    {
        $this->dataPagamento = $dataPagamento;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getPessoa(): ?Pessoas
    {
        return $this->pessoa;
    }

    public function setPessoa(?Pessoas $pessoa): self
    {
        $this->pessoa = $pessoa;
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

    public function getLancamentoOrigem(): ?Lancamentos
    {
        return $this->lancamentoOrigem;
    }

    public function setLancamentoOrigem(?Lancamentos $lancamentoOrigem): self
    {
        $this->lancamentoOrigem = $lancamentoOrigem;
        return $this;
    }

    public function getLancamentoFinanceiroOrigem(): ?LancamentosFinanceiros
    {
        return $this->lancamentoFinanceiroOrigem;
    }

    public function setLancamentoFinanceiroOrigem(?LancamentosFinanceiros $lancamentoFinanceiroOrigem): self
    {
        $this->lancamentoFinanceiroOrigem = $lancamentoFinanceiroOrigem;
        return $this;
    }

    public function getObservacao(): ?string
    {
        return $this->observacao;
    }

    public function setObservacao(?string $observacao): self
    {
        $this->observacao = $observacao;
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

    // === METODOS AUXILIARES ===

    public function isReceita(): bool
    {
        return $this->tipo === self::TIPO_RECEITA;
    }

    public function isDespesa(): bool
    {
        return $this->tipo === self::TIPO_DESPESA;
    }

    public function isAberto(): bool
    {
        return $this->status === self::STATUS_ABERTO;
    }

    public function isPago(): bool
    {
        return $this->status === self::STATUS_PAGO;
    }

    public function isCancelado(): bool
    {
        return $this->status === self::STATUS_CANCELADO;
    }

    public function getStatusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_ABERTO => 'Aberto',
            self::STATUS_PAGO => 'Pago',
            self::STATUS_CANCELADO => 'Cancelado',
            default => $this->status,
        };
    }

    public function getStatusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_ABERTO => 'warning',
            self::STATUS_PAGO => 'success',
            self::STATUS_CANCELADO => 'danger',
            default => 'light',
        };
    }
}
