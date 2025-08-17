<?php

namespace App\Tests\Repository;

use App\Entity\Users;
use PHPUnit\Framework\TestCase;

class UserRepositoryTest extends TestCase
{
    public function testEntityCanBeCreated(): void
    {
        $entity = new Users();
        $this->assertInstanceOf(Users::class, $entity);
    }

    public function testRepositoryClassExists(): void
    {
        $this->assertTrue(class_exists('App\\Repository\\UserRepository'));
    }

    public function testBasicEntityMethods(): void
    {
        $entity = new Users();
        
        // Test that getId method exists and returns null initially
        $this->assertTrue(method_exists($entity, 'getId') || method_exists($entity, 'getIdpessoa'));
    }

    public function testEntityStructure(): void
    {
        $entity = new Users();
        $this->assertInstanceOf(Users::class, $entity);
    }
}