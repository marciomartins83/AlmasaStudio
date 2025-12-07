<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\LancamentosFinanceirosRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LancamentosFinanceirosRepository::class)]
#[ORM\Table(
    name: 'lancamentos_financeiros',
    indexes: [
        new ORM\Index(name: 'idx_lanc_contrato', columns: ['id_contrato']),
        new ORM\Index(name: 'idx_lanc_imovel', columns: ['id_imovel']),
        new ORM\Index(name: 'idx_lanc_inquilino', columns: ['id_inquilino']),
        new ORM\Index(name: 'idx_lanc_proprietario', columns: ['id_proprietario']),
        new ORM\Index(name: 'idx_lanc_vencimento', columns: ['data_vencimento']),
        new ORM\Index(name: 'idx_lanc_situacao', columns: ['situacao']),
        new ORM\Index(name: 'idx_lanc_competencia', columns: ['competencia']),
    ]
)]
#[ORM\HasLifecycleCallbacks]
class LancamentosFinanceiros
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    // === RELACIONAMENTOS ===

    #[ORM\ManyToOne(targetEntity: ImoveisContratos::class)]
    #[ORM\JoinColumn(name: 'id_contrato', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?ImoveisContratos $contrato = null;

    #[ORM\ManyToOne(targetEntity: Imoveis::class)]
    #[ORM\JoinColumn(name: 'id_imovel', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Imoveis $imovel = null;

    #[ORM\ManyToOne(targetEntity: Pessoas::class)]
    #[ORM\JoinColumn(name: 'id_inquilino', referencedColumnName: 'idpessoa', nullable: true, onDelete: 'SET NULL')]
    private ?Pessoas $inquilino = null;

    #[ORM\ManyToOne(targetEntity: Pessoas::class)]
    #[ORM\JoinColumn(name: 'id_proprietario', referencedColumnName: 'idpessoa', nullable: true, onDelete: 'SET NULL')]
    private ?Pessoas $proprietario = null;

    #[ORM\ManyToOne(targetEntity: PlanoContas::class)]
    #[ORM\JoinColumn(name: 'id_conta', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?PlanoContas $conta = null;

    #[ORM\ManyToOne(targetEntity: ContasBancarias::class)]
    #[ORM\JoinColumn(name: 'id_conta_bancaria', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?ContasBancarias $contaBancaria = null;

    /**
     * @var Collection<int, BaixasFinanceiras>
     */
    #[ORM\OneToMany(targetEntity: BaixasFinanceiras::class, mappedBy: 'lancamento', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $baixas;

    // === IDENTIFICAÇÃO ===

    #[ORM\Column(name: 'numero_acordo', type: Types::INTEGER, nullable: true)]
    private ?int $numeroAcordo = null;

    #[ORM\Column(name: 'numero_parcela', type: Types::INTEGER, nullable: true)]
    private ?int $numeroParcela = 1;

    #[ORM\Column(name: 'numero_recibo', type: Types::STRING, length: 20, nullable: true)]
    private ?string $numeroRecibo = null;

    #[ORM\Column(name: 'numero_boleto', type: Types::STRING, length: 50, nullable: true)]
    private ?string $numeroBoleto = null;

    // === COMPETÊNCIA ===

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private \DateTimeInterface $competencia;

    #[ORM\Column(name: 'data_lancamento', type: Types::DATE_MUTABLE)]
    private \DateTimeInterface $dataLancamento;

    #[ORM\Column(name: 'data_vencimento', type: Types::DATE_MUTABLE)]
    private \DateTimeInterface $dataVencimento;

    #[ORM\Column(name: 'data_limite', type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dataLimite = null;

    // === VALORES ORIGINAIS ===

    #[ORM\Column(name: 'valor_principal', type: Types::DECIMAL, precision: 10, scale: 2, options: ['default' => '0.00'])]
    private string $valorPrincipal = '0.00';

    #[ORM\Column(name: 'valor_condominio', type: Types::DECIMAL, precision: 10, scale: 2, nullable: true, options: ['default' => '0.00'])]
    private ?string $valorCondominio = '0.00';

    #[ORM\Column(name: 'valor_iptu', type: Types::DECIMAL, precision: 10, scale: 2, nullable: true, options: ['default' => '0.00'])]
    private ?string $valorIptu = '0.00';

    #[ORM\Column(name: 'valor_agua', type: Types::DECIMAL, precision: 10, scale: 2, nullable: true, options: ['default' => '0.00'])]
    private ?string $valorAgua = '0.00';

    #[ORM\Column(name: 'valor_luz', type: Types::DECIMAL, precision: 10, scale: 2, nullable: true, options: ['default' => '0.00'])]
    private ?string $valorLuz = '0.00';

    #[ORM\Column(name: 'valor_gas', type: Types::DECIMAL, precision: 10, scale: 2, nullable: true, options: ['default' => '0.00'])]
    private ?string $valorGas = '0.00';

    #[ORM\Column(name: 'valor_outros', type: Types::DECIMAL, precision: 10, scale: 2, nullable: true, options: ['default' => '0.00'])]
    private ?string $valorOutros = '0.00';

    // === ACRÉSCIMOS E DESCONTOS ===

    #[ORM\Column(name: 'valor_multa', type: Types::DECIMAL, precision: 10, scale: 2, nullable: true, options: ['default' => '0.00'])]
    private ?string $valorMulta = '0.00';

    #[ORM\Column(name: 'valor_juros', type: Types::DECIMAL, precision: 10, scale: 2, nullable: true, options: ['default' => '0.00'])]
    private ?string $valorJuros = '0.00';

    #[ORM\Column(name: 'valor_honorarios', type: Types::DECIMAL, precision: 10, scale: 2, nullable: true, options: ['default' => '0.00'])]
    private ?string $valorHonorarios = '0.00';

    #[ORM\Column(name: 'valor_desconto', type: Types::DECIMAL, precision: 10, scale: 2, nullable: true, options: ['default' => '0.00'])]
    private ?string $valorDesconto = '0.00';

    #[ORM\Column(name: 'valor_bonificacao', type: Types::DECIMAL, precision: 10, scale: 2, nullable: true, options: ['default' => '0.00'])]
    private ?string $valorBonificacao = '0.00';

    // === TOTAIS ===

    #[ORM\Column(name: 'valor_total', type: Types::DECIMAL, precision: 10, scale: 2, options: ['default' => '0.00'])]
    private string $valorTotal = '0.00';

    #[ORM\Column(name: 'valor_pago', type: Types::DECIMAL, precision: 10, scale: 2, nullable: true, options: ['default' => '0.00'])]
    private ?string $valorPago = '0.00';

    #[ORM\Column(name: 'valor_saldo', type: Types::DECIMAL, precision: 10, scale: 2, nullable: true, options: ['default' => '0.00'])]
    private ?string $valorSaldo = '0.00';

    // === STATUS E CONTROLE ===

    #[ORM\Column(type: Types::STRING, length: 20, nullable: true)]
    private ?string $situacao = 'aberto';

    #[ORM\Column(name: 'tipo_lancamento', type: Types::STRING, length: 30, nullable: true)]
    private ?string $tipoLancamento = 'aluguel';

    #[ORM\Column(type: Types::STRING, length: 30, nullable: true)]
    private ?string $origem = 'contrato';

    // === OBSERVAÇÕES ===

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $descricao = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $historico = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $observacoes = null;

    // === AUDITORIA ===

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    // === FLAGS ===

    #[ORM\Column(name: 'gerado_automaticamente', type: Types::BOOLEAN, nullable: true)]
    private ?bool $geradoAutomaticamente = false;

    #[ORM\Column(type: Types::BOOLEAN, nullable: true)]
    private ?bool $ativo = true;

    #[ORM\Column(name: 'enviado_email', type: Types::BOOLEAN, nullable: true)]
    private ?bool $enviadoEmail = false;

    #[ORM\Column(type: Types::BOOLEAN, nullable: true)]
    private ?bool $impresso = false;

    #[ORM\Column(name: 'data_geracao', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dataGeracao = null;

    #[ORM\Column(name: 'data_envio_email', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dataEnvioEmail = null;

    #[ORM\Column(name: 'data_impressao', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dataImpressao = null;

    #[ORM\Column(name: 'created_by', type: Types::INTEGER, nullable: true)]
    private ?int $createdBy = null;

    #[ORM\Column(name: 'updated_by', type: Types::INTEGER, nullable: true)]
    private ?int $updatedBy = null;

    public function __construct()
    {
        $this->baixas = new ArrayCollection();
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
        $this->competencia = new \DateTime();
        $this->dataLancamento = new \DateTime();
        $this->dataVencimento = new \DateTime();
    }

    // === GETTERS E SETTERS ===

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContrato(): ?ImoveisContratos
    {
        return $this->contrato;
    }

    public function setContrato(?ImoveisContratos $contrato): self
    {
        $this->contrato = $contrato;
        return $this;
    }

    public function getImovel(): ?Imoveis
    {
        return $this->imovel;
    }

    public function setImovel(?Imoveis $imovel): self
    {
        $this->imovel = $imovel;
        return $this;
    }

    public function getInquilino(): ?Pessoas
    {
        return $this->inquilino;
    }

    public function setInquilino(?Pessoas $inquilino): self
    {
        $this->inquilino = $inquilino;
        return $this;
    }

    public function getProprietario(): ?Pessoas
    {
        return $this->proprietario;
    }

    public function setProprietario(?Pessoas $proprietario): self
    {
        $this->proprietario = $proprietario;
        return $this;
    }

    public function getConta(): ?PlanoContas
    {
        return $this->conta;
    }

    public function setConta(?PlanoContas $conta): self
    {
        $this->conta = $conta;
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

    /**
     * @return Collection<int, BaixasFinanceiras>
     */
    public function getBaixas(): Collection
    {
        return $this->baixas;
    }

    public function addBaixa(BaixasFinanceiras $baixa): self
    {
        if (!$this->baixas->contains($baixa)) {
            $this->baixas->add($baixa);
            $baixa->setLancamento($this);
        }
        return $this;
    }

    public function removeBaixa(BaixasFinanceiras $baixa): self
    {
        if ($this->baixas->removeElement($baixa)) {
            if ($baixa->getLancamento() === $this) {
                $baixa->setLancamento(null);
            }
        }
        return $this;
    }

    public function getNumeroAcordo(): ?int
    {
        return $this->numeroAcordo;
    }

    public function setNumeroAcordo(?int $numeroAcordo): self
    {
        $this->numeroAcordo = $numeroAcordo;
        return $this;
    }

    public function getNumeroParcela(): ?int
    {
        return $this->numeroParcela;
    }

    public function setNumeroParcela(?int $numeroParcela): self
    {
        $this->numeroParcela = $numeroParcela;
        return $this;
    }

    public function getNumeroRecibo(): ?string
    {
        return $this->numeroRecibo;
    }

    public function setNumeroRecibo(?string $numeroRecibo): self
    {
        $this->numeroRecibo = $numeroRecibo;
        return $this;
    }

    public function getNumeroBoleto(): ?string
    {
        return $this->numeroBoleto;
    }

    public function setNumeroBoleto(?string $numeroBoleto): self
    {
        $this->numeroBoleto = $numeroBoleto;
        return $this;
    }

    public function getCompetencia(): \DateTimeInterface
    {
        return $this->competencia;
    }

    public function setCompetencia(\DateTimeInterface $competencia): self
    {
        $this->competencia = $competencia;
        return $this;
    }

    public function getDataLancamento(): \DateTimeInterface
    {
        return $this->dataLancamento;
    }

    public function setDataLancamento(\DateTimeInterface $dataLancamento): self
    {
        $this->dataLancamento = $dataLancamento;
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

    public function getDataLimite(): ?\DateTimeInterface
    {
        return $this->dataLimite;
    }

    public function setDataLimite(?\DateTimeInterface $dataLimite): self
    {
        $this->dataLimite = $dataLimite;
        return $this;
    }

    public function getValorPrincipal(): string
    {
        return $this->valorPrincipal;
    }

    public function setValorPrincipal(string $valorPrincipal): self
    {
        $this->valorPrincipal = $valorPrincipal;
        return $this;
    }

    public function getValorCondominio(): ?string
    {
        return $this->valorCondominio;
    }

    public function setValorCondominio(?string $valorCondominio): self
    {
        $this->valorCondominio = $valorCondominio;
        return $this;
    }

    public function getValorIptu(): ?string
    {
        return $this->valorIptu;
    }

    public function setValorIptu(?string $valorIptu): self
    {
        $this->valorIptu = $valorIptu;
        return $this;
    }

    public function getValorAgua(): ?string
    {
        return $this->valorAgua;
    }

    public function setValorAgua(?string $valorAgua): self
    {
        $this->valorAgua = $valorAgua;
        return $this;
    }

    public function getValorLuz(): ?string
    {
        return $this->valorLuz;
    }

    public function setValorLuz(?string $valorLuz): self
    {
        $this->valorLuz = $valorLuz;
        return $this;
    }

    public function getValorGas(): ?string
    {
        return $this->valorGas;
    }

    public function setValorGas(?string $valorGas): self
    {
        $this->valorGas = $valorGas;
        return $this;
    }

    public function getValorOutros(): ?string
    {
        return $this->valorOutros;
    }

    public function setValorOutros(?string $valorOutros): self
    {
        $this->valorOutros = $valorOutros;
        return $this;
    }

    public function getValorMulta(): ?string
    {
        return $this->valorMulta;
    }

    public function setValorMulta(?string $valorMulta): self
    {
        $this->valorMulta = $valorMulta;
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

    public function getValorHonorarios(): ?string
    {
        return $this->valorHonorarios;
    }

    public function setValorHonorarios(?string $valorHonorarios): self
    {
        $this->valorHonorarios = $valorHonorarios;
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

    public function getValorBonificacao(): ?string
    {
        return $this->valorBonificacao;
    }

    public function setValorBonificacao(?string $valorBonificacao): self
    {
        $this->valorBonificacao = $valorBonificacao;
        return $this;
    }

    public function getValorTotal(): string
    {
        return $this->valorTotal;
    }

    public function setValorTotal(string $valorTotal): self
    {
        $this->valorTotal = $valorTotal;
        return $this;
    }

    public function getValorPago(): ?string
    {
        return $this->valorPago;
    }

    public function setValorPago(?string $valorPago): self
    {
        $this->valorPago = $valorPago;
        return $this;
    }

    public function getValorSaldo(): ?string
    {
        return $this->valorSaldo;
    }

    public function setValorSaldo(?string $valorSaldo): self
    {
        $this->valorSaldo = $valorSaldo;
        return $this;
    }

    public function getSituacao(): ?string
    {
        return $this->situacao;
    }

    public function setSituacao(?string $situacao): self
    {
        $this->situacao = $situacao;
        return $this;
    }

    public function getTipoLancamento(): ?string
    {
        return $this->tipoLancamento;
    }

    public function setTipoLancamento(?string $tipoLancamento): self
    {
        $this->tipoLancamento = $tipoLancamento;
        return $this;
    }

    public function getOrigem(): ?string
    {
        return $this->origem;
    }

    public function setOrigem(?string $origem): self
    {
        $this->origem = $origem;
        return $this;
    }

    public function getDescricao(): ?string
    {
        return $this->descricao;
    }

    public function setDescricao(?string $descricao): self
    {
        $this->descricao = $descricao;
        return $this;
    }

    public function getHistorico(): ?string
    {
        return $this->historico;
    }

    public function setHistorico(?string $historico): self
    {
        $this->historico = $historico;
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

    public function isGeradoAutomaticamente(): ?bool
    {
        return $this->geradoAutomaticamente;
    }

    public function setGeradoAutomaticamente(?bool $geradoAutomaticamente): self
    {
        $this->geradoAutomaticamente = $geradoAutomaticamente;
        return $this;
    }

    public function isAtivo(): ?bool
    {
        return $this->ativo;
    }

    public function setAtivo(?bool $ativo): self
    {
        $this->ativo = $ativo;
        return $this;
    }

    public function isEnviadoEmail(): ?bool
    {
        return $this->enviadoEmail;
    }

    public function setEnviadoEmail(?bool $enviadoEmail): self
    {
        $this->enviadoEmail = $enviadoEmail;
        return $this;
    }

    public function isImpresso(): ?bool
    {
        return $this->impresso;
    }

    public function setImpresso(?bool $impresso): self
    {
        $this->impresso = $impresso;
        return $this;
    }

    #[ORM\PreUpdate]
    public function preUpdate(): void
    {
        $this->updatedAt = new \DateTime();
    }

    // === MÉTODOS AUXILIARES ===

    /**
     * Calcula e atualiza o valor total
     */
    public function calcularTotal(): self
    {
        $total = (float) $this->valorPrincipal
            + (float) ($this->valorCondominio ?? 0)
            + (float) ($this->valorIptu ?? 0)
            + (float) ($this->valorAgua ?? 0)
            + (float) ($this->valorLuz ?? 0)
            + (float) ($this->valorGas ?? 0)
            + (float) ($this->valorOutros ?? 0)
            + (float) ($this->valorMulta ?? 0)
            + (float) ($this->valorJuros ?? 0)
            + (float) ($this->valorHonorarios ?? 0)
            - (float) ($this->valorDesconto ?? 0)
            - (float) ($this->valorBonificacao ?? 0);

        $this->valorTotal = number_format($total, 2, '.', '');
        $this->calcularSaldo();

        return $this;
    }

    /**
     * Calcula e atualiza o saldo
     */
    public function calcularSaldo(): self
    {
        $saldo = (float) $this->valorTotal - (float) ($this->valorPago ?? 0);
        $this->valorSaldo = number_format($saldo, 2, '.', '');

        return $this;
    }

    /**
     * Verifica se está em atraso
     */
    public function isEmAtraso(): bool
    {
        if ($this->situacao === 'pago') {
            return false;
        }

        $hoje = new \DateTime();
        return $this->dataVencimento < $hoje;
    }

    /**
     * Calcula dias em atraso
     */
    public function getDiasAtraso(): int
    {
        if (!$this->isEmAtraso()) {
            return 0;
        }

        $hoje = new \DateTime();
        $interval = $this->dataVencimento->diff($hoje);

        return $interval->days;
    }

    /**
     * Verifica se está totalmente pago
     */
    public function isPago(): bool
    {
        return $this->situacao === 'pago' || (float) $this->valorSaldo <= 0;
    }

    /**
     * Verifica se está parcialmente pago
     */
    public function isParcial(): bool
    {
        return (float) $this->valorPago > 0 && (float) $this->valorSaldo > 0;
    }

    /**
     * Retorna descrição da competência (MM/YYYY)
     */
    public function getCompetenciaFormatada(): string
    {
        return $this->competencia->format('m/Y');
    }

    public function getDataGeracao(): ?\DateTimeInterface
    {
        return $this->dataGeracao;
    }

    public function setDataGeracao(?\DateTimeInterface $dataGeracao): self
    {
        $this->dataGeracao = $dataGeracao;
        return $this;
    }

    public function getDataEnvioEmail(): ?\DateTimeInterface
    {
        return $this->dataEnvioEmail;
    }

    public function setDataEnvioEmail(?\DateTimeInterface $dataEnvioEmail): self
    {
        $this->dataEnvioEmail = $dataEnvioEmail;
        return $this;
    }

    public function getDataImpressao(): ?\DateTimeInterface
    {
        return $this->dataImpressao;
    }

    public function setDataImpressao(?\DateTimeInterface $dataImpressao): self
    {
        $this->dataImpressao = $dataImpressao;
        return $this;
    }

    public function getCreatedBy(): ?int
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?int $createdBy): self
    {
        $this->createdBy = $createdBy;
        return $this;
    }

    public function getUpdatedBy(): ?int
    {
        return $this->updatedBy;
    }

    public function setUpdatedBy(?int $updatedBy): self
    {
        $this->updatedBy = $updatedBy;
        return $this;
    }
}
