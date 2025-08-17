<?php

namespace App\Tests\Entity;

use App\Entity\Roles;
use PHPUnit\Framework\TestCase;

class RolesTest extends TestCase
{
    public function testCreateRole(): void
    {
        $role = new Roles();
        $this->assertInstanceOf(Roles::class, $role);
    }

    public function testRoleGettersAndSetters(): void
    {
        $role = new Roles();
        $name = "admin";
        $guardName = "web";

        $role->setName($name);
        $role->setGuardName($guardName);

        $this->assertEquals($name, $role->getName());
        $this->assertEquals($guardName, $role->getGuardName());
    }

    public function testRoleVariousNames(): void
    {
        $role = new Roles();
        
        $names = ["admin", "user", "moderator", "editor", "viewer"];
        
        foreach ($names as $name) {
            $role->setName($name);
            $this->assertEquals($name, $role->getName());
        }
    }

    public function testRoleFluentInterface(): void
    {
        $role = new Roles();
        
        $result = $role->setName("admin")->setGuardName("web");
        
        $this->assertSame($role, $result);
        $this->assertEquals("admin", $role->getName());
        $this->assertEquals("web", $role->getGuardName());
    }
}
