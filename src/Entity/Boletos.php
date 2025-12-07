<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\BoletosRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BoletosRepository::class)]
#[ORM\Table(
    name: 'boletos',
    indexes: [
        new ORM\Index(name: 'idx_boletos_config', columns: ['configuracao_api_id']),
        new ORM\Index(name: 'idx_boletos_lancamento', columns: ['lancamento_financeiro_id']),
        new ORM\Index(name: 'idx_boletos_pagador', columns: ['pessoa_pagador_id']),
        new ORM\Index(name: 'idx_boletos_imovel', columns: ['imovel_id']),
        new ORM\Index(name: 'idx_boletos_status', columns: ['status']),
        new ORM\Index(name: 'idx_boletos_vencimento', columns: ['data_vencimento']),
        new ORM\Index(name: 'idx_boletos_nosso_numero', columns: ['nosso_numero']),
    ]
)]
#[ORM\HasLifecycleCallbacks]
class Boletos
{
    // Constantes de Status
    public const STATUS_PENDENTE = 'PENDENTE';
    public const STATUS_REGISTRADO = 'REGISTRADO';
    public const STATUS_PAGO = 'PAGO';
    public const STATUS_VENCIDO = 'VENCIDO';
    public const STATUS_BAIXADO = 'BAIXADO';
    public const STATUS_PROTESTADO = 'PROTESTADO';
    public const STATUS_ERRO = 'ERRO';

    // Constantes de Tipo de Desconto
    public const DESCONTO_ISENTO = 'ISENTO';
    public const DESCONTO_VALOR_DATA_FIXA = 'VALOR_DATA_FIXA';
    public const DESCONTO_PERCENTUAL_DATA_FIXA = 'PERCENTUAL_DATA_FIXA';

    // Constantes de Tipo de Juros
    public const JUROS_ISENTO = 'ISENTO';
    public const JUROS_VALOR_DIA = 'VALOR_DIA';
    public const JUROS_PERCENTUAL_MES = 'PERCENTUAL_MES';

    // Constantes de Tipo de Multa
    public const MULTA_ISENTO = 'ISENTO';
    public const MULTA_VALOR_FIXO = 'VALOR_FIXO';
    public const MULTA_PERCENTUAL = 'PERCENTUAL';

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    // === RELACIONAMENTOS ===

    #[ORM\ManyToOne(targetEntity: ConfiguracoesApiBanco::class)]
    #[ORM\JoinColumn(name: 'configuracao_api_id', referencedColumnName: 'id', nullable: false, onDelete: 'RESTRICT')]
    private ?ConfiguracoesApiBanco $configuracaoApi = null;

    #[ORM\ManyToOne(targetEntity: LancamentosFinanceiros::class)]
    #[ORM\JoinColumn(name: 'lancamento_financeiro_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?LancamentosFinanceiros $lancamentoFinanceiro = null;

    #[ORM\ManyToOne(targetEntity: Pessoas::class)]
    #[ORM\JoinColumn(name: 'pessoa_pagador_id', referencedColumnName: 'idpessoa', nullable: false, onDelete: 'RESTRICT')]
    private ?Pessoas $pessoaPagador = null;

    #[ORM\ManyToOne(targetEntity: Imoveis::class)]
    #[ORM\JoinColumn(name: 'imovel_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Imoveis $imovel = null;

    /**
     * @var Collection<int, BoletosLogApi>
     */
    #[ORM\OneToMany(targetEntity: BoletosLogApi::class, mappedBy: 'boleto', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['createdAt' => 'DESC'])]
    private Collection $logs;

    // === IDENTIFICAÇÃO ===

    #[ORM\Column(name: 'nosso_numero', type: Types::STRING, length: 20)]
    private string $nossoNumero;

    #[ORM\Column(name: 'seu_numero', type: Types::STRING, length: 15, nullable: true)]
    private ?string $seuNumero = null;

    // === VALORES ===

    #[ORM\Column(name: 'valor_nominal', type: Types::DECIMAL, precision: 15, scale: 2)]
    private string $valorNominal;

    #[ORM\Column(name: 'valor_desconto', type: Types::DECIMAL, precision: 15, scale: 2, nullable: true)]
    private ?string $valorDesconto = '0.00';

    #[ORM\Column(name: 'valor_multa', type: Types::DECIMAL, precision: 15, scale: 2, nullable: true)]
    private ?string $valorMulta = '0.00';

    #[ORM\Column(name: 'valor_juros_dia', type: Types::DECIMAL, precision: 15, scale: 2, nullable: true)]
    private ?string $valorJurosDia = '0.00';

