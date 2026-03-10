<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\AlmasaVinculoBancarioRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AlmasaVinculoBancarioRepository::class)]
#[ORM\Table(
    name: 'almasa_vinculos_bancarios',
    uniqueConstraints: [
        new ORM\UniqueConstraint(name: 'uq_vinculo_conta_plano', columns: ['id_conta_bancaria', 'id_almasa_plano_conta']),
    ]
)]
#[ORM\HasLifecycleCallbacks]
class AlmasaVinculoBancario
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ContasBancarias::class)]
    #[ORM\JoinColumn(name: 'id_conta_bancaria', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ContasBancarias $contaBancaria;

    #[ORM\ManyToOne(targetEntity: AlmasaPlanoContas::class)]
    #[ORM\JoinColumn(name: 'id_almasa_plano_conta', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private AlmasaPlanoContas $almasaPlanoConta;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $observacao = null;

    #[ORM\Column(type: Types::BOOLEAN)]
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

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContaBancaria(): ContasBancarias
    {
        return $this->contaBancaria;
    }

    public function setContaBancaria(ContasBancarias $contaBancaria): self
    {
        $this->contaBancaria = $contaBancaria;
        return $this;
    }

    public function getAlmasaPlanoConta(): AlmasaPlanoContas
    {
        return $this->almasaPlanoConta;
    }

    public function setAlmasaPlanoConta(AlmasaPlanoContas $almasaPlanoConta): self
    {
        $this->almasaPlanoConta = $almasaPlanoConta;
        return $this;
    }

    public function getObservacao(): ?string
    {
        return $this->observacao;
    }

    public function setObservacao(?string $observacao): self
    {
        $this->observacao = $observacao;
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

    public function getContaBancariaLabel(): string
    {
        $cb = $this->contaBancaria;
        $banco = $cb->getIdBanco()?->getNome() ?? '';
        $agencia = $cb->getIdAgencia()?->getCodigo() ?? '';
        $conta = $cb->getCodigo();
        $digito = $cb->getDigitoConta() ? '-' . $cb->getDigitoConta() : '';
        $pessoa = $cb->getIdPessoa()?->getNome() ?? 'Almasa';

        return "{$banco} Ag:{$agencia} Cc:{$conta}{$digito} ({$pessoa})";
    }

    public function getPlanoContaLabel(): string
    {
        return $this->almasaPlanoConta->getCodigo() . ' - ' . $this->almasaPlanoConta->getDescricao();
    }

    public function __toString(): string
    {
        return $this->getContaBancariaLabel() . ' → ' . $this->getPlanoContaLabel();
    }
}
