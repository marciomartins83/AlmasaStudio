<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'personal_access_tokens')]
class PersonalAccessTokens
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;
    #[ORM\Column]
    private string $tokenableType;
    #[ORM\Column(type: 'bigint')]
    private int $tokenableId;
    #[ORM\Column]
    private string $name;
    #[ORM\Column]
    private string $token;
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $abilities = null;
    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $lastUsedAt = null;
    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $expiresAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTokenableType(): string
    {
        return $this->tokenableType;
    }

    public function setTokenableType(string $tokenableType): self
    {
        $this->tokenableType = $tokenableType;
        return $this;
    }

    public function getTokenableId(): int
    {
        return $this->tokenableId;
    }

    public function setTokenableId(int $tokenableId): self
    {
        $this->tokenableId = $tokenableId;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function setToken(string $token): self
    {
        $this->token = $token;
        return $this;
    }

    public function getAbilities(): ?string
    {
        return $this->abilities;
    }

    public function setAbilities(?string $abilities): self
    {
        $this->abilities = $abilities;
        return $this;
    }

    public function getLastUsedAt(): ?\DateTimeInterface
    {
        return $this->lastUsedAt;
    }

    public function setLastUsedAt(?\DateTimeInterface $lastUsedAt): self
    {
        $this->lastUsedAt = $lastUsedAt;
        return $this;
    }

    public function getExpiresAt(): ?\DateTimeInterface
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(?\DateTimeInterface $expiresAt): self
    {
        $this->expiresAt = $expiresAt;
        return $this;
    }

}
