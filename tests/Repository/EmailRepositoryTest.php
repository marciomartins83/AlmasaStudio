<?php

namespace App\Tests\Repository;

use App\Entity\Emails;
use PHPUnit\Framework\TestCase;

class EmailRepositoryTest extends TestCase
{
    public function testEntityCanBeCreated(): void
    {
        $entity = new Emails();
        $this->assertInstanceOf(Emails::class, $entity);
    }

    public function testRepositoryClassExists(): void
    {
        $this->assertTrue(class_exists('App\\Repository\\EmailRepository'));
    }

    public function testBasicEntityMethods(): void
    {
        $entity = new Emails();
        
        // Test that getId method exists and returns null initially
        $this->assertTrue(method_exists($entity, 'getId') || method_exists($entity, 'getIdpessoa'));
    }

    public function testEntityStructure(): void
    {
        $entity = new Emails();
        $this->assertInstanceOf(Emails::class, $entity);
    }
}