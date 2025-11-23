<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'emails')]
class Emails
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column]
    private ?int $id = null;
    #[ORM\Column]
    private string $email;
#[ORM\ManyToOne(targetEntity: TiposEmails::class)]
#[ORM\JoinColumn(name: 'id_tipo', referencedColumnName: 'id')]
private ?TiposEmails $tipo = null;
    #[ORM\Column(nullable: true)]
    private ?string $descricao = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

public function getTipo(): ?TiposEmails
{
    return $this->tipo;
}

public function setTipo(?TiposEmails $tipo): self
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

}
