<?php

namespace App\Tests\Entity;

use App\Entity\TiposChavesPix;
use PHPUnit\Framework\TestCase;

class TiposChavesPixTest extends TestCase
{
    public function testCreateEntity(): void
    {
        $entity = new TiposChavesPix();
        $this->assertInstanceOf(TiposChavesPix::class, $entity);
    }

    public function testBasicMethods(): void
    {
        $entity = new TiposChavesPix();
        
        // Test that basic methods exist
        $this->assertTrue(method_exists($entity, 'getId'));
        $this->assertTrue(method_exists($entity, 'getTipo'));
    }

    public function testEntityStructure(): void
    {
        $entity = new TiposChavesPix();
        $this->assertInstanceOf(TiposChavesPix::class, $entity);
    }
}