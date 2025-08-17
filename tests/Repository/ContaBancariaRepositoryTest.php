<?php

namespace App\Tests\Repository;

use App\Entity\ContasBancarias;
use PHPUnit\Framework\TestCase;

class ContaBancariaRepositoryTest extends TestCase
{
    public function testEntityCanBeCreated(): void
    {
        $entity = new ContasBancarias();
        $this->assertInstanceOf(ContasBancarias::class, $entity);
    }

    public function testRepositoryClassExists(): void
    {
        $this->assertTrue(class_exists('App\\Repository\\ContaBancariaRepository'));
    }

    public function testBasicEntityMethods(): void
    {
        $entity = new ContasBancarias();
        
        // Test that getId method exists and returns null initially
        $this->assertTrue(method_exists($entity, 'getId'));
        $this->assertNull($entity->getId());
    }

    public function testEntityStructure(): void
    {
        $entity = new ContasBancarias();
        $this->assertInstanceOf(ContasBancarias::class, $entity);
        $this->assertNull($entity->getId());
    }
}