    #[ORM\Column(name: 'valor_abatimento', type: Types::DECIMAL, precision: 15, scale: 2, nullable: true)]
    private ?string $valorAbatimento = '0.00';

    // === DATAS ===

    #[ORM\Column(name: 'data_emissao', type: Types::DATE_MUTABLE)]
    private \DateTimeInterface $dataEmissao;

    #[ORM\Column(name: 'data_vencimento', type: Types::DATE_MUTABLE)]
    private \DateTimeInterface $dataVencimento;

    #[ORM\Column(name: 'data_limite_pagamento', type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dataLimitePagamento = null;

    // === DESCONTO ===

    #[ORM\Column(name: 'tipo_desconto', type: Types::STRING, length: 20, nullable: true)]
    private ?string $tipoDesconto = self::DESCONTO_ISENTO;

    #[ORM\Column(name: 'data_desconto', type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dataDesconto = null;

    // === JUROS E MULTA ===

    #[ORM\Column(name: 'tipo_juros', type: Types::STRING, length: 20, nullable: true)]
    private ?string $tipoJuros = self::JUROS_ISENTO;

    #[ORM\Column(name: 'tipo_multa', type: Types::STRING, length: 20, nullable: true)]
    private ?string $tipoMulta = self::MULTA_ISENTO;

    #[ORM\Column(name: 'data_multa', type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dataMulta = null;

    // === DADOS RETORNADOS PELA API ===

    #[ORM\Column(name: 'codigo_barras', type: Types::STRING, length: 44, nullable: true)]
    private ?string $codigoBarras = null;

    #[ORM\Column(name: 'linha_digitavel', type: Types::STRING, length: 47, nullable: true)]
    private ?string $linhaDigitavel = null;

    #[ORM\Column(name: 'txid_pix', type: Types::STRING, length: 35, nullable: true)]
    private ?string $txidPix = null;

    #[ORM\Column(name: 'qrcode_pix', type: Types::TEXT, nullable: true)]
    private ?string $qrcodePix = null;

    // === STATUS ===

    #[ORM\Column(type: Types::STRING, length: 30, options: ['default' => 'PENDENTE'])]
    private string $status = self::STATUS_PENDENTE;

    // === RESPOSTA DA API ===

    #[ORM\Column(name: 'id_titulo_banco', type: Types::STRING, length: 50, nullable: true)]
    private ?string $idTituloBanco = null;

    #[ORM\Column(name: 'convenio_banco', type: Types::STRING, length: 20, nullable: true)]
    private ?string $convenioBanco = null;

    // === MENSAGENS ===

    #[ORM\Column(name: 'mensagem_pagador', type: Types::TEXT, nullable: true)]
    private ?string $mensagemPagador = null;

    // === CONTROLE ===

    #[ORM\Column(name: 'tentativas_registro', type: Types::INTEGER, nullable: true)]
    private ?int $tentativasRegistro = 0;

    #[ORM\Column(name: 'ultimo_erro', type: Types::TEXT, nullable: true)]
    private ?string $ultimoErro = null;

    #[ORM\Column(name: 'data_registro', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dataRegistro = null;

    #[ORM\Column(name: 'data_pagamento', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dataPagamento = null;

    #[ORM\Column(name: 'valor_pago', type: Types::DECIMAL, precision: 15, scale: 2, nullable: true)]
    private ?string $valorPago = null;

    #[ORM\Column(name: 'data_baixa', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dataBaixa = null;

    #[ORM\Column(name: 'motivo_baixa', type: Types::STRING, length: 100, nullable: true)]
    private ?string $motivoBaixa = null;

    // === TIMESTAMPS ===

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $updatedAt;

    public function __construct()
    {
        $this->logs = new ArrayCollection();
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
        $this->dataEmissao = new \DateTime();
    }

    // === GETTERS E SETTERS ===

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getConfiguracaoApi(): ?ConfiguracoesApiBanco
    {
        return $this->configuracaoApi;
    }

