<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\TiposRemessaRepository;

#[ORM\Entity(repositoryClass: TiposRemessaRepository::class)]
#[ORM\Table(name: 'tipos_remessa')]
class TiposRemessa
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column]
    private ?int $id = null;
    #[ORM\Column]
    private string $tipo;
    #[ORM\Column(nullable: true)]
    private ?string $descricao = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTipo(): string
    {
        return $this->tipo;
    }

    public function setTipo(string $tipo): self
    {
        $this->tipo = $tipo;
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

}
