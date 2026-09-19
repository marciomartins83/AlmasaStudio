<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ContratoReajusteHistoricoRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ContratoReajusteHistoricoRepository::class)]
#[ORM\Table(name: 'contratos_reajustes_historico')]
#[ORM\Index(name: 'idx_reajustes_data', columns: ['data_reajuste'])]
class ContratoReajusteHistorico
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ImoveisContratos::class)]
    #[ORM\JoinColumn(name: 'contrato_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ImoveisContratos $contrato;

    #[ORM\Column(name: 'data_reajuste', type: Types::DATE_MUTABLE)]
    private \DateTimeInterface $dataReajuste;

    #[ORM\Column(name: 'competencia_base', type: Types::STRING, length: 7)]
    private string $competenciaBase;

    #[ORM\Column(name: 'competencia_atual', type: Types::STRING, length: 7)]
    private string $competenciaAtual;

    #[ORM\Column(name: 'indice_tipo', type: Types::STRING, length: 20)]
    private string $indiceTipo;

    #[ORM\Column(name: 'tipo_valor', type: Types::STRING, length: 10)]
    private string $tipoValor;

    #[ORM\Column(name: 'indice_valor_anterior', type: Types::DECIMAL, precision: 15, scale: 6, nullable: true)]
    private ?string $indiceValorAnterior = null;

    #[ORM\Column(name: 'indice_valor_atual', type: Types::DECIMAL, precision: 15, scale: 6, nullable: true)]
    private ?string $indiceValorAtual = null;

    #[ORM\Column(name: 'percentual_aplicado', type: Types::DECIMAL, precision: 8, scale: 4, nullable: true)]
    private ?string $percentualAplicado = null;

    #[ORM\Column(name: 'fator_aplicado', type: Types::DECIMAL, precision: 10, scale: 6)]
    private string $fatorAplicado;

    #[ORM\Column(name: 'valor_anterior', type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $valorAnterior;

    #[ORM\Column(name: 'valor_novo', type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $valorNovo;

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

    public function getContrato(): ImoveisContratos
    {
        return $this->contrato;
    }

    public function setContrato(ImoveisContratos $contrato): self
    {
        $this->contrato = $contrato;
        return $this;
    }

    public function getDataReajuste(): \DateTimeInterface
    {
        return $this->dataReajuste;
    }

    public function setDataReajuste(\DateTimeInterface $dataReajuste): self
    {
        $this->dataReajuste = $dataReajuste;
        return $this;
    }

    public function getCompetenciaBase(): string
    {
        return $this->competenciaBase;
    }

    public function setCompetenciaBase(string $competenciaBase): self
    {
        $this->competenciaBase = $competenciaBase;
        return $this;
    }

    public function getCompetenciaAtual(): string
    {
        return $this->competenciaAtual;
    }

    public function setCompetenciaAtual(string $competenciaAtual): self
    {
        $this->competenciaAtual = $competenciaAtual;
        return $this;
    }

    public function getIndiceTipo(): string
    {
        return $this->indiceTipo;
    }

    public function setIndiceTipo(string $indiceTipo): self
    {
        $this->indiceTipo = $indiceTipo;
        return $this;
    }

    public function getTipoValor(): string
    {
        return $this->tipoValor;
    }

    public function setTipoValor(string $tipoValor): self
    {
        $this->tipoValor = $tipoValor;
        return $this;
    }

    public function getIndiceValorAnterior(): ?string
    {
        return $this->indiceValorAnterior;
    }

    public function setIndiceValorAnterior(?string $indiceValorAnterior): self
    {
        $this->indiceValorAnterior = $indiceValorAnterior;
        return $this;
    }

    public function getIndiceValorAtual(): ?string
    {
        return $this->indiceValorAtual;
    }

    public function setIndiceValorAtual(?string $indiceValorAtual): self
    {
        $this->indiceValorAtual = $indiceValorAtual;
        return $this;
    }

    public function getPercentualAplicado(): ?string
    {
        return $this->percentualAplicado;
    }

    public function setPercentualAplicado(?string $percentualAplicado): self
    {
        $this->percentualAplicado = $percentualAplicado;
        return $this;
    }

    public function getFatorAplicado(): string
    {
        return $this->fatorAplicado;
    }

    public function setFatorAplicado(string $fatorAplicado): self
    {
        $this->fatorAplicado = $fatorAplicado;
        return $this;
    }

    public function getValorAnterior(): string
    {
        return $this->valorAnterior;
    }

    public function setValorAnterior(string $valorAnterior): self
    {
        $this->valorAnterior = $valorAnterior;
        return $this;
    }

    public function getValorNovo(): string
    {
        return $this->valorNovo;
    }

    public function setValorNovo(string $valorNovo): self
    {
        $this->valorNovo = $valorNovo;
        return $this;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }
}
