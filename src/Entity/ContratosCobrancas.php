<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ContratosCobrancasRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Cobranças mensais por competência.
 *
 * Controla:
 * - Uma única cobrança por contrato/competência (constraint)
 * - Valores detalhados por tipo (aluguel, IPTU, etc.)
 * - Status do ciclo (pendente → boleto gerado → enviado → pago)
 * - Bloqueio de rotina automática após envio manual
 */
#[ORM\Entity(repositoryClass: ContratosCobrancasRepository::class)]
#[ORM\Table(
    name: 'contratos_cobrancas',
    indexes: [
        new ORM\Index(name: 'idx_cobrancas_contrato', columns: ['contrato_id']),
        new ORM\Index(name: 'idx_cobrancas_competencia', columns: ['competencia']),
        new ORM\Index(name: 'idx_cobrancas_status', columns: ['status']),
        new ORM\Index(name: 'idx_cobrancas_vencimento', columns: ['data_vencimento']),
        new ORM\Index(name: 'idx_cobrancas_boleto', columns: ['boleto_id']),
    ],
    uniqueConstraints: [
        new ORM\UniqueConstraint(name: 'uk_cobranca_contrato_competencia', columns: ['contrato_id', 'competencia'])
    ]
)]
#[ORM\HasLifecycleCallbacks]
class ContratosCobrancas
{
    // Status do ciclo
    public const STATUS_PENDENTE = 'PENDENTE';
    public const STATUS_BOLETO_GERADO = 'BOLETO_GERADO';
    public const STATUS_ENVIADO = 'ENVIADO';
    public const STATUS_PAGO = 'PAGO';
    public const STATUS_CANCELADO = 'CANCELADO';

