<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ImoveisMedidoresRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ImoveisMedidoresRepository::class)]
#[ORM\Table(name: 'imoveis_medidores')]
#[ORM\UniqueConstraint(name: 'uk_imovel_tipo_medidor', columns: ['id_imovel', 'tipo_medidor'])]
class ImoveisMedidores
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Imoveis::class, inversedBy: 'medidores')]
    #[ORM\JoinColumn(name: 'id_imovel', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Imoveis $imovel;

    #[ORM\Column(name: 'tipo_medidor', type: Types::STRING, length: 20)]
    private string $tipoMedidor;

    #[ORM\Column(name: 'numero_medidor', type: Types::STRING, length: 50)]
    private string $numeroMedidor;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    private ?string $concessionaria = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $observacoes = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    private bool $ativo = true;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE, options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTimeInterface $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
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

    public function getTipoMedidor(): string
    {
        return $this->tipoMedidor;
    }

    public function setTipoMedidor(string $tipoMedidor): self
    {
        $this->tipoMedidor = $tipoMedidor;
        return $this;
    }

    public function getNumeroMedidor(): string
    {
        return $this->numeroMedidor;
    }

    public function setNumeroMedidor(string $numeroMedidor): self
    {
        $this->numeroMedidor = $numeroMedidor;
        return $this;
    }

    public function getConcessionaria(): ?string
    {
        return $this->concessionaria;
    }

    public function setConcessionaria(?string $concessionaria): self
    {
        $this->concessionaria = $concessionaria;
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

    public function isAtivo(): bool
    {
        return $this->ativo;
    }

    public function setAtivo(bool $ativo): self
    {
        $this->ativo = $ativo;
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
}
