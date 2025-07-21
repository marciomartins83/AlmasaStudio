<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\PessoaRepository;

#[ORM\Entity(repositoryClass: PessoaRepository::class)]
class Pessoa
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'idpessoa')] // Mapeia para a coluna 'idpessoa'
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nome = null;

    #[ORM\Column(name: 'dt_cadastro')]
    private ?\DateTimeImmutable $dtCadastro = null;

    #[ORM\Column(name: 'tipo_pessoa')]
    private ?int $tipoPessoa = null;

    #[ORM\Column]
    private ?bool $status = null;

    #[ORM\Column(name: 'theme_light')]
    private ?bool $themeLight = null;

    // Relacionamento One-to-One com User
    #[ORM\OneToOne(targetEntity: User::class, inversedBy: 'pessoa')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id')]
    private ?User $user = null;

    // GETTERS E SETTERS...
    
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNome(): ?string
    {
        return $this->nome;
    }

    public function setNome(string $nome): static
    {
        $this->nome = $nome;
        return $this;
    }

    // Adicione os getters e setters para os outros campos...

    public function isThemeLight(): ?bool
    {
        return $this->themeLight;
    }

    public function setThemeLight(bool $themeLight): static
    {
        $this->themeLight = $themeLight;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }
}