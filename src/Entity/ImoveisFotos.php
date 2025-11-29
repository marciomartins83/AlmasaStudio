<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ImoveisFotosRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ImoveisFotosRepository::class)]
#[ORM\Table(name: 'imoveis_fotos')]
class ImoveisFotos
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Imoveis::class, inversedBy: 'fotos')]
    #[ORM\JoinColumn(name: 'id_imovel', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Imoveis $imovel;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $arquivo;

    #[ORM\Column(type: Types::STRING, length: 500)]
    private string $caminho;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $legenda = null;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    private int $ordem = 0;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $capa = false;

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

    public function getArquivo(): string
    {
        return $this->arquivo;
    }

    public function setArquivo(string $arquivo): self
    {
        $this->arquivo = $arquivo;
        return $this;
    }

    public function getCaminho(): string
    {
        return $this->caminho;
    }

    public function setCaminho(string $caminho): self
    {
        $this->caminho = $caminho;
        return $this;
    }

    public function getLegenda(): ?string
    {
        return $this->legenda;
    }

    public function setLegenda(?string $legenda): self
    {
        $this->legenda = $legenda;
        return $this;
    }

    public function getOrdem(): int
    {
        return $this->ordem;
    }

    public function setOrdem(int $ordem): self
    {
        $this->ordem = $ordem;
        return $this;
    }

    public function isCapa(): bool
    {
        return $this->capa;
    }

    public function setCapa(bool $capa): self
    {
        $this->capa = $capa;
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
