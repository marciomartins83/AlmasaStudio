<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'failed_jobs')]
class FailedJobs
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;
    #[ORM\Column]
    private string $uuid;
    #[ORM\Column(type: 'text')]
    private string $connection;
    #[ORM\Column(type: 'text')]
    private string $queue;
    #[ORM\Column(type: 'text')]
    private string $payload;
    #[ORM\Column(type: 'text')]
    private string $exception;
    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $failedAt;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function setUuid(string $uuid): self
    {
        $this->uuid = $uuid;
        return $this;
    }

    public function getConnection(): string
    {
        return $this->connection;
    }

    public function setConnection(string $connection): self
    {
        $this->connection = $connection;
        return $this;
    }

    public function getQueue(): string
    {
        return $this->queue;
    }

    public function setQueue(string $queue): self
    {
        $this->queue = $queue;
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

    public function getException(): string
    {
        return $this->exception;
    }

    public function setException(string $exception): self
    {
        $this->exception = $exception;
        return $this;
    }

    public function getFailedAt(): \DateTimeInterface
    {
        return $this->failedAt;
    }

    public function setFailedAt(\DateTimeInterface $failedAt): self
    {
        $this->failedAt = $failedAt;
        return $this;
    }

}
