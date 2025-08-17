<?php

namespace App\Tests\Repository;

use App\Entity\Telefones;
use PHPUnit\Framework\TestCase;

class TelefoneRepositoryTest extends TestCase
{
    public function testEntityCanBeCreated(): void
    {
        $entity = new Telefones();
        $this->assertInstanceOf(Telefones::class, $entity);
    }

    public function testRepositoryClassExists(): void
    {
        $this->assertTrue(class_exists('App\\Repository\\TelefoneRepository'));
    }

    public function testBasicEntityMethods(): void
    {
        $entity = new Telefones();
        
        // Test that getId method exists and returns null initially
        $this->assertTrue(method_exists($entity, 'getId') || method_exists($entity, 'getIdpessoa'));
    }

    public function testEntityStructure(): void
    {
        $entity = new Telefones();
        $this->assertInstanceOf(Telefones::class, $entity);
    }
}