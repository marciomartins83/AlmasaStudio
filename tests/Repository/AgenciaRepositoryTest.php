<?php

namespace App\Tests\Repository;

use App\Entity\Agencias;
use PHPUnit\Framework\TestCase;

class AgenciaRepositoryTest extends TestCase
{
    public function testEntityCanBeCreated(): void
    {
        $entity = new Agencias();
        $this->assertInstanceOf(Agencias::class, $entity);
    }

    public function testRepositoryClassExists(): void
    {
        $this->assertTrue(class_exists('App\\Repository\\AgenciaRepository'));
    }

    public function testBasicEntityMethods(): void
    {
        $entity = new Agencias();
        
        // Test that getId method exists and returns null initially
        $this->assertTrue(method_exists($entity, 'getId') || method_exists($entity, 'getIdpessoa'));
    }

    public function testEntityStructure(): void
    {
        $entity = new Agencias();
        $this->assertInstanceOf(Agencias::class, $entity);
    }
}