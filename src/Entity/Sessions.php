<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'sessions')]
class Sessions
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;
    #[ORM\Column(type: 'bigint', nullable: true)]
    private ?int $userId = null;
    #[ORM\Column(nullable: true)]
    private ?string $ipAddress = null;
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $userAgent = null;
    #[ORM\Column(type: 'text')]
    private string $payload;
    #[ORM\Column]
    private int $lastActivity;

    public function getId(): string
    {
        return $this->id;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setUserId(?int $userId): self
    {
        $this->userId = $userId;
        return $this;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function setIpAddress(?string $ipAddress): self
    {
        $this->ipAddress = $ipAddress;
        return $this;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function setUserAgent(?string $userAgent): self
    {
        $this->userAgent = $userAgent;
        return $this;
    }

    public function getPayload(): string
    {
        return $this->payload;
    }

    public function setPayload(string $payload): self
    {
        $this->payload = $payload;
        return $this;
    }

    public function getLastActivity(): int
    {
        return $this->lastActivity;
    }

    public function setLastActivity(int $lastActivity): self
    {
        $this->lastActivity = $lastActivity;
        return $this;
    }

}
