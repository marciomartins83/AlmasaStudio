<?php

namespace App\Tests\Repository;

use App\Entity\TiposTelefones;
use PHPUnit\Framework\TestCase;

class TipoTelefoneRepositoryTest extends TestCase
{
    public function testEntityCanBeCreated(): void
    {
        $entity = new TiposTelefones();
        $this->assertInstanceOf(TiposTelefones::class, $entity);
    }

    public function testRepositoryClassExists(): void
    {
        $this->assertTrue(class_exists('App\\Repository\\TipoTelefoneRepository'));
    }

    public function testBasicEntityMethods(): void
    {
        $entity = new TiposTelefones();
        
        // Test that getId method exists and returns null initially
        $this->assertTrue(method_exists($entity, 'getId'));
        $this->assertNull($entity->getId());
    }

    public function testEntityStructure(): void
    {
        $entity = new TiposTelefones();
        $this->assertInstanceOf(TiposTelefones::class, $entity);
        $this->assertNull($entity->getId());
    }
}