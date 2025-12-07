<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ImoveisContratosRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ImoveisContratosRepository::class)]
#[ORM\Table(
    name: 'imoveis_contratos',
    indexes: [
        new ORM\Index(name: 'idx_contratos_status', columns: ['status']),
        new ORM\Index(name: 'idx_contratos_ativo', columns: ['ativo']),
        new ORM\Index(name: 'idx_contratos_data_fim', columns: ['data_fim']),
        new ORM\Index(name: 'idx_contratos_data_proximo_reajuste', columns: ['data_proximo_reajuste']),
    ]
)]
#[ORM\HasLifecycleCallbacks]
class ImoveisContratos
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Imoveis::class, inversedBy: 'contratos')]
    #[ORM\JoinColumn(name: 'id_imovel', referencedColumnName: 'id', nullable: false)]
    private Imoveis $imovel;

    #[ORM\ManyToOne(targetEntity: Pessoas::class)]
    #[ORM\JoinColumn(name: 'id_pessoa_locatario', referencedColumnName: 'idpessoa', nullable: true)]
    private ?Pessoas $pessoaLocatario = null;

    #[ORM\ManyToOne(targetEntity: Pessoas::class)]
    #[ORM\JoinColumn(name: 'id_pessoa_fiador', referencedColumnName: 'idpessoa', nullable: true)]
    private ?Pessoas $pessoaFiador = null;

    #[ORM\Column(name: 'tipo_contrato', type: Types::STRING, length: 20)]
    private string $tipoContrato;

    #[ORM\Column(name: 'data_inicio', type: Types::DATE_MUTABLE)]
    private \DateTimeInterface $dataInicio;

    #[ORM\Column(name: 'data_fim', type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dataFim = null;

    #[ORM\Column(name: 'valor_contrato', type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $valorContrato;

    #[ORM\Column(name: 'dia_vencimento', type: Types::INTEGER, nullable: true)]
    private ?int $diaVencimento = null;

    #[ORM\Column(type: Types::STRING, length: 20)]
    private string $status;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $observacoes = null;

    #[ORM\Column(name: 'taxa_administracao', type: Types::DECIMAL, precision: 5, scale: 2, nullable: true, options: ['default' => '10.00'])]
    private ?string $taxaAdministracao = '10.00';

    #[ORM\Column(name: 'tipo_garantia', type: Types::STRING, length: 30, nullable: true, options: ['default' => 'fiador'])]
    private ?string $tipoGarantia = 'fiador';

    #[ORM\Column(name: 'valor_caucao', type: Types::DECIMAL, precision: 10, scale: 2, nullable: true, options: ['default' => '0'])]
    private ?string $valorCaucao = '0.00';

    #[ORM\Column(name: 'indice_reajuste', type: Types::STRING, length: 20, nullable: true, options: ['default' => 'IGPM'])]
    private ?string $indiceReajuste = 'IGPM';

    #[ORM\Column(name: 'periodicidade_reajuste', type: Types::STRING, length: 20, nullable: true, options: ['default' => 'anual'])]
    private ?string $periodicidadeReajuste = 'anual';

    #[ORM\Column(name: 'data_proximo_reajuste', type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dataProximoReajuste = null;

    #[ORM\Column(name: 'multa_rescisao', type: Types::DECIMAL, precision: 10, scale: 2, nullable: true, options: ['default' => '0'])]
    private ?string $multaRescisao = '0.00';

    #[ORM\Column(name: 'carencia_dias', type: Types::INTEGER, nullable: true, options: ['default' => '0'])]
    private ?int $carenciaDias = 0;

    #[ORM\Column(name: 'gera_boleto', type: Types::BOOLEAN, nullable: true, options: ['default' => 'true'])]
    private ?bool $geraBoleto = true;

    #[ORM\Column(name: 'envia_email', type: Types::BOOLEAN, nullable: true, options: ['default' => 'true'])]
    private ?bool $enviaEmail = true;

    #[ORM\Column(type: Types::BOOLEAN, nullable: true, options: ['default' => 'true'])]
    private ?bool $ativo = true;

    #[ORM\Column(name: 'dias_antecedencia_boleto', type: Types::INTEGER, options: ['default' => 5])]
    private int $diasAntecedenciaBoleto = 5;

    /**
     * @var Collection<int, ContratosItensCobranca>
     */
    #[ORM\OneToMany(targetEntity: ContratosItensCobranca::class, mappedBy: 'contrato', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $itensCobranca;

    /**
     * @var Collection<int, ContratosCobrancas>
     */
    #[ORM\OneToMany(targetEntity: ContratosCobrancas::class, mappedBy: 'contrato', cascade: ['persist'], orphanRemoval: true)]
    private Collection $cobrancas;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE, options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_MUTABLE, options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
        $this->itensCobranca = new ArrayCollection();
        $this->cobrancas = new ArrayCollection();
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

    public function getPessoaLocatario(): ?Pessoas
    {
        return $this->pessoaLocatario;
    }

    public function setPessoaLocatario(?Pessoas $pessoaLocatario): self
    {
        $this->pessoaLocatario = $pessoaLocatario;
        return $this;
    }

    public function getPessoaFiador(): ?Pessoas
    {
        return $this->pessoaFiador;
    }

    public function setPessoaFiador(?Pessoas $pessoaFiador): self
    {
        $this->pessoaFiador = $pessoaFiador;
        return $this;
    }

    public function getTipoContrato(): string
    {
        return $this->tipoContrato;
    }

    public function setTipoContrato(string $tipoContrato): self
    {
        $this->tipoContrato = $tipoContrato;
        return $this;
    }

    public function getDataInicio(): \DateTimeInterface
    {
        return $this->dataInicio;
    }

    public function setDataInicio(\DateTimeInterface $dataInicio): self
    {
        $this->dataInicio = $dataInicio;
        return $this;
    }

    public function getDataFim(): ?\DateTimeInterface
    {
        return $this->dataFim;
    }

    public function setDataFim(?\DateTimeInterface $dataFim): self
    {
        $this->dataFim = $dataFim;
        return $this;
    }

    public function getValorContrato(): string
    {
        return $this->valorContrato;
    }

    public function setValorContrato(string $valorContrato): self
    {
        $this->valorContrato = $valorContrato;
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

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
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

    public function getTaxaAdministracao(): ?string
    {
        return $this->taxaAdministracao;
    }

    public function setTaxaAdministracao(?string $taxaAdministracao): self
    {
        $this->taxaAdministracao = $taxaAdministracao;
        return $this;
    }

    public function getTipoGarantia(): ?string
    {
        return $this->tipoGarantia;
    }

    public function setTipoGarantia(?string $tipoGarantia): self
    {
        $this->tipoGarantia = $tipoGarantia;
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

    public function getIndiceReajuste(): ?string
    {
        return $this->indiceReajuste;
    }

    public function setIndiceReajuste(?string $indiceReajuste): self
    {
        $this->indiceReajuste = $indiceReajuste;
        return $this;
    }

    public function getPeriodicidadeReajuste(): ?string
    {
        return $this->periodicidadeReajuste;
    }

    public function setPeriodicidadeReajuste(?string $periodicidadeReajuste): self
    {
        $this->periodicidadeReajuste = $periodicidadeReajuste;
        return $this;
    }

    public function getDataProximoReajuste(): ?\DateTimeInterface
    {
        return $this->dataProximoReajuste;
    }

    public function setDataProximoReajuste(?\DateTimeInterface $dataProximoReajuste): self
    {
        $this->dataProximoReajuste = $dataProximoReajuste;
        return $this;
    }

    public function getMultaRescisao(): ?string
    {
        return $this->multaRescisao;
    }

    public function setMultaRescisao(?string $multaRescisao): self
    {
        $this->multaRescisao = $multaRescisao;
        return $this;
    }

    public function getCarenciaDias(): ?int
    {
        return $this->carenciaDias;
    }

    public function setCarenciaDias(?int $carenciaDias): self
    {
        $this->carenciaDias = $carenciaDias;
        return $this;
    }

    public function isGeraBoleto(): ?bool
    {
        return $this->geraBoleto;
    }

    public function setGeraBoleto(?bool $geraBoleto): self
    {
        $this->geraBoleto = $geraBoleto;
        return $this;
    }

    public function isEnviaEmail(): ?bool
    {
        return $this->enviaEmail;
    }

    public function setEnviaEmail(?bool $enviaEmail): self
    {
        $this->enviaEmail = $enviaEmail;
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

    #[ORM\PreUpdate]
    public function preUpdate(): void
    {
        $this->updatedAt = new \DateTime();
    }

    public function getDuracaoMeses(): ?int
    {
        if (!$this->dataFim) {
            return null;
        }
        $interval = $this->dataInicio->diff($this->dataFim);
        return ($interval->y * 12) + $interval->m;
    }

    public function isVigente(): bool
    {
        $hoje = new \DateTime();
        return $this->status === 'ativo'
            && $this->dataInicio <= $hoje
            && ($this->dataFim === null || $this->dataFim >= $hoje);
    }

    public function getValorLiquidoProprietario(): float
    {
        $valor = (float) $this->valorContrato;
        $taxa = (float) $this->taxaAdministracao;
        return $valor - ($valor * $taxa / 100);
    }

    // ===== Dias Antecedência Boleto =====

    public function getDiasAntecedenciaBoleto(): int
    {
        return $this->diasAntecedenciaBoleto;
    }

    public function setDiasAntecedenciaBoleto(int $diasAntecedenciaBoleto): self
    {
        $this->diasAntecedenciaBoleto = $diasAntecedenciaBoleto;
        return $this;
    }

    // ===== Itens de Cobrança =====

    /**
     * @return Collection<int, ContratosItensCobranca>
     */
    public function getItensCobranca(): Collection
    {
        return $this->itensCobranca;
    }

    public function addItemCobranca(ContratosItensCobranca $item): self
    {
        if (!$this->itensCobranca->contains($item)) {
            $this->itensCobranca->add($item);
            $item->setContrato($this);
        }
        return $this;
    }

    public function removeItemCobranca(ContratosItensCobranca $item): self
    {
        if ($this->itensCobranca->removeElement($item)) {
            if ($item->getContrato() === $this) {
                // Orphan removal vai cuidar disso
            }
        }
        return $this;
    }

    /**
     * Retorna itens ativos de cobrança
     */
    public function getItensCobrancaAtivos(): Collection
    {
        return $this->itensCobranca->filter(fn($item) => $item->isAtivo());
    }

    // ===== Cobranças =====

    /**
     * @return Collection<int, ContratosCobrancas>
     */
    public function getCobrancas(): Collection
    {
        return $this->cobrancas;
    }

    public function addCobranca(ContratosCobrancas $cobranca): self
    {
        if (!$this->cobrancas->contains($cobranca)) {
            $this->cobrancas->add($cobranca);
            $cobranca->setContrato($this);
        }
        return $this;
    }

    public function removeCobranca(ContratosCobrancas $cobranca): self
    {
        $this->cobrancas->removeElement($cobranca);
        return $this;
    }

    /**
     * Busca cobrança por competência
     */
    public function getCobrancaPorCompetencia(string $competencia): ?ContratosCobrancas
    {
        foreach ($this->cobrancas as $cobranca) {
            if ($cobranca->getCompetencia() === $competencia) {
                return $cobranca;
            }
        }
        return null;
    }

    /**
     * Verifica se contrato está configurado para envio automático
     */
    public function isEnvioAutomaticoAtivo(): bool
    {
        return $this->isAtivo()
            && $this->isGeraBoleto()
            && $this->isEnviaEmail()
            && $this->isVigente();
    }

    /**
     * Calcula valor total da cobrança mensal baseado nos itens
     */
    public function calcularValorCobrancaMensal(): float
    {
        $valorBase = (float) $this->valorContrato;
        $total = 0;

        foreach ($this->getItensCobrancaAtivos() as $item) {
            $total += $item->calcularValorEfetivo($valorBase);
        }

        return $total;
    }
}
