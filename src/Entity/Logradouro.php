<?php

namespace App\Entity;

use App\Repository\LogradouroRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LogradouroRepository::class)]
class Logradouro
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nome = null;

    #[ORM\Column(length: 8)]
    private ?string $cep = null;

    #[ORM\ManyToOne(inversedBy: 'logradouros')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Bairros $bairro = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNome(): ?string
    {
        return $this->nome;
    }

    public function setNome(string $nome): self
    {
        $this->nome = $nome;

        return $this;
    }

    public function getCep(): ?string
    {
        return $this->cep;
    }

    public function setCep(string $cep): self
    {
        $this->cep = $cep;

        return $this;
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
}
