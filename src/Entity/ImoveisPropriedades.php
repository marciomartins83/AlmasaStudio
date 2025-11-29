<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ImoveisPropriedadesRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ImoveisPropriedadesRepository::class)]
#[ORM\Table(name: 'imoveis_propriedades')]
#[ORM\UniqueConstraint(name: 'uk_imovel_propriedade', columns: ['id_imovel', 'id_propriedade'])]
class ImoveisPropriedades
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Imoveis::class, inversedBy: 'propriedades')]
    #[ORM\JoinColumn(name: 'id_imovel', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Imoveis $imovel;

    #[ORM\ManyToOne(targetEntity: PropriedadesCatalogo::class, inversedBy: 'imoveisPropriedades')]
    #[ORM\JoinColumn(name: 'id_propriedade', referencedColumnName: 'id', nullable: false)]
    private PropriedadesCatalogo $propriedade;

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

    public function getPropriedade(): PropriedadesCatalogo
    {
        return $this->propriedade;
    }

    public function setPropriedade(PropriedadesCatalogo $propriedade): self
    {
        $this->propriedade = $propriedade;
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