    // Tipo de envio
    public const TIPO_ENVIO_AUTOMATICO = 'AUTOMATICO';
    public const TIPO_ENVIO_MANUAL = 'MANUAL';

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ImoveisContratos::class, inversedBy: 'cobrancas')]
    #[ORM\JoinColumn(name: 'contrato_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ImoveisContratos $contrato;

    #[ORM\OneToOne(targetEntity: Boletos::class)]
    #[ORM\JoinColumn(name: 'boleto_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Boletos $boleto = null;

    #[ORM\Column(name: 'competencia', type: Types::STRING, length: 7)]
    private string $competencia;

    #[ORM\Column(name: 'periodo_inicio', type: Types::DATE_MUTABLE)]
    private \DateTimeInterface $periodoInicio;

    #[ORM\Column(name: 'periodo_fim', type: Types::DATE_MUTABLE)]
    private \DateTimeInterface $periodoFim;

    #[ORM\Column(name: 'data_vencimento', type: Types::DATE_MUTABLE)]
    private \DateTimeInterface $dataVencimento;

    #[ORM\Column(name: 'valor_aluguel', type: Types::DECIMAL, precision: 15, scale: 2, options: ['default' => 0])]
    private string $valorAluguel = '0.00';

    #[ORM\Column(name: 'valor_iptu', type: Types::DECIMAL, precision: 15, scale: 2, options: ['default' => 0])]
    private string $valorIptu = '0.00';

    #[ORM\Column(name: 'valor_condominio', type: Types::DECIMAL, precision: 15, scale: 2, options: ['default' => 0])]
    private string $valorCondominio = '0.00';

    #[ORM\Column(name: 'valor_taxa_admin', type: Types::DECIMAL, precision: 15, scale: 2, options: ['default' => 0])]
    private string $valorTaxaAdmin = '0.00';

    #[ORM\Column(name: 'valor_outros', type: Types::DECIMAL, precision: 15, scale: 2, options: ['default' => 0])]
    private string $valorOutros = '0.00';

    #[ORM\Column(name: 'valor_total', type: Types::DECIMAL, precision: 15, scale: 2)]
    private string $valorTotal;

    #[ORM\Column(name: 'itens_detalhados', type: Types::JSON, nullable: true)]
    private ?array $itensDetalhados = null;

    #[ORM\Column(name: 'status', type: Types::STRING, length: 30, options: ['default' => 'PENDENTE'])]
    private string $status = self::STATUS_PENDENTE;

    #[ORM\Column(name: 'tipo_envio', type: Types::STRING, length: 20, nullable: true)]
    private ?string $tipoEnvio = null;

    #[ORM\Column(name: 'enviado_em', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $enviadoEm = null;

    #[ORM\Column(name: 'email_destino', type: Types::STRING, length: 255, nullable: true)]
    private ?string $emailDestino = null;

    #[ORM\Column(name: 'bloqueado_rotina_auto', type: Types::BOOLEAN, options: ['default' => false])]
    private bool $bloqueadoRotinaAuto = false;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE, options: ['default' => 'CURRENT_TIMESTAMP'])]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_MUTABLE, options: ['default' => 'CURRENT_TIMESTAMP'])]
    private \DateTimeInterface $updatedAt;

    #[ORM\ManyToOne(targetEntity: Users::class)]
    #[ORM\JoinColumn(name: 'created_by', referencedColumnName: 'id', nullable: true)]
    private ?Users $createdBy = null;

    public function __construct()
    {
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

    // Getters e Setters

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContrato(): ImoveisContratos
    {
        return $this->contrato;
    }

    public function setContrato(ImoveisContratos $contrato): self
    {
        $this->contrato = $contrato;
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

    public function getCompetencia(): string
    {
        return $this->competencia;
    }

    public function setCompetencia(string $competencia): self
    {
        $this->competencia = $competencia;
        return $this;
    }

    public function getPeriodoInicio(): \DateTimeInterface
    {
        return $this->periodoInicio;
    }

    public function setPeriodoInicio(\DateTimeInterface $periodoInicio): self
    {
        $this->periodoInicio = $periodoInicio;
        return $this;
    }

    public function getPeriodoFim(): \DateTimeInterface
    {
        return $this->periodoFim;
    }

    public function setPeriodoFim(\DateTimeInterface $periodoFim): self
    {
        $this->periodoFim = $periodoFim;
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

    public function getValorAluguel(): string
    {
        return $this->valorAluguel;
    }

    public function setValorAluguel(string|float $valorAluguel): self
    {
        $this->valorAluguel = (string) $valorAluguel;
        return $this;
    }

    public function getValorIptu(): string
    {
        return $this->valorIptu;
    }

    public function setValorIptu(string|float $valorIptu): self
    {
        $this->valorIptu = (string) $valorIptu;
        return $this;
    }

    public function getValorCondominio(): string
    {
        return $this->valorCondominio;
    }

    public function setValorCondominio(string|float $valorCondominio): self
    {
        $this->valorCondominio = (string) $valorCondominio;
        return $this;
    }

    public function getValorTaxaAdmin(): string
    {
        return $this->valorTaxaAdmin;
    }

    public function setValorTaxaAdmin(string|float $valorTaxaAdmin): self
    {
        $this->valorTaxaAdmin = (string) $valorTaxaAdmin;
        return $this;
    }

    public function getValorOutros(): string
    {
        return $this->valorOutros;
    }

    public function setValorOutros(string|float $valorOutros): self
    {
        $this->valorOutros = (string) $valorOutros;
        return $this;
    }

    public function getValorTotal(): string
    {
        return $this->valorTotal;
    }

    public function setValorTotal(string|float $valorTotal): self
    {
        $this->valorTotal = (string) $valorTotal;
        return $this;
    }

    public function getValorTotalFloat(): float
    {
        return (float) $this->valorTotal;
    }

    public function getItensDetalhados(): ?array
    {
        return $this->itensDetalhados;
    }

    public function setItensDetalhados(?array $itensDetalhados): self
    {
        $this->itensDetalhados = $itensDetalhados;
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

    public function getTipoEnvio(): ?string
    {
        return $this->tipoEnvio;
    }

    public function setTipoEnvio(?string $tipoEnvio): self
    {
        $this->tipoEnvio = $tipoEnvio;
        return $this;
    }

    public function getEnviadoEm(): ?\DateTimeInterface
    {
        return $this->enviadoEm;
    }

    public function setEnviadoEm(?\DateTimeInterface $enviadoEm): self
    {
        $this->enviadoEm = $enviadoEm;
        return $this;
    }

    public function getEmailDestino(): ?string
    {
        return $this->emailDestino;
    }

    public function setEmailDestino(?string $emailDestino): self
    {
        $this->emailDestino = $emailDestino;
        return $this;
    }

    public function isBloqueadoRotinaAuto(): bool
    {
        return $this->bloqueadoRotinaAuto;
    }

    public function setBloqueadoRotinaAuto(bool $bloqueadoRotinaAuto): self
    {
        $this->bloqueadoRotinaAuto = $bloqueadoRotinaAuto;
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

    public function getCreatedBy(): ?Users
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?Users $createdBy): self
    {
        $this->createdBy = $createdBy;
        return $this;
    }

    // Métodos auxiliares de status

    public function isPendente(): bool
    {
        return $this->status === self::STATUS_PENDENTE;
    }

    public function isBoletoGerado(): bool
    {
        return $this->status === self::STATUS_BOLETO_GERADO;
    }

    public function isEnviado(): bool
    {
        return $this->status === self::STATUS_ENVIADO;
    }

    public function isPago(): bool
    {
        return $this->status === self::STATUS_PAGO;
    }

    public function isCancelado(): bool
    {
        return $this->status === self::STATUS_CANCELADO;
    }

    public function podeEnviarManualmente(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDENTE,
            self::STATUS_BOLETO_GERADO
        ]);
    }

    public function podeGerarBoleto(): bool
    {
        return $this->status === self::STATUS_PENDENTE && $this->boleto === null;
    }

    public function podeCancelar(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDENTE,
            self::STATUS_BOLETO_GERADO,
            self::STATUS_ENVIADO
        ]);
    }

    // Métodos de apresentação

    public function getStatusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDENTE => 'Pendente',
            self::STATUS_BOLETO_GERADO => 'Boleto Gerado',
            self::STATUS_ENVIADO => 'Enviado',
            self::STATUS_PAGO => 'Pago',
            self::STATUS_CANCELADO => 'Cancelado',
            default => $this->status,
        };
    }

    public function getStatusClass(): string
    {
        return match ($this->status) {
            self::STATUS_PENDENTE => 'warning',
            self::STATUS_BOLETO_GERADO => 'info',
            self::STATUS_ENVIADO => 'primary',
            self::STATUS_PAGO => 'success',
            self::STATUS_CANCELADO => 'secondary',
            default => 'secondary',
        };
    }

    public function getTipoEnvioLabel(): ?string
    {
        if (!$this->tipoEnvio) {
            return null;
        }

        return match ($this->tipoEnvio) {
            self::TIPO_ENVIO_AUTOMATICO => 'Automático',
            self::TIPO_ENVIO_MANUAL => 'Manual',
            default => $this->tipoEnvio,
        };
    }

    public function getCompetenciaFormatada(): string
    {
        $meses = [
            '01' => 'Janeiro', '02' => 'Fevereiro', '03' => 'Março',
            '04' => 'Abril', '05' => 'Maio', '06' => 'Junho',
            '07' => 'Julho', '08' => 'Agosto', '09' => 'Setembro',
            '10' => 'Outubro', '11' => 'Novembro', '12' => 'Dezembro'
        ];

        [$ano, $mes] = explode('-', $this->competencia);
        return ($meses[$mes] ?? $mes) . '/' . $ano;
    }

    public function getValorTotalFormatado(): string
    {
        return 'R$ ' . number_format($this->getValorTotalFloat(), 2, ',', '.');
    }

    public function getPeriodoFormatado(): string
    {
        return $this->periodoInicio->format('d/m/Y') . ' a ' . $this->periodoFim->format('d/m/Y');
    }

    /**
     * Calcula total a partir dos valores individuais (sem usar valorTotal)
     */
    public function calcularTotal(): float
    {
        return (float) $this->valorAluguel
            + (float) $this->valorIptu
            + (float) $this->valorCondominio
            + (float) $this->valorTaxaAdmin
            + (float) $this->valorOutros;
    }

    /**
     * Lista de status disponíveis
     */
    public static function getStatusDisponiveis(): array
    {
        return [
            self::STATUS_PENDENTE => 'Pendente',
            self::STATUS_BOLETO_GERADO => 'Boleto Gerado',
            self::STATUS_ENVIADO => 'Enviado',
            self::STATUS_PAGO => 'Pago',
            self::STATUS_CANCELADO => 'Cancelado',
        ];
    }
}