    public function setConfiguracaoApi(?ConfiguracoesApiBanco $configuracaoApi): self
    {
        $this->configuracaoApi = $configuracaoApi;
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

    /**
     * @return Collection<int, BoletosLogApi>
     */
    public function getLogs(): Collection
    {
        return $this->logs;
    }

    public function addLog(BoletosLogApi $log): self
    {
        if (!$this->logs->contains($log)) {
            $this->logs->add($log);
            $log->setBoleto($this);
        }
        return $this;
    }

    public function removeLog(BoletosLogApi $log): self
    {
        if ($this->logs->removeElement($log)) {
            if ($log->getBoleto() === $this) {
                $log->setBoleto(null);
            }
        }
        return $this;
    }

    public function getNossoNumero(): string
    {
        return $this->nossoNumero;
    }

    public function setNossoNumero(string $nossoNumero): self
    {
        $this->nossoNumero = $nossoNumero;
        return $this;
    }

    public function getSeuNumero(): ?string
    {
        return $this->seuNumero;
    }

    public function setSeuNumero(?string $seuNumero): self
    {
        $this->seuNumero = $seuNumero;
        return $this;
    }

    public function getValorNominal(): string
    {
        return $this->valorNominal;
    }

    public function setValorNominal(string $valorNominal): self
    {
        $this->valorNominal = $valorNominal;
        return $this;
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

    public function getValorMulta(): ?string
    {
        return $this->valorMulta;
    }

    public function setValorMulta(?string $valorMulta): self
    {
        $this->valorMulta = $valorMulta;
        return $this;
    }

    public function getValorJurosDia(): ?string
    {
        return $this->valorJurosDia;
    }

    public function setValorJurosDia(?string $valorJurosDia): self
    {
        $this->valorJurosDia = $valorJurosDia;
        return $this;
    }

    public function getValorAbatimento(): ?string
    {
        return $this->valorAbatimento;
    }

    public function setValorAbatimento(?string $valorAbatimento): self
    {
        $this->valorAbatimento = $valorAbatimento;
        return $this;
    }

    public function getDataEmissao(): \DateTimeInterface
    {
        return $this->dataEmissao;
    }

    public function setDataEmissao(\DateTimeInterface $dataEmissao): self
    {
        $this->dataEmissao = $dataEmissao;
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

    public function getDataLimitePagamento(): ?\DateTimeInterface
    {
        return $this->dataLimitePagamento;
    }

    public function setDataLimitePagamento(?\DateTimeInterface $dataLimitePagamento): self
    {
        $this->dataLimitePagamento = $dataLimitePagamento;
        return $this;
    }

    public function getTipoDesconto(): ?string
    {
        return $this->tipoDesconto;
    }

    public function setTipoDesconto(?string $tipoDesconto): self
    {
        $this->tipoDesconto = $tipoDesconto;
        return $this;
    }

    public function getDataDesconto(): ?\DateTimeInterface
    {
        return $this->dataDesconto;
    }

    public function setDataDesconto(?\DateTimeInterface $dataDesconto): self
    {
        $this->dataDesconto = $dataDesconto;
        return $this;
    }

    public function getTipoJuros(): ?string
    {
        return $this->tipoJuros;
    }

    public function setTipoJuros(?string $tipoJuros): self
    {
        $this->tipoJuros = $tipoJuros;
        return $this;
    }

    public function getTipoMulta(): ?string
    {
        return $this->tipoMulta;
    }

    public function setTipoMulta(?string $tipoMulta): self
    {
        $this->tipoMulta = $tipoMulta;
        return $this;
    }

    public function getDataMulta(): ?\DateTimeInterface
    {
        return $this->dataMulta;
    }

    public function setDataMulta(?\DateTimeInterface $dataMulta): self
    {
        $this->dataMulta = $dataMulta;
        return $this;
    }

    public function getCodigoBarras(): ?string
    {
        return $this->codigoBarras;
    }

    public function setCodigoBarras(?string $codigoBarras): self
    {
        $this->codigoBarras = $codigoBarras;
        return $this;
    }

    public function getLinhaDigitavel(): ?string
    {
        return $this->linhaDigitavel;
    }

    public function setLinhaDigitavel(?string $linhaDigitavel): self
    {
        $this->linhaDigitavel = $linhaDigitavel;
        return $this;
    }

    public function getTxidPix(): ?string
    {
        return $this->txidPix;
    }

    public function setTxidPix(?string $txidPix): self
    {
        $this->txidPix = $txidPix;
        return $this;
    }

    public function getQrcodePix(): ?string
    {
        return $this->qrcodePix;
    }

    public function setQrcodePix(?string $qrcodePix): self
    {
        $this->qrcodePix = $qrcodePix;
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

    public function getIdTituloBanco(): ?string
    {
        return $this->idTituloBanco;
    }

    public function setIdTituloBanco(?string $idTituloBanco): self
    {
        $this->idTituloBanco = $idTituloBanco;
        return $this;
    }

    public function getConvenioBanco(): ?string
    {
        return $this->convenioBanco;
    }

    public function setConvenioBanco(?string $convenioBanco): self
    {
        $this->convenioBanco = $convenioBanco;
        return $this;
    }

    public function getMensagemPagador(): ?string
    {
        return $this->mensagemPagador;
    }

    public function setMensagemPagador(?string $mensagemPagador): self
    {
        $this->mensagemPagador = $mensagemPagador;
        return $this;
    }

    public function getTentativasRegistro(): ?int
    {
        return $this->tentativasRegistro;
    }

    public function setTentativasRegistro(?int $tentativasRegistro): self
    {
        $this->tentativasRegistro = $tentativasRegistro;
        return $this;
    }

    public function getUltimoErro(): ?string
    {
        return $this->ultimoErro;
    }

    public function setUltimoErro(?string $ultimoErro): self
    {
        $this->ultimoErro = $ultimoErro;
        return $this;
    }

    public function getDataRegistro(): ?\DateTimeInterface
    {
        return $this->dataRegistro;
    }

    public function setDataRegistro(?\DateTimeInterface $dataRegistro): self
    {
        $this->dataRegistro = $dataRegistro;
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

    public function getValorPago(): ?string
    {
        return $this->valorPago;
    }

    public function setValorPago(?string $valorPago): self
    {
        $this->valorPago = $valorPago;
        return $this;
    }

    public function getDataBaixa(): ?\DateTimeInterface
    {
        return $this->dataBaixa;
    }

    public function setDataBaixa(?\DateTimeInterface $dataBaixa): self
    {
        $this->dataBaixa = $dataBaixa;
        return $this;
    }

    public function getMotivoBaixa(): ?string
    {
        return $this->motivoBaixa;
    }

    public function setMotivoBaixa(?string $motivoBaixa): self
    {
        $this->motivoBaixa = $motivoBaixa;
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

    // === MÉTODOS AUXILIARES ===

    /**
     * Verifica se o boleto está registrado no banco
     */
    public function isRegistrado(): bool
    {
        return $this->status === self::STATUS_REGISTRADO
            || $this->status === self::STATUS_PAGO
            || $this->status === self::STATUS_VENCIDO
            || $this->status === self::STATUS_BAIXADO
            || $this->status === self::STATUS_PROTESTADO;
    }

    /**
     * Verifica se o boleto foi pago
     */
    public function isPago(): bool
    {
        return $this->status === self::STATUS_PAGO;
    }

    /**
     * Verifica se o boleto está vencido
     */
    public function isVencido(): bool
    {
        if ($this->isPago() || $this->status === self::STATUS_BAIXADO) {
            return false;
        }

        return $this->dataVencimento < new \DateTime('today');
    }

    /**
     * Calcula dias em atraso
     */
    public function getDiasAtraso(): int
    {
        if (!$this->isVencido()) {
            return 0;
        }

        $hoje = new \DateTime('today');
        $diff = $this->dataVencimento->diff($hoje);

        return $diff->days;
    }

    /**
     * Retorna valor nominal formatado
     */
    public function getValorNominalFormatado(): string
    {
        return 'R$ ' . number_format((float) $this->valorNominal, 2, ',', '.');
    }

    /**
     * Retorna data de vencimento formatada
     */
    public function getDataVencimentoFormatada(): string
    {
        return $this->dataVencimento->format('d/m/Y');
    }

    /**
     * Retorna label do status para exibição
     */
    public function getStatusLabel(): string
    {
        $labels = [
            self::STATUS_PENDENTE => 'Pendente',
            self::STATUS_REGISTRADO => 'Registrado',
            self::STATUS_PAGO => 'Pago',
            self::STATUS_VENCIDO => 'Vencido',
            self::STATUS_BAIXADO => 'Baixado',
            self::STATUS_PROTESTADO => 'Protestado',
            self::STATUS_ERRO => 'Erro',
        ];

        return $labels[$this->status] ?? $this->status;
    }

    /**
     * Retorna classe CSS do status para exibição
     */
    public function getStatusClass(): string
    {
        $classes = [
            self::STATUS_PENDENTE => 'warning',
            self::STATUS_REGISTRADO => 'info',
            self::STATUS_PAGO => 'success',
            self::STATUS_VENCIDO => 'danger',
            self::STATUS_BAIXADO => 'secondary',
            self::STATUS_PROTESTADO => 'dark',
            self::STATUS_ERRO => 'danger',
        ];

        return $classes[$this->status] ?? 'secondary';
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTime();
    }
}
