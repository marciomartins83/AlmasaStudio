<?php

namespace App\Tests\Entity;

use App\Entity\TiposContasBancarias;
use PHPUnit\Framework\TestCase;

class TiposContasBancariasTest extends TestCase
{
    public function testCreateEntity(): void
    {
        $entity = new TiposContasBancarias();
        $this->assertInstanceOf(TiposContasBancarias::class, $entity);
    }

    public function testBasicMethods(): void
    {
        $entity = new TiposContasBancarias();
        
        // Test that basic methods exist
        $this->assertTrue(method_exists($entity, 'getId'));
        $this->assertTrue(method_exists($entity, 'getTipo'));
    }

    public function testEntityStructure(): void
    {
        $entity = new TiposContasBancarias();
        $this->assertInstanceOf(TiposContasBancarias::class, $entity);
    }
}