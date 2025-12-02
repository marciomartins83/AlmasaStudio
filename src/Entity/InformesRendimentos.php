<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\InformesRendimentosRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InformesRendimentosRepository::class)]
#[ORM\Table(name: 'informes_rendimentos')]
#[ORM\UniqueConstraint(name: 'uk_informes_composicao', columns: ['ano', 'id_proprietario', 'id_imovel', 'id_inquilino', 'id_plano_conta'])]
class InformesRendimentos
{
    public const STATUS_PENDENTE = 'pendente';
    public const STATUS_PROCESSADO = 'processado';
    public const STATUS_REVISADO = 'revisado';
    public const STATUS_FINALIZADO = 'finalizado';

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::INTEGER)]
    private int $ano;

    #[ORM\ManyToOne(targetEntity: Pessoas::class)]
    #[ORM\JoinColumn(name: 'id_proprietario', referencedColumnName: 'idpessoa', nullable: false)]
    private Pessoas $proprietario;

    #[ORM\ManyToOne(targetEntity: Imoveis::class)]
    #[ORM\JoinColumn(name: 'id_imovel', referencedColumnName: 'id', nullable: false)]
    private Imoveis $imovel;

    #[ORM\ManyToOne(targetEntity: Pessoas::class)]
    #[ORM\JoinColumn(name: 'id_inquilino', referencedColumnName: 'idpessoa', nullable: false)]
    private Pessoas $inquilino;

    #[ORM\ManyToOne(targetEntity: PlanoContas::class, inversedBy: 'informesRendimentos')]
    #[ORM\JoinColumn(name: 'id_plano_conta', referencedColumnName: 'id', nullable: false)]
    private PlanoContas $planoConta;

    #[ORM\Column(type: Types::STRING, length: 20)]
    private string $status = self::STATUS_PENDENTE;

    #[ORM\Column(name: 'data_processamento', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dataProcessamento = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $updatedAt;

    /**
     * @var Collection<int, InformesRendimentosValores>
     */
    #[ORM\OneToMany(
        targetEntity: InformesRendimentosValores::class,
        mappedBy: 'informe',
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    private Collection $valores;

    public function __construct()
    {
        $this->ano = (int) date('Y');
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
        $this->valores = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAno(): int
    {
        return $this->ano;
    }

    public function setAno(int $ano): self
    {
        $this->ano = $ano;
        return $this;
    }

    public function getProprietario(): Pessoas
    {
        return $this->proprietario;
    }

    public function setProprietario(Pessoas $proprietario): self
    {
        $this->proprietario = $proprietario;
        return $this;
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

    public function getInquilino(): Pessoas
    {
        return $this->inquilino;
    }

    public function setInquilino(Pessoas $inquilino): self
    {
        $this->inquilino = $inquilino;
        return $this;
    }

    public function getPlanoConta(): PlanoContas
    {
        return $this->planoConta;
    }

    public function setPlanoConta(?PlanoContas $planoConta): self
    {
        $this->planoConta = $planoConta;
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

    public function getDataProcessamento(): ?\DateTimeInterface
    {
        return $this->dataProcessamento;
    }

    public function setDataProcessamento(?\DateTimeInterface $dataProcessamento): self
    {
        $this->dataProcessamento = $dataProcessamento;
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

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    /**
     * @return Collection<int, InformesRendimentosValores>
     */
    public function getValores(): Collection
    {
        return $this->valores;
    }

    public function addValor(InformesRendimentosValores $valor): self
    {
        if (!$this->valores->contains($valor)) {
            $this->valores->add($valor);
            $valor->setInforme($this);
        }
        return $this;
    }

    public function removeValor(InformesRendimentosValores $valor): self
    {
        if ($this->valores->removeElement($valor)) {
            if ($valor->getInforme() === $this) {
                $valor->setInforme(null);
            }
        }
        return $this;
    }

    /**
     * Retorna o valor de um mês específico
     */
    public function getValorMes(int $mes): float
    {
        foreach ($this->valores as $valor) {
            if ($valor->getMes() === $mes) {
                return (float) $valor->getValor();
            }
        }
        return 0.0;
    }

    /**
     * Define o valor de um mês específico
     */
    public function setValorMes(int $mes, float $valorMes): self
    {
        foreach ($this->valores as $valor) {
            if ($valor->getMes() === $mes) {
                $valor->setValor((string) $valorMes);
                return $this;
            }
        }

        // Se não existe, cria novo
        $novoValor = new InformesRendimentosValores();
        $novoValor->setMes($mes);
        $novoValor->setValor((string) $valorMes);
        $this->addValor($novoValor);

        return $this;
    }

    /**
     * Retorna o total anual (soma de todos os meses)
     */
    public function getTotalAnual(): float
    {
        $total = 0.0;
        foreach ($this->valores as $valor) {
            $total += (float) $valor->getValor();
        }
        return $total;
    }

    /**
     * Retorna valores como array [mes => valor]
     */
    public function getValoresArray(): array
    {
        $resultado = [];
        for ($mes = 1; $mes <= 12; $mes++) {
            $resultado[$mes] = 0.0;
        }

        foreach ($this->valores as $valor) {
            $resultado[$valor->getMes()] = (float) $valor->getValor();
        }

        return $resultado;
    }

    /**
     * Verifica se o informe está finalizado
     */
    public function isFinalizado(): bool
    {
        return $this->status === self::STATUS_FINALIZADO;
    }

    /**
     * Verifica se pode ser editado
     */
    public function isEditavel(): bool
    {
        return !$this->isFinalizado();
    }
}
