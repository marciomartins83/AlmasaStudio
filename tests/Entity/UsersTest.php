<?php

namespace App\Tests\Entity;

use App\Entity\Users;
use PHPUnit\Framework\TestCase;

class UsersTest extends TestCase
{
    public function testCreateEntity(): void
    {
        $entity = new Users();
        $this->assertInstanceOf(Users::class, $entity);
    }

    public function testBasicMethods(): void
    {
        $entity = new Users();
        
        // Test that basic methods exist
        $this->assertTrue(method_exists($entity, 'getEmail'));
        $this->assertTrue(method_exists($entity, 'getRoles'));
    }

    public function testEntityStructure(): void
    {
        $entity = new Users();
        $this->assertInstanceOf(Users::class, $entity);
    }
}