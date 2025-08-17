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
        $this->assertTrue(method_exists($entity, 'getIdPessoa'));
    }

    public function testEntityStructure(): void
    {
        $entity = new PessoasContratantes();
        $this->assertInstanceOf(PessoasContratantes::class, $entity);
    }
}