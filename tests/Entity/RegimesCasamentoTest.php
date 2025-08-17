<?php

namespace App\Tests\Entity;

use App\Entity\RegimesCasamento;
use PHPUnit\Framework\TestCase;

class RegimesCasamentoTest extends TestCase
{
    public function testCreateEntity(): void
    {
        $entity = new RegimesCasamento();
        $this->assertInstanceOf(RegimesCasamento::class, $entity);
    }

    public function testBasicMethods(): void
    {
        $entity = new RegimesCasamento();
        
        // Test that basic methods exist
        $this->assertTrue(method_exists($entity, 'getId'));
        $this->assertTrue(method_exists($entity, 'getRegime'));
    }

    public function testEntityStructure(): void
    {
        $entity = new RegimesCasamento();
        $this->assertInstanceOf(RegimesCasamento::class, $entity);
    }
}