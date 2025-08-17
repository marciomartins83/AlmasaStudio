<?php

namespace App\Tests\Entity;

use App\Entity\TiposDocumentos;
use PHPUnit\Framework\TestCase;

class TiposDocumentosTest extends TestCase
{
    public function testCreateEntity(): void
    {
        $entity = new TiposDocumentos();
        $this->assertInstanceOf(TiposDocumentos::class, $entity);
    }

    public function testBasicMethods(): void
    {
        $entity = new TiposDocumentos();
        
        // Test that basic methods exist
        $this->assertTrue(method_exists($entity, 'getId'));
        $this->assertTrue(method_exists($entity, 'getTipo'));
    }

    public function testEntityStructure(): void
    {
        $entity = new TiposDocumentos();
        $this->assertInstanceOf(TiposDocumentos::class, $entity);
    }
}