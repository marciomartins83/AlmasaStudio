<?php

namespace App\Tests\Entity;

use App\Entity\Permissions;
use PHPUnit\Framework\TestCase;

class PermissionsTest extends TestCase
{
    public function testCreatePermission(): void
    {
        $permission = new Permissions();
        $this->assertInstanceOf(Permissions::class, $permission);
    }

    public function testPermissionGettersAndSetters(): void
    {
        $permission = new Permissions();
        $name = "edit-posts";
        $guardName = "web";

        $permission->setName($name);
        $permission->setGuardName($guardName);

        $this->assertEquals($name, $permission->getName());
        $this->assertEquals($guardName, $permission->getGuardName());
    }

    public function testPermissionVariousNames(): void
    {
        $permission = new Permissions();
        
        $names = ["create-users", "edit-posts", "delete-comments", "view-reports", "manage-settings"];
        
        foreach ($names as $name) {
            $permission->setName($name);
            $this->assertEquals($name, $permission->getName());
        }
    }

    public function testPermissionFluentInterface(): void
    {
        $permission = new Permissions();
        
        $result = $permission->setName("edit-posts")->setGuardName("web");
        
        $this->assertSame($permission, $result);
        $this->assertEquals("edit-posts", $permission->getName());
        $this->assertEquals("web", $permission->getGuardName());
    }
}
