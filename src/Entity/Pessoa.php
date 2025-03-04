<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'pessoa')]
class Pessoa
{
    #[ORM\Id]
    #[ORM\Column(name: "idPessoa", type: "integer")]
    #[ORM\GeneratedValue]
    private int $idPessoa;

    #[ORM\Column(type: 'string', length: 255)]
    private string $nome;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $dtCadastro;

    #[ORM\Column(type: 'integer')]
    private int $tipoPessoa;

    #[ORM\Column(type: 'boolean')]
    private bool $status = true;

    #[ORM\Column(type: "boolean", options: ["default" => true])]
    private bool $themeLight = true; // Agora o campo theme_light está na Pessoa

    #[ORM\OneToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: "user_id", referencedColumnName: "id", nullable: false)]
    private ?User $user = null;

    public function getId(): ?int
    {
        return $this->idPessoa;
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

    public function getDtCadastro(): \DateTimeInterface
    {
        return $this->dtCadastro;
    }

    public function setDtCadastro(\DateTimeInterface $dtCadastro): self
    {
        $this->dtCadastro = $dtCadastro;
        return $this;
    }

    public function getTipoPessoa(): int
    {
        return $this->tipoPessoa;
    }

    public function setTipoPessoa(int $tipoPessoa): self
    {
        $this->tipoPessoa = $tipoPessoa;
        return $this;
    }

    public function isStatus(): bool
    {
        return $this->status;
    }

    public function setStatus(bool $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function isThemeLight(): bool
    {
        return $this->themeLight;
    }

    public function setThemeLight(bool $themeLight): self
    {
        $this->themeLight = $themeLight;
        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }


}
