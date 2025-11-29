<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\PropriedadesCatalogoRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PropriedadesCatalogoRepository::class)]
#[ORM\Table(name: 'propriedades_catalogo')]
class PropriedadesCatalogo
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 50, unique: true)]
    private string $nome;

    #[ORM\Column(type: Types::STRING, length: 30, nullable: true)]
    private ?string $categoria = null;

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true)]
    private ?string $icone = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    private bool $ativo = true;

    /**
     * @var Collection<int, ImoveisPropriedades>
     */
    #[ORM\OneToMany(targetEntity: ImoveisPropriedades::class, mappedBy: 'propriedade')]
    private Collection $imoveisPropriedades;

    public function __construct()
    {
        $this->imoveisPropriedades = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNome(): string
    {
        return $this->nome;
    }

    public function setNome(string $nome): self
    {
        $this->nome = $nome;
        return $this;
    }

    public function getCategoria(): ?string
    {
        return $this->categoria;
    }

    public function setCategoria(?string $categoria): self
    {
        $this->categoria = $categoria;
        return $this;
    }

    public function getIcone(): ?string
    {
        return $this->icone;
    }

    public function setIcone(?string $icone): self
    {
        $this->icone = $icone;
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

    /**
     * @return Collection<int, ImoveisPropriedades>
     */
    public function getImoveisPropriedades(): Collection
    {
        return $this->imoveisPropriedades;
    }

    public function addImoveisPropriedade(ImoveisPropriedades $imoveisPropriedade): self
    {
        if (!$this->imoveisPropriedades->contains($imoveisPropriedade)) {
            $this->imoveisPropriedades->add($imoveisPropriedade);
            $imoveisPropriedade->setPropriedade($this);
        }

        return $this;
    }

    public function removeImoveisPropriedade(ImoveisPropriedades $imoveisPropriedade): self
    {
        if ($this->imoveisPropriedades->removeElement($imoveisPropriedade)) {
            if ($imoveisPropriedade->getPropriedade() === $this) {
                $imoveisPropriedade->setPropriedade(null);
            }
        }

        return $this;
    }
}
