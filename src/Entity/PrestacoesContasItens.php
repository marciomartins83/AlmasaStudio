<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\PrestacoesContasItensRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * PrestacoesContasItens - Itens detalhados da Prestação de Contas
 *
 * Cada item representa uma receita ou despesa do período,
 * podendo vir da Ficha Financeira ou dos Lançamentos (Contas a Pagar/Receber)
 */
#[ORM\Entity(repositoryClass: PrestacoesContasItensRepository::class)]
#[ORM\Table(
    name: 'prestacoes_contas_itens',
    indexes: [
        new ORM\Index(name: 'idx_prestacoes_itens_prestacao', columns: ['id_prestacao_conta']),
        new ORM\Index(name: 'idx_prestacoes_itens_tipo', columns: ['tipo']),
        new ORM\Index(name: 'idx_prestacoes_itens_imovel', columns: ['id_imovel']),
    ]
)]
#[ORM\HasLifecycleCallbacks]
class PrestacoesContasItens
{
    // Constantes de Tipo
    public const TIPO_RECEITA = 'receita';
    public const TIPO_DESPESA = 'despesa';

    // Constantes de Origem
    public const ORIGEM_FICHA_FINANCEIRA = 'ficha_financeira';
    public const ORIGEM_LANCAMENTO_PAGAR = 'lancamento_pagar';
    public const ORIGEM_LANCAMENTO_RECEBER = 'lancamento_receber';

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    // === RELACIONAMENTO COM CABEÇALHO ===

    #[ORM\ManyToOne(targetEntity: PrestacoesContas::class, inversedBy: 'itens')]
    #[ORM\JoinColumn(name: 'id_prestacao_conta', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?PrestacoesContas $prestacaoConta = null;

    // === ORIGEM ===

    #[ORM\Column(type: Types::STRING, length: 30)]
    private string $origem;

    #[ORM\ManyToOne(targetEntity: LancamentosFinanceiros::class)]
    #[ORM\JoinColumn(name: 'id_lancamento_financeiro', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?LancamentosFinanceiros $lancamentoFinanceiro = null;

    #[ORM\ManyToOne(targetEntity: Lancamentos::class)]
    #[ORM\JoinColumn(name: 'id_lancamento', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Lancamentos $lancamento = null;

    // === DADOS DO ITEM ===

    #[ORM\Column(name: 'data_movimento', type: Types::DATE_MUTABLE)]
    private \DateTimeInterface $dataMovimento;

    #[ORM\Column(name: 'data_vencimento', type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dataVencimento = null;

    #[ORM\Column(name: 'data_pagamento', type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dataPagamento = null;

    // === CLASSIFICAÇÃO ===

    #[ORM\Column(type: Types::STRING, length: 10)]
    private string $tipo;

    #[ORM\ManyToOne(targetEntity: PlanoContas::class)]
    #[ORM\JoinColumn(name: 'id_plano_conta', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?PlanoContas $planoConta = null;

    #[ORM\Column(type: Types::STRING, length: 200, nullable: true)]
    private ?string $historico = null;

    // === IMÓVEL ===

    #[ORM\ManyToOne(targetEntity: Imoveis::class)]
    #[ORM\JoinColumn(name: 'id_imovel', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Imoveis $imovel = null;

    // === VALORES ===

    #[ORM\Column(name: 'valor_bruto', type: Types::DECIMAL, precision: 15, scale: 2)]
    private string $valorBruto;

    #[ORM\Column(name: 'valor_taxa_admin', type: Types::DECIMAL, precision: 15, scale: 2, nullable: true)]
    private ?string $valorTaxaAdmin = '0.00';

    #[ORM\Column(name: 'valor_retencao_ir', type: Types::DECIMAL, precision: 15, scale: 2, nullable: true)]
    private ?string $valorRetencaoIr = '0.00';

    #[ORM\Column(name: 'valor_liquido', type: Types::DECIMAL, precision: 15, scale: 2)]
    private string $valorLiquido;

