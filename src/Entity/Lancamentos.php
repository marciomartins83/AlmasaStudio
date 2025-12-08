<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\LancamentosRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Lancamentos - Módulo Contas a Pagar/Receber
 *
 * Entidade unificada para lançamentos financeiros:
 * - tipo 'pagar': Contas a Pagar (despesas)
 * - tipo 'receber': Contas a Receber (receitas)
 */
#[ORM\Entity(repositoryClass: LancamentosRepository::class)]
#[ORM\Table(
    name: 'lancamentos',
    indexes: [
        new ORM\Index(name: 'idx_lancamentos_tipo', columns: ['tipo']),
        new ORM\Index(name: 'idx_lancamentos_vencimento', columns: ['data_vencimento']),
        new ORM\Index(name: 'idx_lancamentos_status', columns: ['status']),
        new ORM\Index(name: 'idx_lancamentos_pessoa_credor', columns: ['id_pessoa_credor']),
        new ORM\Index(name: 'idx_lancamentos_pessoa_pagador', columns: ['id_pessoa_pagador']),
        new ORM\Index(name: 'idx_lancamentos_contrato', columns: ['id_contrato']),
        new ORM\Index(name: 'idx_lancamentos_competencia', columns: ['competencia']),
    ]
)]
#[ORM\HasLifecycleCallbacks]
class Lancamentos
{
    public const TIPO_PAGAR = 'pagar';
    public const TIPO_RECEBER = 'receber';

    public const STATUS_ABERTO = 'aberto';
    public const STATUS_PAGO = 'pago';
    public const STATUS_PAGO_PARCIAL = 'pago_parcial';
    public const STATUS_CANCELADO = 'cancelado';
    public const STATUS_SUSPENSO = 'suspenso';

