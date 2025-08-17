<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'role_has_permissions')]
class RoleHasPermissions
{
    #[ORM\Id]
    #[ORM\Column(type: 'bigint')]
    private int $permissionId;

    #[ORM\Id]
    #[ORM\Column(type: 'bigint')]
    private int $roleId;

    public function getPermissionId(): int
    {
        return $this->permissionId;
    }

    public function setPermissionId(int $permissionId): self
    {
        $this->permissionId = $permissionId;
        return $this;
    }

    public function getRoleId(): int
    {
        return $this->roleId;
    }

    public function setRoleId(int $roleId): self
    {
        $this->roleId = $roleId;
        return $this;
    }
}
