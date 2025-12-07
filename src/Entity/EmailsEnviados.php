<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\EmailsEnviadosRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Log de todos os emails enviados pelo sistema.
 *
 * Armazena:
 * - Referência polimórfica (COBRANCA, BOLETO, CONTRATO, etc.)
 * - Dados do email (destinatário, assunto, corpo)
 * - Anexos (JSON com nome e tamanho)
 * - Status do envio
 */
#[ORM\Entity(repositoryClass: EmailsEnviadosRepository::class)]
#[ORM\Table(
    name: 'emails_enviados',
    indexes: [
        new ORM\Index(name: 'idx_emails_destinatario', columns: ['destinatario']),
        new ORM\Index(name: 'idx_emails_tipo', columns: ['tipo_referencia', 'referencia_id']),
        new ORM\Index(name: 'idx_emails_status', columns: ['status']),
        new ORM\Index(name: 'idx_emails_enviado_em', columns: ['enviado_em']),
    ]
)]
class EmailsEnviados
{
    // Tipos de referência
    public const TIPO_COBRANCA = 'COBRANCA';
    public const TIPO_BOLETO = 'BOLETO';
    public const TIPO_CONTRATO = 'CONTRATO';
    public const TIPO_INFORME = 'INFORME';
    public const TIPO_AVISO = 'AVISO';

    // Status
    public const STATUS_ENVIADO = 'ENVIADO';
    public const STATUS_FALHA = 'FALHA';
    public const STATUS_BOUNCE = 'BOUNCE';

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(name: 'tipo_referencia', type: Types::STRING, length: 50)]
    private string $tipoReferencia;

    #[ORM\Column(name: 'referencia_id', type: Types::INTEGER)]
    private int $referenciaId;

    #[ORM\Column(name: 'destinatario', type: Types::STRING, length: 255)]
    private string $destinatario;

    #[ORM\Column(name: 'assunto', type: Types::STRING, length: 255)]
    private string $assunto;

    #[ORM\Column(name: 'corpo', type: Types::TEXT, nullable: true)]
    private ?string $corpo = null;

    #[ORM\Column(name: 'anexos', type: Types::JSON, nullable: true)]
    private ?array $anexos = null;

    #[ORM\Column(name: 'status', type: Types::STRING, length: 20, options: ['default' => 'ENVIADO'])]
    private string $status = self::STATUS_ENVIADO;

    #[ORM\Column(name: 'erro_mensagem', type: Types::TEXT, nullable: true)]
    private ?string $erroMensagem = null;

    #[ORM\Column(name: 'enviado_em', type: Types::DATETIME_MUTABLE, options: ['default' => 'CURRENT_TIMESTAMP'])]
    private \DateTimeInterface $enviadoEm;

    public function __construct()
    {
        $this->enviadoEm = new \DateTime();
    }

    // Getters e Setters

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTipoReferencia(): string
    {
        return $this->tipoReferencia;
    }

    public function setTipoReferencia(string $tipoReferencia): self
    {
        $this->tipoReferencia = $tipoReferencia;
        return $this;
    }

    public function getReferenciaId(): int
    {
        return $this->referenciaId;
    }

    public function setReferenciaId(int $referenciaId): self
    {
        $this->referenciaId = $referenciaId;
        return $this;
    }

    public function getDestinatario(): string
    {
        return $this->destinatario;
    }

    public function setDestinatario(string $destinatario): self
    {
        $this->destinatario = $destinatario;
        return $this;
    }

    public function getAssunto(): string
    {
        return $this->assunto;
    }

    public function setAssunto(string $assunto): self
    {
        $this->assunto = $assunto;
        return $this;
    }

    public function getCorpo(): ?string
    {
        return $this->corpo;
    }

    public function setCorpo(?string $corpo): self
    {
        $this->corpo = $corpo;
        return $this;
    }

    public function getAnexos(): ?array
    {
        return $this->anexos;
    }

    public function setAnexos(?array $anexos): self
    {
        $this->anexos = $anexos;
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

    public function getErroMensagem(): ?string
    {
        return $this->erroMensagem;
    }

    public function setErroMensagem(?string $erroMensagem): self
    {
        $this->erroMensagem = $erroMensagem;
        return $this;
    }

    public function getEnviadoEm(): \DateTimeInterface
    {
        return $this->enviadoEm;
    }

    public function setEnviadoEm(\DateTimeInterface $enviadoEm): self
    {
        $this->enviadoEm = $enviadoEm;
        return $this;
    }

    // Métodos auxiliares

    public function isEnviado(): bool
    {
        return $this->status === self::STATUS_ENVIADO;
    }

    public function isFalha(): bool
    {
        return $this->status === self::STATUS_FALHA;
    }

    public function isBounce(): bool
    {
        return $this->status === self::STATUS_BOUNCE;
    }

    public function getStatusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_ENVIADO => 'Enviado',
            self::STATUS_FALHA => 'Falha',
            self::STATUS_BOUNCE => 'Bounce',
            default => $this->status,
        };
    }

    public function getStatusClass(): string
    {
        return match ($this->status) {
            self::STATUS_ENVIADO => 'success',
            self::STATUS_FALHA => 'danger',
            self::STATUS_BOUNCE => 'warning',
            default => 'secondary',
        };
    }

    public function getTipoReferenciaLabel(): string
    {
        return match ($this->tipoReferencia) {
            self::TIPO_COBRANCA => 'Cobrança',
            self::TIPO_BOLETO => 'Boleto',
            self::TIPO_CONTRATO => 'Contrato',
            self::TIPO_INFORME => 'Informe',
            self::TIPO_AVISO => 'Aviso',
            default => $this->tipoReferencia,
        };
    }

    /**
     * Retorna quantidade de anexos
     */
    public function getQuantidadeAnexos(): int
    {
        return $this->anexos ? count($this->anexos) : 0;
    }

    /**
     * Retorna tamanho total dos anexos em bytes
     */
    public function getTamanhoTotalAnexos(): int
    {
        if (!$this->anexos) {
            return 0;
        }

        $total = 0;
        foreach ($this->anexos as $anexo) {
            $total += $anexo['tamanho'] ?? 0;
        }

        return $total;
    }

    /**
     * Retorna tamanho formatado (KB, MB)
     */
    public function getTamanhoAnexosFormatado(): string
    {
        $bytes = $this->getTamanhoTotalAnexos();

        if ($bytes < 1024) {
            return $bytes . ' B';
        } elseif ($bytes < 1048576) {
            return round($bytes / 1024, 2) . ' KB';
        } else {
            return round($bytes / 1048576, 2) . ' MB';
        }
    }
}
