<?php

namespace App\Entity;

use App\Repository\PessoaCorretoraRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PessoaCorretoraRepository::class)]
#[ORM\Table(name: 'pessoas_corretoras')]
#[ORM\HasLifecycleCallbacks]
class PessoasCorretoras
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: Pessoas::class)]
    #[ORM\JoinColumn(name: 'id_pessoa', referencedColumnName: 'idpessoa', nullable: false)]
    private ?Pessoas $pessoa = null;

    #[ORM\Column(name: 'created_at', type: 'datetime', nullable: false)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(name: 'updated_at', type: 'datetime', nullable: false)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(name: 'cod', type: 'integer', nullable: true)]
    private ?int $cod = null;

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

    public function getPessoa(): ?Pessoas
    {
        return $this->pessoa;
    }

    public function setPessoa(?Pessoas $pessoa): self
    {
        $this->pessoa = $pessoa;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function getCod(): ?int
    {
        return $this->cod;
    }

    public function setCod(?int $cod): self
    {
        $this->cod = $cod;
        return $this;
    }
}