<?php

namespace App\Entity;

use App\Repository\LogradouroRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: LogradouroRepository::class)]
#[ORM\Table(name: 'logradouros')]
class Logradouro
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id')]
    private ?int $id = null;

    #[ORM\Column(name: 'logradouro', length: 255)]
    #[Assert\NotBlank(message: 'O nome do logradouro é obrigatório.')]
    #[Assert\Length(
        max: 255,
        maxMessage: 'O nome do logradouro não pode ter mais de {{ limit }} caracteres.'
    )]
    private ?string $nome = null;

    #[ORM\Column(name: 'cep', length: 8)]
    #[Assert\NotBlank(message: 'O CEP é obrigatório.')]
    #[Assert\Length(
        exactMessage: 'O CEP deve ter exatamente 8 dígitos.',
        min: 8,
        max: 8
    )]
    #[Assert\Regex(
        pattern: '/^[0-9]{8}$/',
        message: 'O CEP deve conter apenas números e ter 8 dígitos.'
    )]
    private ?string $cep = null;

    #[ORM\ManyToOne(targetEntity: Bairros::class, inversedBy: 'logradouros')]
    #[ORM\JoinColumn(name: 'id_bairro', referencedColumnName: 'id', nullable: false)]
    #[Assert\NotNull(message: 'Selecione um bairro.')]
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
        // Remove qualquer caractere não numérico
        $cleanedCep = preg_replace('/[^0-9]/', '', $cep);
        $this->cep = $cleanedCep;

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
