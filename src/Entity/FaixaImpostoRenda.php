<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\FaixaImpostoRendaRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FaixaImpostoRendaRepository::class)]
#[ORM\Table(name: 'faixas_imposto_renda')]
#[ORM\Index(name: 'idx_faixas_ir_vigencia', columns: ['data_vigencia'])]
#[ORM\Index(name: 'idx_faixas_ir_ativo', columns: ['ativo'])]
#[ORM\HasLifecycleCallbacks]
class FaixaImpostoRenda
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(name: 'data_vigencia', type: Types::DATE_MUTABLE)]
    private \DateTimeInterface $dataVigencia;

    #[ORM\Column(name: 'valor_inicial', type: Types::DECIMAL, precision: 15, scale: 2)]
    private string $valorInicial;

    #[ORM\Column(name: 'valor_final', type: Types::DECIMAL, precision: 15, scale: 2, nullable: true)]
    private ?string $valorFinal = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    private string $aliquota;

    #[ORM\Column(name: 'parcela_deduzir', type: Types::DECIMAL, precision: 15, scale: 2, options: ['default' => '0'])]
    private string $parcelaDeduzir = '0.00';

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    private bool $ativo = true;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $updatedAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDataVigencia(): \DateTimeInterface
    {
        return $this->dataVigencia;
    }

    public function setDataVigencia(\DateTimeInterface $dataVigencia): self
    {
        $this->dataVigencia = $dataVigencia;
        return $this;
    }

    public function getValorInicial(): string
    {
        return $this->valorInicial;
    }

    public function setValorInicial(string $valorInicial): self
    {
        $this->valorInicial = $valorInicial;
        return $this;
    }

    public function getValorFinal(): ?string
    {
        return $this->valorFinal;
    }

    public function setValorFinal(?string $valorFinal): self
    {
        $this->valorFinal = $valorFinal;
        return $this;
    }

    public function getAliquota(): string
    {
        return $this->aliquota;
    }

    public function setAliquota(string $aliquota): self
    {
        $this->aliquota = $aliquota;
        return $this;
    }

    public function getParcelaDeduzir(): string
    {
        return $this->parcelaDeduzir;
    }

    public function setParcelaDeduzir(string $parcelaDeduzir): self
    {
        $this->parcelaDeduzir = $parcelaDeduzir;
        return $this;
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

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    #[ORM\PreUpdate]
    public function preUpdate(): void
    {
        $this->updatedAt = new \DateTime();
    }
}
