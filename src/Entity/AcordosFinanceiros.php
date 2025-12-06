<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\AcordosFinanceirosRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AcordosFinanceirosRepository::class)]
#[ORM\Table(name: 'acordos_financeiros')]
#[ORM\HasLifecycleCallbacks]
class AcordosFinanceiros
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(name: 'numero_acordo', type: Types::INTEGER)]
    private int $numeroAcordo;

    #[ORM\ManyToOne(targetEntity: Pessoas::class)]
    #[ORM\JoinColumn(name: 'id_inquilino', referencedColumnName: 'idpessoa', nullable: false, onDelete: 'CASCADE')]
    private Pessoas $inquilino;

    #[ORM\Column(name: 'data_acordo', type: Types::DATE_MUTABLE)]
    private \DateTimeInterface $dataAcordo;

    #[ORM\Column(name: 'data_primeira_parcela', type: Types::DATE_MUTABLE)]
    private \DateTimeInterface $dataPrimeiraParcela;

    #[ORM\Column(name: 'valor_divida_original', type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $valorDividaOriginal;

    #[ORM\Column(name: 'valor_desconto', type: Types::DECIMAL, precision: 10, scale: 2, nullable: true, options: ['default' => '0.00'])]
    private ?string $valorDesconto = '0.00';

    #[ORM\Column(name: 'valor_juros', type: Types::DECIMAL, precision: 10, scale: 2, nullable: true, options: ['default' => '0.00'])]
    private ?string $valorJuros = '0.00';

    #[ORM\Column(name: 'valor_total_acordo', type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $valorTotalAcordo;

    #[ORM\Column(name: 'quantidade_parcelas', type: Types::INTEGER, options: ['default' => 1])]
    private int $quantidadeParcelas = 1;

    #[ORM\Column(name: 'valor_parcela', type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $valorParcela;

    #[ORM\Column(name: 'dia_vencimento', type: Types::INTEGER, nullable: true, options: ['default' => 10])]
    private ?int $diaVencimento = 10;

    #[ORM\Column(type: Types::STRING, length: 20, options: ['default' => 'ativo'])]
    private string $situacao = 'ativo';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $observacoes = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE, options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_MUTABLE, options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
        $this->dataAcordo = new \DateTime();
        $this->dataPrimeiraParcela = new \DateTime();
    }

    // === GETTERS E SETTERS ===

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumeroAcordo(): int
    {
        return $this->numeroAcordo;
    }

    public function setNumeroAcordo(int $numeroAcordo): self
    {
        $this->numeroAcordo = $numeroAcordo;
        return $this;
    }

    public function getInquilino(): Pessoas
    {
        return $this->inquilino;
    }

    public function setInquilino(Pessoas $inquilino): self
    {
        $this->inquilino = $inquilino;
        return $this;
    }

    public function getDataAcordo(): \DateTimeInterface
    {
        return $this->dataAcordo;
    }

    public function setDataAcordo(\DateTimeInterface $dataAcordo): self
    {
        $this->dataAcordo = $dataAcordo;
        return $this;
    }

    public function getDataPrimeiraParcela(): \DateTimeInterface
    {
        return $this->dataPrimeiraParcela;
    }

    public function setDataPrimeiraParcela(\DateTimeInterface $dataPrimeiraParcela): self
    {
        $this->dataPrimeiraParcela = $dataPrimeiraParcela;
        return $this;
    }

    public function getValorDividaOriginal(): string
    {
        return $this->valorDividaOriginal;
    }

    public function setValorDividaOriginal(string $valorDividaOriginal): self
    {
        $this->valorDividaOriginal = $valorDividaOriginal;
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

    public function getValorJuros(): ?string
    {
        return $this->valorJuros;
    }

    public function setValorJuros(?string $valorJuros): self
    {
        $this->valorJuros = $valorJuros;
        return $this;
    }

    public function getValorTotalAcordo(): string
    {
        return $this->valorTotalAcordo;
    }

    public function setValorTotalAcordo(string $valorTotalAcordo): self
    {
        $this->valorTotalAcordo = $valorTotalAcordo;
        return $this;
    }

    public function getQuantidadeParcelas(): int
    {
        return $this->quantidadeParcelas;
    }

    public function setQuantidadeParcelas(int $quantidadeParcelas): self
    {
        $this->quantidadeParcelas = $quantidadeParcelas;
        return $this;
    }

    public function getValorParcela(): string
    {
        return $this->valorParcela;
    }

    public function setValorParcela(string $valorParcela): self
    {
        $this->valorParcela = $valorParcela;
        return $this;
    }

    public function getDiaVencimento(): ?int
    {
        return $this->diaVencimento;
    }

    public function setDiaVencimento(?int $diaVencimento): self
    {
        $this->diaVencimento = $diaVencimento;
        return $this;
    }

    public function getSituacao(): string
    {
        return $this->situacao;
    }

    public function setSituacao(string $situacao): self
    {
        $this->situacao = $situacao;
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

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    #[ORM\PreUpdate]
    public function preUpdate(): void
    {
        $this->updatedAt = new \DateTime();
    }

    // === MÉTODOS AUXILIARES ===

    /**
     * Calcula valor total do acordo
     */
    public function calcularTotal(): self
    {
        $total = (float) $this->valorDividaOriginal
            - (float) ($this->valorDesconto ?? 0)
            + (float) ($this->valorJuros ?? 0);

        $this->valorTotalAcordo = number_format($total, 2, '.', '');

        return $this;
    }

    /**
     * Calcula valor de cada parcela
     */
    public function calcularParcela(): self
    {
        if ($this->quantidadeParcelas > 0) {
            $parcela = (float) $this->valorTotalAcordo / $this->quantidadeParcelas;
            $this->valorParcela = number_format($parcela, 2, '.', '');
        }

        return $this;
    }

    /**
     * Verifica se o acordo está ativo
     */
    public function isAtivo(): bool
    {
        return $this->situacao === 'ativo';
    }

    /**
     * Verifica se o acordo está quitado
     */
    public function isQuitado(): bool
    {
        return $this->situacao === 'quitado';
    }
}
