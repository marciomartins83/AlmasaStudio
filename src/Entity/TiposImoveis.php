<?php

namespace App\Entity;

use App\Repository\TiposImoveisRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TiposImoveisRepository::class)]
#[ORM\Table(name: 'tipos_imoveis')]
#[ORM\HasLifecycleCallbacks]
class TiposImoveis
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 60)]
    private string $tipo;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $descricao = null;

    #[ORM\Column(name: 'created_at', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(name: 'updated_at', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    /**
     * @var Collection<int, Imoveis>
     */
    #[ORM\OneToMany(targetEntity: Imoveis::class, mappedBy: 'tipoImovel')]
    private Collection $imoveis;

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

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function __construct()
    {
        $this->imoveis = new ArrayCollection();
    }

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $now = new \DateTime();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTime();
    }

    /**
     * @return Collection<int, Imoveis>
     */
    public function getImoveis(): Collection
    {
        return $this->imoveis;
    }

    public function addImovel(Imoveis $imovel): self
    {
        if (!$this->imoveis->contains($imovel)) {
            $this->imoveis->add($imovel);
            $imovel->setTipoImovel($this);
        }

        return $this;
    }

    public function removeImovel(Imoveis $imovel): self
    {
        if ($this->imoveis->removeElement($imovel)) {
            if ($imovel->getTipoImovel() === $this) {
                $imovel->setTipoImovel(null);
            }
        }

        return $this;
    }
}
