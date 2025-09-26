<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'tipos_chaves_pix')]
class TiposChavesPix
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Propriedade ajustada para corresponder à coluna 'tipo' da tabela no banco de dados.
     * O tipo foi definido como não nulo para refletir a restrição 'not null' da tabela.
     */
    #[ORM\Column(name: 'tipo', length: 255)]
    private string $tipo;

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
}

