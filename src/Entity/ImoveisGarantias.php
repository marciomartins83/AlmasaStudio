<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ImoveisGarantiasRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ImoveisGarantiasRepository::class)]
#[ORM\Table(name: 'imoveis_garantias')]
#[ORM\UniqueConstraint(name: 'uk_imovel_garantia', columns: ['id_imovel'])]
class ImoveisGarantias
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Imoveis::class, inversedBy: 'garantias')]
    #[ORM\JoinColumn(name: 'id_imovel', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Imoveis $imovel;

    #[ORM\Column(name: 'aceita_caucao', type: Types::BOOLEAN, options: ['default' => false])]
    private bool $aceitaCaucao = false;

    #[ORM\Column(name: 'aceita_fiador', type: Types::BOOLEAN, options: ['default' => false])]
    private bool $aceitaFiador = false;

    #[ORM\Column(name: 'aceita_seguro_fianca', type: Types::BOOLEAN, options: ['default' => false])]
    private bool $aceitaSeguroFianca = false;

    #[ORM\Column(name: 'aceita_outras', type: Types::BOOLEAN, options: ['default' => false])]
    private bool $aceitaOutras = false;

    #[ORM\Column(name: 'valor_caucao', type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $valorCaucao = null;

    #[ORM\Column(name: 'qtd_meses_caucao', type: Types::INTEGER, nullable: true)]
    private ?int $qtdMesesCaucao = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    private ?string $seguradora = null;

    #[ORM\Column(name: 'numero_apolice', type: Types::STRING, length: 30, nullable: true)]
    private ?string $numeroApolice = null;

    #[ORM\Column(name: 'vencimento_seguro', type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $vencimentoSeguro = null;

    #[ORM\Column(name: 'valor_seguro', type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $valorSeguro = null;

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
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getImovel(): Imoveis
    {
        return $this->imovel;
    }

    public function setImovel(Imoveis $imovel): self
    {
        $this->imovel = $imovel;
        return $this;
    }

    public function isAceitaCaucao(): bool
    {
        return $this->aceitaCaucao;
    }

    public function setAceitaCaucao(bool $aceitaCaucao): self
    {
        $this->aceitaCaucao = $aceitaCaucao;
        return $this;
    }

    public function isAceitaFiador(): bool
    {
        return $this->aceitaFiador;
    }

    public function setAceitaFiador(bool $aceitaFiador): self
    {
        $this->aceitaFiador = $aceitaFiador;
        return $this;
    }

    public function isAceitaSeguroFianca(): bool
    {
        return $this->aceitaSeguroFianca;
    }

    public function setAceitaSeguroFianca(bool $aceitaSeguroFianca): self
    {
        $this->aceitaSeguroFianca = $aceitaSeguroFianca;
        return $this;
    }

    public function isAceitaOutras(): bool
    {
        return $this->aceitaOutras;
    }

    public function setAceitaOutras(bool $aceitaOutras): self
    {
        $this->aceitaOutras = $aceitaOutras;
        return $this;
    }

    public function getValorCaucao(): ?string
    {
        return $this->valorCaucao;
    }

    public function setValorCaucao(?string $valorCaucao): self
    {
        $this->valorCaucao = $valorCaucao;
        return $this;
    }

    public function getQtdMesesCaucao(): ?int
    {
        return $this->qtdMesesCaucao;
    }

    public function setQtdMesesCaucao(?int $qtdMesesCaucao): self
    {
        $this->qtdMesesCaucao = $qtdMesesCaucao;
        return $this;
    }

    public function getSeguradora(): ?string
    {
        return $this->seguradora;
    }

    public function setSeguradora(?string $seguradora): self
    {
        $this->seguradora = $seguradora;
        return $this;
    }

    public function getNumeroApolice(): ?string
    {
        return $this->numeroApolice;
    }

    public function setNumeroApolice(?string $numeroApolice): self
    {
        $this->numeroApolice = $numeroApolice;
        return $this;
    }

    public function getVencimentoSeguro(): ?\DateTimeInterface
    {
        return $this->vencimentoSeguro;
    }

    public function setVencimentoSeguro(?\DateTimeInterface $vencimentoSeguro): self
    {
        $this->vencimentoSeguro = $vencimentoSeguro;
        return $this;
    }

    public function getValorSeguro(): ?string
    {
        return $this->valorSeguro;
    }

    public function setValorSeguro(?string $valorSeguro): self
    {
        $this->valorSeguro = $valorSeguro;
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

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }
}
