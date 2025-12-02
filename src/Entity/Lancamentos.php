<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\LancamentosRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LancamentosRepository::class)]
#[ORM\Table(name: 'lancamentos')]
class Lancamentos
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private \DateTimeInterface $data;

    #[ORM\ManyToOne(targetEntity: PlanoContas::class, inversedBy: 'lancamentos')]
    #[ORM\JoinColumn(name: 'id_plano_conta', referencedColumnName: 'id', nullable: false)]
    private PlanoContas $planoConta;

    #[ORM\ManyToOne(targetEntity: Imoveis::class)]
    #[ORM\JoinColumn(name: 'id_imovel', referencedColumnName: 'id', nullable: true)]
    private ?Imoveis $imovel = null;

    #[ORM\ManyToOne(targetEntity: Pessoas::class)]
    #[ORM\JoinColumn(name: 'id_proprietario', referencedColumnName: 'idpessoa', nullable: true)]
    private ?Pessoas $proprietario = null;

    #[ORM\ManyToOne(targetEntity: Pessoas::class)]
    #[ORM\JoinColumn(name: 'id_inquilino', referencedColumnName: 'idpessoa', nullable: true)]
    private ?Pessoas $inquilino = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    private string $valor = '0.00';

    #[ORM\Column(name: 'tipo_sinal', type: Types::STRING, length: 1)]
    private string $tipoSinal = 'C';

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $historico = null;

    #[ORM\Column(name: 'numero_recibo', type: Types::INTEGER, nullable: true)]
    private ?int $numeroRecibo = null;

    #[ORM\Column(name: 'numero_documento', type: Types::STRING, length: 50, nullable: true)]
    private ?string $numeroDocumento = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private \DateTimeInterface $competencia;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $updatedAt;

    public function __construct()
    {
        $this->data = new \DateTime();
        $this->competencia = new \DateTime();
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getData(): \DateTimeInterface
    {
        return $this->data;
    }

    public function setData(\DateTimeInterface $data): self
    {
        $this->data = $data;
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

    public function getImovel(): ?Imoveis
    {
        return $this->imovel;
    }

    public function setImovel(?Imoveis $imovel): self
    {
        $this->imovel = $imovel;
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

    public function getInquilino(): ?Pessoas
    {
        return $this->inquilino;
    }

    public function setInquilino(?Pessoas $inquilino): self
    {
        $this->inquilino = $inquilino;
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

    public function getTipoSinal(): string
    {
        return $this->tipoSinal;
    }

    public function setTipoSinal(string $tipoSinal): self
    {
        $this->tipoSinal = $tipoSinal;
        return $this;
    }

    public function isCredito(): bool
    {
        return $this->tipoSinal === 'C';
    }

    public function isDebito(): bool
    {
        return $this->tipoSinal === 'D';
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

    public function getNumeroRecibo(): ?int
    {
        return $this->numeroRecibo;
    }

    public function setNumeroRecibo(?int $numeroRecibo): self
    {
        $this->numeroRecibo = $numeroRecibo;
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

    public function getCompetencia(): \DateTimeInterface
    {
        return $this->competencia;
    }

    public function setCompetencia(\DateTimeInterface $competencia): self
    {
        $this->competencia = $competencia;
        return $this;
    }

    public function getCompetenciaAno(): int
    {
        return (int) $this->competencia->format('Y');
    }

    public function getCompetenciaMes(): int
    {
        return (int) $this->competencia->format('n');
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
     * Retorna valor com sinal (positivo para crédito, negativo para débito)
     */
    public function getValorComSinal(): float
    {
        $valor = (float) $this->valor;
        return $this->isDebito() ? -$valor : $valor;
    }
}
