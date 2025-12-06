<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\BaixasFinanceirasRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BaixasFinanceirasRepository::class)]
#[ORM\Table(name: 'baixas_financeiras')]
class BaixasFinanceiras
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: LancamentosFinanceiros::class, inversedBy: 'baixas')]
    #[ORM\JoinColumn(name: 'id_lancamento', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?LancamentosFinanceiros $lancamento = null;

    #[ORM\ManyToOne(targetEntity: ContasBancarias::class)]
    #[ORM\JoinColumn(name: 'id_conta_bancaria', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?ContasBancarias $contaBancaria = null;

    #[ORM\Column(name: 'data_pagamento', type: Types::DATE_MUTABLE)]
    private \DateTimeInterface $dataPagamento;

    #[ORM\Column(name: 'valor_pago', type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $valorPago = '0.00';

    #[ORM\Column(name: 'valor_multa_paga', type: Types::DECIMAL, precision: 10, scale: 2, nullable: true, options: ['default' => '0.00'])]
    private ?string $valorMultaPaga = '0.00';

    #[ORM\Column(name: 'valor_juros_pago', type: Types::DECIMAL, precision: 10, scale: 2, nullable: true, options: ['default' => '0.00'])]
    private ?string $valorJurosPago = '0.00';

    #[ORM\Column(name: 'valor_desconto', type: Types::DECIMAL, precision: 10, scale: 2, nullable: true, options: ['default' => '0.00'])]
    private ?string $valorDesconto = '0.00';

    #[ORM\Column(name: 'valor_total_pago', type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $valorTotalPago = '0.00';

    #[ORM\Column(name: 'forma_pagamento', type: Types::STRING, length: 30, options: ['default' => 'boleto'])]
    private string $formaPagamento = 'boleto';

    #[ORM\Column(name: 'numero_documento', type: Types::STRING, length: 50, nullable: true)]
    private ?string $numeroDocumento = null;

    #[ORM\Column(name: 'numero_autenticacao', type: Types::STRING, length: 100, nullable: true)]
    private ?string $numeroAutenticacao = null;

    #[ORM\Column(name: 'tipo_baixa', type: Types::STRING, length: 20, options: ['default' => 'normal'])]
    private string $tipoBaixa = 'normal';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $observacoes = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE, options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $estornada = false;

    #[ORM\Column(name: 'data_estorno', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dataEstorno = null;

    #[ORM\Column(name: 'motivo_estorno', type: Types::TEXT, nullable: true)]
    private ?string $motivoEstorno = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->dataPagamento = new \DateTime();
    }

    // === GETTERS E SETTERS ===

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLancamento(): ?LancamentosFinanceiros
    {
        return $this->lancamento;
    }

    public function setLancamento(?LancamentosFinanceiros $lancamento): self
    {
        $this->lancamento = $lancamento;
        return $this;
    }

    public function getContaBancaria(): ?ContasBancarias
    {
        return $this->contaBancaria;
    }

    public function setContaBancaria(?ContasBancarias $contaBancaria): self
    {
        $this->contaBancaria = $contaBancaria;
        return $this;
    }

    public function getDataPagamento(): \DateTimeInterface
    {
        return $this->dataPagamento;
    }

    public function setDataPagamento(\DateTimeInterface $dataPagamento): self
    {
        $this->dataPagamento = $dataPagamento;
        return $this;
    }

    public function getValorPago(): string
    {
        return $this->valorPago;
    }

    public function setValorPago(string $valorPago): self
    {
        $this->valorPago = $valorPago;
        return $this;
    }

    public function getValorMultaPaga(): ?string
    {
        return $this->valorMultaPaga;
    }

    public function setValorMultaPaga(?string $valorMultaPaga): self
    {
        $this->valorMultaPaga = $valorMultaPaga;
        return $this;
    }

    public function getValorJurosPago(): ?string
    {
        return $this->valorJurosPago;
    }

    public function setValorJurosPago(?string $valorJurosPago): self
    {
        $this->valorJurosPago = $valorJurosPago;
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

    public function getValorTotalPago(): string
    {
        return $this->valorTotalPago;
    }

    public function setValorTotalPago(string $valorTotalPago): self
    {
        $this->valorTotalPago = $valorTotalPago;
        return $this;
    }

    public function getFormaPagamento(): string
    {
        return $this->formaPagamento;
    }

    public function setFormaPagamento(string $formaPagamento): self
    {
        $this->formaPagamento = $formaPagamento;
        return $this;
    }

    public function getNumeroDocumento(): ?string
    {
        return $this->numeroDocumento;
    }

    public function setNumeroDocumento(?string $numeroDocumento): self
    {
        $this->numeroDocumento = $numeroDocumento;
        return $this;
    }

    public function getNumeroAutenticacao(): ?string
    {
        return $this->numeroAutenticacao;
    }

    public function setNumeroAutenticacao(?string $numeroAutenticacao): self
    {
        $this->numeroAutenticacao = $numeroAutenticacao;
        return $this;
    }

    public function getTipoBaixa(): string
    {
        return $this->tipoBaixa;
    }

    public function setTipoBaixa(string $tipoBaixa): self
    {
        $this->tipoBaixa = $tipoBaixa;
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

    public function isEstornada(): bool
    {
        return $this->estornada;
    }

    public function setEstornada(bool $estornada): self
    {
        $this->estornada = $estornada;
        return $this;
    }

    public function getDataEstorno(): ?\DateTimeInterface
    {
        return $this->dataEstorno;
    }

    public function setDataEstorno(?\DateTimeInterface $dataEstorno): self
    {
        $this->dataEstorno = $dataEstorno;
        return $this;
    }

    public function getMotivoEstorno(): ?string
    {
        return $this->motivoEstorno;
    }

    public function setMotivoEstorno(?string $motivoEstorno): self
    {
        $this->motivoEstorno = $motivoEstorno;
        return $this;
    }

    // === MÉTODOS AUXILIARES ===

    /**
     * Calcula total da baixa
     */
    public function calcularTotal(): self
    {
        $total = (float) $this->valorPago
            + (float) ($this->valorMultaPaga ?? 0)
            + (float) ($this->valorJurosPago ?? 0)
            - (float) ($this->valorDesconto ?? 0);

        $this->valorTotalPago = number_format($total, 2, '.', '');

        return $this;
    }
}