    public const ORIGEM_MANUAL = 'manual';
    public const ORIGEM_CONTRATO = 'contrato';
    public const ORIGEM_ACORDO = 'acordo';
    public const ORIGEM_IMPORTACAO = 'importacao';

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 10)]
    private string $tipo = self::TIPO_RECEBER;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $numero = null;

    // === DATAS ===

    #[ORM\Column(name: 'data_movimento', type: Types::DATE_MUTABLE)]
    private \DateTimeInterface $dataMovimento;

    #[ORM\Column(name: 'data_vencimento', type: Types::DATE_MUTABLE)]
    private \DateTimeInterface $dataVencimento;

    #[ORM\Column(name: 'data_pagamento', type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dataPagamento = null;

    #[ORM\Column(type: Types::STRING, length: 7, nullable: true)]
    private ?string $competencia = null;

    // === CLASSIFICAÇÃO ===

    #[ORM\ManyToOne(targetEntity: PlanoContas::class, inversedBy: 'lancamentos')]
    #[ORM\JoinColumn(name: 'id_plano_conta', referencedColumnName: 'id', nullable: false)]
    private PlanoContas $planoConta;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $historico = null;

    #[ORM\Column(name: 'centro_custo', type: Types::STRING, length: 20, nullable: true)]
    private ?string $centroCusto = null;

    // === PESSOAS ===

    #[ORM\ManyToOne(targetEntity: Pessoas::class)]
    #[ORM\JoinColumn(name: 'id_pessoa_credor', referencedColumnName: 'idpessoa', nullable: true)]
    private ?Pessoas $pessoaCredor = null;

    #[ORM\ManyToOne(targetEntity: Pessoas::class)]
    #[ORM\JoinColumn(name: 'id_pessoa_pagador', referencedColumnName: 'idpessoa', nullable: true)]
    private ?Pessoas $pessoaPagador = null;

    // === VÍNCULOS LEGADOS (mantidos para compatibilidade) ===

    #[ORM\ManyToOne(targetEntity: Imoveis::class)]
    #[ORM\JoinColumn(name: 'id_imovel', referencedColumnName: 'id', nullable: true)]
    private ?Imoveis $imovel = null;

    #[ORM\ManyToOne(targetEntity: Pessoas::class)]
    #[ORM\JoinColumn(name: 'id_proprietario', referencedColumnName: 'idpessoa', nullable: true)]
    private ?Pessoas $proprietario = null;

    #[ORM\ManyToOne(targetEntity: Pessoas::class)]
    #[ORM\JoinColumn(name: 'id_inquilino', referencedColumnName: 'idpessoa', nullable: true)]
    private ?Pessoas $inquilino = null;

    // === VÍNCULOS NOVOS ===

    #[ORM\ManyToOne(targetEntity: ImoveisContratos::class)]
    #[ORM\JoinColumn(name: 'id_contrato', referencedColumnName: 'id', nullable: true)]
    private ?ImoveisContratos $contrato = null;

    #[ORM\ManyToOne(targetEntity: ContasBancarias::class)]
    #[ORM\JoinColumn(name: 'id_conta_bancaria', referencedColumnName: 'id', nullable: true)]
    private ?ContasBancarias $contaBancaria = null;

    #[ORM\ManyToOne(targetEntity: Boletos::class)]
    #[ORM\JoinColumn(name: 'id_boleto', referencedColumnName: 'id', nullable: true)]
    private ?Boletos $boleto = null;

    // === VALORES ===

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    private string $valor = '0.00';

    #[ORM\Column(name: 'valor_pago', type: Types::DECIMAL, precision: 15, scale: 2, nullable: true)]
    private ?string $valorPago = '0.00';

    #[ORM\Column(name: 'valor_desconto', type: Types::DECIMAL, precision: 15, scale: 2, nullable: true)]
    private ?string $valorDesconto = '0.00';

    #[ORM\Column(name: 'valor_juros', type: Types::DECIMAL, precision: 15, scale: 2, nullable: true)]
    private ?string $valorJuros = '0.00';

    #[ORM\Column(name: 'valor_multa', type: Types::DECIMAL, precision: 15, scale: 2, nullable: true)]
    private ?string $valorMulta = '0.00';

    // === RETENÇÕES FISCAIS ===

    #[ORM\Column(name: 'reter_inss', type: Types::BOOLEAN, nullable: true)]
    private ?bool $reterInss = false;

    #[ORM\Column(name: 'perc_inss', type: Types::DECIMAL, precision: 5, scale: 2, nullable: true)]
    private ?string $percInss = null;

    #[ORM\Column(name: 'valor_inss', type: Types::DECIMAL, precision: 15, scale: 2, nullable: true)]
    private ?string $valorInss = null;

    #[ORM\Column(name: 'reter_iss', type: Types::BOOLEAN, nullable: true)]
    private ?bool $reterIss = false;

    #[ORM\Column(name: 'perc_iss', type: Types::DECIMAL, precision: 5, scale: 2, nullable: true)]
    private ?string $percIss = null;

    #[ORM\Column(name: 'valor_iss', type: Types::DECIMAL, precision: 15, scale: 2, nullable: true)]
    private ?string $valorIss = null;

    // === PAGAMENTO ===

    #[ORM\Column(name: 'forma_pagamento', type: Types::STRING, length: 20, nullable: true)]
    private ?string $formaPagamento = null;

    // === DOCUMENTOS ===

    #[ORM\Column(name: 'tipo_documento', type: Types::STRING, length: 20, nullable: true)]
    private ?string $tipoDocumento = null;

    #[ORM\Column(name: 'numero_documento', type: Types::STRING, length: 50, nullable: true)]
    private ?string $numeroDocumento = null;

    #[ORM\Column(name: 'numero_recibo', type: Types::INTEGER, nullable: true)]
    private ?int $numeroRecibo = null;

    // === CONTROLE ===

    #[ORM\Column(type: Types::STRING, length: 15, nullable: true)]
    private ?string $status = self::STATUS_ABERTO;

    #[ORM\Column(name: 'suspenso_motivo', type: Types::STRING, length: 200, nullable: true)]
    private ?string $suspensoMotivo = null;

    #[ORM\Column(type: Types::STRING, length: 20, nullable: true)]
    private ?string $origem = self::ORIGEM_MANUAL;

    // === JURÍDICO ===

    #[ORM\Column(name: 'id_processo', type: Types::INTEGER, nullable: true)]
    private ?int $idProcesso = null;

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

    public function __construct()
    {
        $this->dataMovimento = new \DateTime();
        $this->dataVencimento = new \DateTime();
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
        $this->competencia = (new \DateTime())->format('Y-m');
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

    public function getTipo(): string
    {
        return $this->tipo;
    }

    public function setTipo(string $tipo): self
    {
        $this->tipo = $tipo;
        return $this;
    }

    public function isPagar(): bool
    {
        return $this->tipo === self::TIPO_PAGAR;
    }

    public function isReceber(): bool
    {
        return $this->tipo === self::TIPO_RECEBER;
    }

    public function getNumero(): ?int
    {
        return $this->numero;
    }

    public function setNumero(?int $numero): self
    {
        $this->numero = $numero;
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

    public function getDataVencimento(): \DateTimeInterface
    {
        return $this->dataVencimento;
    }

    public function setDataVencimento(\DateTimeInterface $dataVencimento): self
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

    public function getCompetencia(): ?string
    {
        return $this->competencia;
    }

    public function setCompetencia(?string $competencia): self
    {
        $this->competencia = $competencia;
        return $this;
    }

    public function getPlanoConta(): PlanoContas
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

    public function getCentroCusto(): ?string
    {
        return $this->centroCusto;
    }

    public function setCentroCusto(?string $centroCusto): self
    {
        $this->centroCusto = $centroCusto;
        return $this;
    }

    public function getPessoaCredor(): ?Pessoas
    {
        return $this->pessoaCredor;
    }

    public function setPessoaCredor(?Pessoas $pessoaCredor): self
    {
        $this->pessoaCredor = $pessoaCredor;
        return $this;
    }

    public function getPessoaPagador(): ?Pessoas
    {
        return $this->pessoaPagador;
    }

    public function setPessoaPagador(?Pessoas $pessoaPagador): self
    {
        $this->pessoaPagador = $pessoaPagador;
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

    public function getProprietario(): ?Pessoas
    {
        return $this->proprietario;
    }

    public function setProprietario(?Pessoas $proprietario): self
    {
        $this->proprietario = $proprietario;
        return $this;
    }

    public function getInquilino(): ?Pessoas
    {
        return $this->inquilino;
    }

    public function setInquilino(?Pessoas $inquilino): self
    {
        $this->inquilino = $inquilino;
        return $this;
    }

    public function getContrato(): ?ImoveisContratos
    {
        return $this->contrato;
    }

    public function setContrato(?ImoveisContratos $contrato): self
    {
        $this->contrato = $contrato;
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

    public function getBoleto(): ?Boletos
    {
        return $this->boleto;
    }

    public function setBoleto(?Boletos $boleto): self
    {
        $this->boleto = $boleto;
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

    public function getValorPago(): ?string
    {
        return $this->valorPago;
    }

    public function setValorPago(?string $valorPago): self
    {
        $this->valorPago = $valorPago;
        return $this;
    }

    public function getValorPagoFloat(): float
    {
        return (float) ($this->valorPago ?? '0');
    }

    public function getValorDesconto(): ?string
    {
        return $this->valorDesconto;
    }

    public function setValorDesconto(?string $valorDesconto): self
    {
        $this->valorDesconto = $valorDesconto;
        return $this;
    }

    public function getValorDescontoFloat(): float
    {
        return (float) ($this->valorDesconto ?? '0');
    }

    public function getValorJuros(): ?string
    {
        return $this->valorJuros;
    }

    public function setValorJuros(?string $valorJuros): self
    {
        $this->valorJuros = $valorJuros;
        return $this;
    }

    public function getValorJurosFloat(): float
    {
        return (float) ($this->valorJuros ?? '0');
    }

    public function getValorMulta(): ?string
    {
        return $this->valorMulta;
    }

    public function setValorMulta(?string $valorMulta): self
    {
        $this->valorMulta = $valorMulta;
        return $this;
    }

    public function getValorMultaFloat(): float
    {
        return (float) ($this->valorMulta ?? '0');
    }

    public function isReterInss(): ?bool
    {
        return $this->reterInss;
    }

    public function setReterInss(?bool $reterInss): self
    {
        $this->reterInss = $reterInss;
        return $this;
    }

    public function getPercInss(): ?string
    {
        return $this->percInss;
    }

    public function setPercInss(?string $percInss): self
    {
        $this->percInss = $percInss;
        return $this;
    }

    public function getValorInss(): ?string
    {
        return $this->valorInss;
    }

    public function setValorInss(?string $valorInss): self
    {
        $this->valorInss = $valorInss;
        return $this;
    }

    public function isReterIss(): ?bool
    {
        return $this->reterIss;
    }

    public function setReterIss(?bool $reterIss): self
    {
        $this->reterIss = $reterIss;
        return $this;
    }

    public function getPercIss(): ?string
    {
        return $this->percIss;
    }

    public function setPercIss(?string $percIss): self
    {
        $this->percIss = $percIss;
        return $this;
    }

    public function getValorIss(): ?string
    {
        return $this->valorIss;
    }

    public function setValorIss(?string $valorIss): self
    {
        $this->valorIss = $valorIss;
        return $this;
    }

    public function getFormaPagamento(): ?string
    {
        return $this->formaPagamento;
    }

    public function setFormaPagamento(?string $formaPagamento): self
    {
        $this->formaPagamento = $formaPagamento;
        return $this;
    }

    public function getTipoDocumento(): ?string
    {
        return $this->tipoDocumento;
    }

    public function setTipoDocumento(?string $tipoDocumento): self
    {
        $this->tipoDocumento = $tipoDocumento;
        return $this;
    }

    public function getNumeroDocumento(): ?string
    {
        return $this->numeroDocumento;
    }

    public function setNumeroDocumento(?string $numeroDocumento): self
    {
        $this->numeroDocumento = $numeroDocumento;
        return $this;
    }

    public function getNumeroRecibo(): ?int
    {
        return $this->numeroRecibo;
    }

    public function setNumeroRecibo(?int $numeroRecibo): self
    {
        $this->numeroRecibo = $numeroRecibo;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function isAberto(): bool
    {
        return $this->status === self::STATUS_ABERTO;
    }

    public function isPago(): bool
    {
        return $this->status === self::STATUS_PAGO;
    }

    public function isPagoParcial(): bool
    {
        return $this->status === self::STATUS_PAGO_PARCIAL;
    }

    public function isCancelado(): bool
    {
        return $this->status === self::STATUS_CANCELADO;
    }

    public function isSuspenso(): bool
    {
        return $this->status === self::STATUS_SUSPENSO;
    }

    public function getSuspensoMotivo(): ?string
    {
        return $this->suspensoMotivo;
    }

    public function setSuspensoMotivo(?string $suspensoMotivo): self
    {
        $this->suspensoMotivo = $suspensoMotivo;
        return $this;
    }

    public function getOrigem(): ?string
    {
        return $this->origem;
    }

    public function setOrigem(?string $origem): self
    {
        $this->origem = $origem;
        return $this;
    }

    public function getIdProcesso(): ?int
    {
        return $this->idProcesso;
    }

    public function setIdProcesso(?int $idProcesso): self
    {
        $this->idProcesso = $idProcesso;
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

    // === MÉTODOS AUXILIARES ===

    /**
     * Calcula valor líquido considerando descontos, juros, multa e retenções
     */
    public function getValorLiquido(): float
    {
        $valor = $this->getValorFloat();
        $desconto = $this->getValorDescontoFloat();
        $juros = $this->getValorJurosFloat();
        $multa = $this->getValorMultaFloat();
        $inss = (float) ($this->valorInss ?? '0');
        $iss = (float) ($this->valorIss ?? '0');

        return $valor - $desconto + $juros + $multa - $inss - $iss;
    }

    /**
     * Calcula saldo em aberto
     */
    public function getSaldo(): float
    {
        return $this->getValorLiquido() - $this->getValorPagoFloat();
    }

    /**
     * Verifica se está vencido
     */
    public function isVencido(): bool
    {
        if ($this->isPago() || $this->isCancelado()) {
            return false;
        }

        return $this->dataVencimento < new \DateTime('today');
    }

    /**
     * Retorna dias de atraso (0 se não vencido)
     */
    public function getDiasAtraso(): int
    {
        if (!$this->isVencido()) {
            return 0;
        }

        $hoje = new \DateTime('today');
        $diff = $hoje->diff($this->dataVencimento);
        return $diff->days;
    }

    /**
     * Atualiza status baseado no valor pago
     */
    public function atualizarStatus(): void
    {
        if ($this->isCancelado() || $this->isSuspenso()) {
            return;
        }

        $valorPago = $this->getValorPagoFloat();
        $valorLiquido = $this->getValorLiquido();

        if ($valorPago <= 0) {
            $this->status = self::STATUS_ABERTO;
        } elseif ($valorPago >= $valorLiquido) {
            $this->status = self::STATUS_PAGO;
        } else {
            $this->status = self::STATUS_PAGO_PARCIAL;
        }
    }

    /**
     * Retorna label do tipo para exibição
     */
    public function getTipoLabel(): string
    {
        return match ($this->tipo) {
            self::TIPO_PAGAR => 'Pagar',
            self::TIPO_RECEBER => 'Receber',
            default => $this->tipo,
        };
    }

    /**
     * Retorna label do status para exibição
     */
    public function getStatusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_ABERTO => 'Aberto',
            self::STATUS_PAGO => 'Pago',
            self::STATUS_PAGO_PARCIAL => 'Pago Parcial',
            self::STATUS_CANCELADO => 'Cancelado',
            self::STATUS_SUSPENSO => 'Suspenso',
            default => $this->status ?? 'Desconhecido',
        };
    }

    /**
     * Retorna classe CSS do badge de status
     */
    public function getStatusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_ABERTO => 'warning',
            self::STATUS_PAGO => 'success',
            self::STATUS_PAGO_PARCIAL => 'info',
            self::STATUS_CANCELADO => 'danger',
            self::STATUS_SUSPENSO => 'secondary',
            default => 'light',
        };
    }

    /**
     * Retorna classe CSS do badge de tipo
     */
    public function getTipoBadgeClass(): string
    {
        return match ($this->tipo) {
            self::TIPO_PAGAR => 'danger',
            self::TIPO_RECEBER => 'success',
            default => 'light',
        };
    }
}