    // === AUDITORIA ===

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->dataMovimento = new \DateTime();
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new \DateTime();
    }

    // === GETTERS E SETTERS ===

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPrestacaoConta(): ?PrestacoesContas
    {
        return $this->prestacaoConta;
    }

    public function setPrestacaoConta(?PrestacoesContas $prestacaoConta): self
    {
        $this->prestacaoConta = $prestacaoConta;
        return $this;
    }

    public function getOrigem(): string
    {
        return $this->origem;
    }

    public function setOrigem(string $origem): self
    {
        $this->origem = $origem;
        return $this;
    }

    public function getLancamentoFinanceiro(): ?LancamentosFinanceiros
    {
        return $this->lancamentoFinanceiro;
    }

    public function setLancamentoFinanceiro(?LancamentosFinanceiros $lancamentoFinanceiro): self
    {
        $this->lancamentoFinanceiro = $lancamentoFinanceiro;
        return $this;
    }

    public function getLancamento(): ?Lancamentos
    {
        return $this->lancamento;
    }

    public function setLancamento(?Lancamentos $lancamento): self
    {
        $this->lancamento = $lancamento;
        return $this;
    }

    public function getDataMovimento(): \DateTimeInterface
    {
        return $this->dataMovimento;
    }

    public function setDataMovimento(\DateTimeInterface $dataMovimento): self
    {
        $this->dataMovimento = $dataMovimento;
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

    public function getTipo(): string
    {
        return $this->tipo;
    }

    public function setTipo(string $tipo): self
    {
        $this->tipo = $tipo;
        return $this;
    }

    public function isReceita(): bool
    {
        return $this->tipo === self::TIPO_RECEITA;
    }

    public function isDespesa(): bool
    {
        return $this->tipo === self::TIPO_DESPESA;
    }

    public function getPlanoConta(): ?PlanoContas
    {
        return $this->planoConta;
    }

    public function setPlanoConta(?PlanoContas $planoConta): self
    {
        $this->planoConta = $planoConta;
        return $this;
    }

    public function getHistorico(): ?string
    {
        return $this->historico;
    }

    public function setHistorico(?string $historico): self
    {
        $this->historico = $historico;
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

    public function getValorBruto(): string
    {
        return $this->valorBruto;
    }

    public function setValorBruto(string $valorBruto): self
    {
        $this->valorBruto = $valorBruto;
        return $this;
    }

    public function getValorBrutoFloat(): float
    {
        return (float) $this->valorBruto;
    }

    public function getValorTaxaAdmin(): string
    {
        return $this->valorTaxaAdmin ?? '0.00';
    }

    public function setValorTaxaAdmin(?string $valorTaxaAdmin): self
    {
        $this->valorTaxaAdmin = $valorTaxaAdmin;
        return $this;
    }

    public function getValorTaxaAdminFloat(): float
    {
        return (float) ($this->valorTaxaAdmin ?? '0.00');
    }

    public function getValorRetencaoIr(): string
    {
        return $this->valorRetencaoIr ?? '0.00';
    }

    public function setValorRetencaoIr(?string $valorRetencaoIr): self
    {
        $this->valorRetencaoIr = $valorRetencaoIr;
        return $this;
    }

    public function getValorRetencaoIrFloat(): float
    {
        return (float) ($this->valorRetencaoIr ?? '0.00');
    }

    public function getValorLiquido(): string
    {
        return $this->valorLiquido;
    }

    public function setValorLiquido(string $valorLiquido): self
    {
        $this->valorLiquido = $valorLiquido;
        return $this;
    }

    public function getValorLiquidoFloat(): float
    {
        return (float) $this->valorLiquido;
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

    // === MÉTODOS AUXILIARES ===

    /**
     * Retorna label do tipo
     */
    public function getTipoLabel(): string
    {
        return match ($this->tipo) {
            self::TIPO_RECEITA => 'Receita',
            self::TIPO_DESPESA => 'Despesa',
            default => $this->tipo,
        };
    }

    /**
     * Retorna classe CSS do badge de tipo
     */
    public function getTipoBadgeClass(): string
    {
        return match ($this->tipo) {
            self::TIPO_RECEITA => 'success',
            self::TIPO_DESPESA => 'danger',
            default => 'secondary',
        };
    }

    /**
     * Retorna label da origem
     */
    public function getOrigemLabel(): string
    {
        return match ($this->origem) {
            self::ORIGEM_FICHA_FINANCEIRA => 'Ficha Financeira',
            self::ORIGEM_LANCAMENTO_PAGAR => 'Conta a Pagar',
            self::ORIGEM_LANCAMENTO_RECEBER => 'Conta a Receber',
            default => $this->origem,
        };
    }

    /**
     * Calcula e define o valor líquido
     */
    public function calcularValorLiquido(): self
    {
        if ($this->isReceita()) {
            // Receita: Bruto - Taxa Admin - IR
            $liquido = $this->getValorBrutoFloat()
                     - $this->getValorTaxaAdminFloat()
                     - $this->getValorRetencaoIrFloat();
        } else {
            // Despesa: Bruto (100% do proprietário)
            $liquido = $this->getValorBrutoFloat();
        }

        $this->valorLiquido = number_format($liquido, 2, '.', '');
        return $this;
    }
}
