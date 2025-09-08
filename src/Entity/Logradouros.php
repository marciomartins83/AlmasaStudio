<?php

namespace App\Entity;

use App\Repository\LogradourosRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: LogradourosRepository::class)]
#[ORM\Table(name: 'logradouros')]
#[ORM\HasLifecycleCallbacks]
class Logradouros
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Bairros::class, inversedBy: 'logradouros')]
    #[ORM\JoinColumn(name: 'id_bairro', referencedColumnName: 'id', nullable: false)]
    #[Assert\NotNull(message: 'Selecione um bairro.')]
    private ?Bairros $bairro = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'O nome do logradouro é obrigatório.')]
    #[Assert\Length(
        max: 255,
        maxMessage: 'O nome do logradouro não pode ter mais de {{ limit }} caracteres.'
    )]
    private ?string $logradouro = null;

    #[ORM\Column(length: 8)]
    #[Assert\NotBlank(message: 'O CEP é obrigatório.')]
    #[Assert\Regex(
        pattern: '/^[0-9]{8}$/',
        message: 'O CEP deve conter apenas números e ter 8 dígitos.'
    )]
    private ?string $cep = null;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(name: 'updated_at', type: 'datetime')]
    private ?\DateTimeInterface $updatedAt = null;

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

    public function getBairro(): ?Bairros
    {
        return $this->bairro;
    }

    public function setBairro(?Bairros $bairro): self
    {
        $this->bairro = $bairro;
        return $this;
    }

    public function getLogradouro(): ?string
    {
        return $this->logradouro;
    }

    public function setLogradouro(?string $logradouro): self
    {
        $this->logradouro = $logradouro;
        return $this;
    }

    public function getCep(): ?string
    {
        return $this->cep;
    }

    public function setCep(string $cep): self
    {
        $cleanedCep = preg_replace('/[^0-9]/', '', $cep);
        $this->cep = $cleanedCep;
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
}
