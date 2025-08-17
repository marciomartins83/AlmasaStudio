<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'model_has_roles')]
class ModelHasRoles
{
    #[ORM\Id]
    #[ORM\Column(type: 'bigint')]
    private int $roleId;

    #[ORM\Id]
    #[ORM\Column]
    private string $modelType;

    #[ORM\Id]
    #[ORM\Column(type: 'bigint')]
    private int $modelId;

    public function getRoleId(): int
    {
        return $this->roleId;
    }

    public function setRoleId(int $roleId): self
    {
        $this->roleId = $roleId;
        return $this;
    }

    public function getModelType(): string
    {
        return $this->modelType;
    }

    public function setModelType(string $modelType): self
    {
        $this->modelType = $modelType;
        return $this;
    }

    public function getModelId(): int
    {
        return $this->modelId;
    }

    public function setModelId(int $modelId): self
    {
        $this->modelId = $modelId;
        return $this;
    }
}
