<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\BoletosLogApiRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BoletosLogApiRepository::class)]
#[ORM\Table(
    name: 'boletos_log_api',
    indexes: [
        new ORM\Index(name: 'idx_boletos_log_boleto', columns: ['boleto_id']),
        new ORM\Index(name: 'idx_boletos_log_operacao', columns: ['operacao']),
        new ORM\Index(name: 'idx_boletos_log_created', columns: ['created_at']),
    ]
)]
class BoletosLogApi
{
    // Constantes de Operação
    public const OPERACAO_REGISTRO = 'REGISTRO';
    public const OPERACAO_CONSULTA = 'CONSULTA';
    public const OPERACAO_ALTERACAO = 'ALTERACAO';
    public const OPERACAO_BAIXA = 'BAIXA';
    public const OPERACAO_PROTESTO = 'PROTESTO';

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Boletos::class, inversedBy: 'logs')]
    #[ORM\JoinColumn(name: 'boleto_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    private ?Boletos $boleto = null;

    #[ORM\Column(type: Types::STRING, length: 50)]
    private string $operacao;

    #[ORM\Column(name: 'request_payload', type: Types::TEXT, nullable: true)]
    private ?string $requestPayload = null;

    #[ORM\Column(name: 'response_payload', type: Types::TEXT, nullable: true)]
    private ?string $responsePayload = null;

    #[ORM\Column(name: 'http_code', type: Types::INTEGER, nullable: true)]
    private ?int $httpCode = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $sucesso = false;

    #[ORM\Column(name: 'mensagem_erro', type: Types::TEXT, nullable: true)]
    private ?string $mensagemErro = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getOperacao(): string
    {
        return $this->operacao;
    }

    public function setOperacao(string $operacao): self
    {
        $this->operacao = $operacao;
        return $this;
    }

    public function getRequestPayload(): ?string
    {
        return $this->requestPayload;
    }

    public function setRequestPayload(?string $requestPayload): self
    {
        $this->requestPayload = $requestPayload;
        return $this;
    }

    public function getResponsePayload(): ?string
    {
        return $this->responsePayload;
    }

    public function setResponsePayload(?string $responsePayload): self
    {
        $this->responsePayload = $responsePayload;
        return $this;
    }

    public function getHttpCode(): ?int
    {
        return $this->httpCode;
    }

    public function setHttpCode(?int $httpCode): self
    {
        $this->httpCode = $httpCode;
        return $this;
    }

    public function isSucesso(): bool
    {
        return $this->sucesso;
    }

    public function setSucesso(bool $sucesso): self
    {
        $this->sucesso = $sucesso;
        return $this;
    }

    public function getMensagemErro(): ?string
    {
        return $this->mensagemErro;
    }

    public function setMensagemErro(?string $mensagemErro): self
    {
        $this->mensagemErro = $mensagemErro;
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

    // === MÉTODOS AUXILIARES ===

    /**
     * Retorna o request payload como array
     */
    public function getRequestPayloadArray(): ?array
    {
        if ($this->requestPayload === null) {
            return null;
        }

        return json_decode($this->requestPayload, true);
    }

    /**
     * Retorna o response payload como array
     */
    public function getResponsePayloadArray(): ?array
    {
        if ($this->responsePayload === null) {
            return null;
        }

        return json_decode($this->responsePayload, true);
    }

    /**
     * Retorna label da operação
     */
    public function getOperacaoLabel(): string
    {
        $labels = [
            self::OPERACAO_REGISTRO => 'Registro',
            self::OPERACAO_CONSULTA => 'Consulta',
            self::OPERACAO_ALTERACAO => 'Alteração',
            self::OPERACAO_BAIXA => 'Baixa',
            self::OPERACAO_PROTESTO => 'Protesto',
        ];

        return $labels[$this->operacao] ?? $this->operacao;
    }

    /**
     * Retorna classe CSS do status
     */
    public function getStatusClass(): string
    {
        return $this->sucesso ? 'success' : 'danger';
    }

    /**
     * Retorna data formatada
     */
    public function getCreatedAtFormatada(): string
    {
        return $this->createdAt->format('d/m/Y H:i:s');
    }
}
