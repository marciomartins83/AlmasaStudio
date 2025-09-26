<?php

namespace App\Entity;

use App\Repository\TiposEnderecosRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TiposEnderecosRepository::class)]
#[ORM\Table(name: 'tipos_enderecos')]
#[ORM\HasLifecycleCallbacks]
class TiposEnderecos
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 60)]
    private ?string $tipo = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, name: 'created_at')]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, name: 'updated_at')]
    private ?\DateTimeInterface $updatedAt = null;

    /**
     * Garante que as datas de criação e atualização sejam definidas
     * assim que um novo objeto é instanciado em memória, resolvendo
     * o erro na criação de formulários.
     */
    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTipo(): ?string
    {
        return $this->tipo;
    }

    public function setTipo(string $tipo): self
    {
        $this->tipo = $tipo;
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

    /**
     * Garante que a data de atualização seja definida no momento da persistência.
     * O createdAt já foi definido no construtor.
     */
    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->updatedAt = new \DateTime();
    }

    /**
     * Garante que a data de atualização seja alterada a cada update.
     */
    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTime();
    }
}

