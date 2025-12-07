<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ContratosItensCobrancaRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Itens de composição do valor de cobrança mensal do contrato.
 *
 * Define quais itens compõem o boleto mensal:
 * - Aluguel
 * - IPTU (pode ser parcelado)
 * - Condomínio
 * - Taxa de administração
 * - Outros
 */
#[ORM\Entity(repositoryClass: ContratosItensCobrancaRepository::class)]
#[ORM\Table(
    name: 'contratos_itens_cobranca',
    indexes: [
        new ORM\Index(name: 'idx_contratos_itens_contrato', columns: ['contrato_id']),
        new ORM\Index(name: 'idx_contratos_itens_tipo', columns: ['tipo_item']),
        new ORM\Index(name: 'idx_contratos_itens_ativo', columns: ['ativo']),
    ],
    uniqueConstraints: [
        new ORM\UniqueConstraint(name: 'uk_itens_contrato_tipo', columns: ['contrato_id', 'tipo_item'])
    ]
)]
#[ORM\HasLifecycleCallbacks]
class ContratosItensCobranca
{
    // Tipos de Item
    public const TIPO_ALUGUEL = 'ALUGUEL';
    public const TIPO_IPTU = 'IPTU';
    public const TIPO_CONDOMINIO = 'CONDOMINIO';
    public const TIPO_TAXA_ADMIN = 'TAXA_ADMINISTRACAO';
    public const TIPO_SEGURO = 'SEGURO';
    public const TIPO_AGUA = 'AGUA';
    public const TIPO_LUZ = 'LUZ';
    public const TIPO_GAS = 'GAS';
    public const TIPO_OUTROS = 'OUTROS';

    // Tipos de Valor
    public const VALOR_TIPO_FIXO = 'FIXO';
    public const VALOR_TIPO_PERCENTUAL = 'PERCENTUAL';

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ImoveisContratos::class, inversedBy: 'itensCobranca')]
    #[ORM\JoinColumn(name: 'contrato_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ImoveisContratos $contrato;

    #[ORM\Column(name: 'tipo_item', type: Types::STRING, length: 50)]
    private string $tipoItem;

    #[ORM\Column(name: 'descricao', type: Types::STRING, length: 100)]
    private string $descricao;

    #[ORM\Column(name: 'valor_tipo', type: Types::STRING, length: 20, options: ['default' => 'FIXO'])]
    private string $valorTipo = self::VALOR_TIPO_FIXO;

    #[ORM\Column(name: 'valor', type: Types::DECIMAL, precision: 15, scale: 2)]
    private string $valor;

    #[ORM\Column(name: 'ativo', type: Types::BOOLEAN, options: ['default' => true])]
    private bool $ativo = true;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE, options: ['default' => 'CURRENT_TIMESTAMP'])]
    private \DateTimeInterface $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        if (!isset($this->createdAt)) {
            $this->createdAt = new \DateTime();
        }
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

    public function getTipoItem(): string
    {
        return $this->tipoItem;
    }

    public function setTipoItem(string $tipoItem): self
    {
        $this->tipoItem = $tipoItem;
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

    public function getValorTipo(): string
    {
        return $this->valorTipo;
    }

    public function setValorTipo(string $valorTipo): self
    {
        $this->valorTipo = $valorTipo;
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

    // Métodos auxiliares

    public function isPercentual(): bool
    {
        return $this->valorTipo === self::VALOR_TIPO_PERCENTUAL;
    }

    public function isFixo(): bool
    {
        return $this->valorTipo === self::VALOR_TIPO_FIXO;
    }

    /**
     * Calcula o valor efetivo considerando se é percentual
     */
    public function calcularValorEfetivo(float $valorBase = 0): float
    {
        if ($this->isPercentual()) {
            return $valorBase * ($this->getValorFloat() / 100);
        }
        return $this->getValorFloat();
    }

    /**
     * Retorna label do tipo de item
     */
    public function getTipoItemLabel(): string
    {
        return match ($this->tipoItem) {
            self::TIPO_ALUGUEL => 'Aluguel',
            self::TIPO_IPTU => 'IPTU',
            self::TIPO_CONDOMINIO => 'Condomínio',
            self::TIPO_TAXA_ADMIN => 'Taxa de Administração',
            self::TIPO_SEGURO => 'Seguro',
            self::TIPO_AGUA => 'Água',
            self::TIPO_LUZ => 'Luz',
            self::TIPO_GAS => 'Gás',
            self::TIPO_OUTROS => 'Outros',
            default => $this->tipoItem,
        };
    }

    /**
     * Retorna valor formatado em reais
     */
    public function getValorFormatado(): string
    {
        if ($this->isPercentual()) {
            return number_format($this->getValorFloat(), 2, ',', '.') . '%';
        }
        return 'R$ ' . number_format($this->getValorFloat(), 2, ',', '.');
    }

    /**
     * Lista de tipos disponíveis
     */
    public static function getTiposDisponiveis(): array
    {
        return [
            self::TIPO_ALUGUEL => 'Aluguel',
            self::TIPO_IPTU => 'IPTU',
            self::TIPO_CONDOMINIO => 'Condomínio',
            self::TIPO_TAXA_ADMIN => 'Taxa de Administração',
            self::TIPO_SEGURO => 'Seguro',
            self::TIPO_AGUA => 'Água',
            self::TIPO_LUZ => 'Luz',
            self::TIPO_GAS => 'Gás',
            self::TIPO_OUTROS => 'Outros',
        ];
    }
}
