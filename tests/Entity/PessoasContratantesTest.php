<?php

namespace App\Tests\Entity;

use App\Entity\PessoasContratantes;
use PHPUnit\Framework\TestCase;

class PessoasContratantesTest extends TestCase
{
    public function testCreateEntity(): void
    {
        $entity = new PessoasContratantes();
        $this->assertInstanceOf(PessoasContratantes::class, $entity);
    }

    public function testBasicMethods(): void
    {
        $entity = new PessoasContratantes();

        // Test that basic methods exist
        $this->assertTrue(method_exists($entity, 'getId'));
        $this->assertTrue(method_exists($entity, 'getPessoa'));
        $this->assertTrue(method_exists($entity, 'setPessoa'));
        $this->assertTrue(method_exists($entity, 'getCreatedAt'));
        $this->assertTrue(method_exists($entity, 'getUpdatedAt'));
    }

    public function testEntityStructure(): void
    {
        $entity = new PessoasContratantes();
        $this->assertInstanceOf(PessoasContratantes::class, $entity);
    }
}