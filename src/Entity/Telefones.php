<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'telefones')]
class Telefones
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column]
    private ?int $id = null;
    #[ORM\ManyToOne(targetEntity: TiposTelefones::class)]
    #[ORM\JoinColumn(name: 'id_tipo', referencedColumnName: 'id', nullable: false)]
    private ?TiposTelefones $tipo = null;
    #[ORM\Column(length: 30)]
    private ?string $numero = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTipo(): ?TiposTelefones
    {
        return $this->tipo;
    }

    public function setTipo(?TiposTelefones $tipo): self
    {
        $this->tipo = $tipo;
        return $this;
    }

    public function getNumero(): ?string
    {
        return $this->numero;
    }

    public function setNumero(string $numero): self
    {
        $this->numero = $numero;
        return $this;
    }

}
